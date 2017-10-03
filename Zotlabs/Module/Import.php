<?php

namespace Zotlabs\Module;

require_once('include/zot.php');
require_once('include/channel.php');
require_once('include/import.php');
require_once('include/perm_upgrade.php');


/**
 * @brief Module for channel import.
 *
 * Import a channel, either by direct file upload or via
 * connection to another server.
 */
class Import extends \Zotlabs\Web\Controller {

	/**
	 * @brief Import channel into account.
	 *
	 * @param int $account_id
	 */
	function import_account($account_id) {

		if(! $account_id){
			logger('No account ID supplied');
			return;
		}

		$max_friends    = account_service_class_fetch($account_id,'total_channels');
		$max_feeds      = account_service_class_fetch($account_id,'total_feeds');
		$data           = null;
		$seize          = ((x($_REQUEST,'make_primary')) ? intval($_REQUEST['make_primary']) : 0);
		$import_posts   = ((x($_REQUEST,'import_posts')) ? intval($_REQUEST['import_posts']) : 0);
		$moving         = intval($_REQUEST['moving']);
		$src            = $_FILES['filename']['tmp_name'];
		$filename       = basename($_FILES['filename']['name']);
		$filesize       = intval($_FILES['filename']['size']);
		$filetype       = $_FILES['filename']['type'];

		// import channel from file
		if($src) {

			// This is OS specific and could also fail if your tmpdir isn't very
			// large mostly used for Diaspora which exports gzipped files.

			if(strpos($filename,'.gz')){
				@rename($src,$src . '.gz');
				@system('gunzip ' . escapeshellarg($src . '.gz'));
			}

			if($filesize) {
				$data = @file_get_contents($src);
			}
			unlink($src);
		}

		// import channel from another server
		if(! $src) {
			$old_address = ((x($_REQUEST,'old_address')) ? $_REQUEST['old_address'] : '');
			if(! $old_address) {
				logger('Nothing to import.');
				notice( t('Nothing to import.') . EOL);
				return;
			} else if(strpos($old_address, '＠')) {
				// if you copy the identity address from your profile page, make it work for convenience
				$old_address = str_replace('＠', '@', $old_address);
			}

			$email    = ((x($_REQUEST,'email'))    ? $_REQUEST['email']    : '');
			$password = ((x($_REQUEST,'password')) ? $_REQUEST['password'] : '');

			$channelname = substr($old_address,0,strpos($old_address,'@'));
			$servername  = substr($old_address,strpos($old_address,'@')+1);

			$api_path = probe_api_path($servername);
			if(! $api_path) {
				notice( t('Unable to download data from old server') . EOL);
				return;
			}

			$api_path .= 'channel/export/basic?f=&channel=' . $channelname;
			if($import_posts)
				$api_path .= '&posts=1';

			$binary = false;
			$redirects = 0;
			$opts = array('http_auth' => $email . ':' . $password);
			$ret = z_fetch_url($api_path, $binary, $redirects, $opts);
			if($ret['success']) {
				$data = $ret['body'];
			}
			else {
				notice( t('Unable to download data from old server') . EOL);
				return;
			}
		}

		if(! $data) {
			logger('Empty import file.');
			notice( t('Imported file is empty.') . EOL);
			return;
		}

		$data = json_decode($data,true);

		//logger('import: data: ' . print_r($data,true));
		//print_r($data);

		if(! array_key_exists('compatibility',$data)) {
			call_hooks('import_foreign_channel_data',$data);
			if($data['handled'])
				return;
		}

		if(array_key_exists('compatibility',$data) && array_key_exists('database',$data['compatibility'])) {
			$v1 = substr($data['compatibility']['database'],-4);
			$v2 = substr(DB_UPDATE_VERSION,-4);
			if($v2 > $v1) {
				$t = sprintf( t('Warning: Database versions differ by %1$d updates.'), $v2 - $v1 );
				notice($t);
			}

		}

		if($moving)
			$seize = 1;

		// import channel

		$relocate = ((array_key_exists('relocate',$data)) ? $data['relocate'] : null);

		if(array_key_exists('channel',$data)) {

			$max_identities = account_service_class_fetch($account_id,'total_identities');

			if($max_identities !== false) {
				$r = q("select channel_id from channel where channel_account_id = %d",
					intval($account_id)
				);
				if($r && count($r) > $max_identities) {
					notice( sprintf( t('Your service plan only allows %d channels.'), $max_identities) . EOL);
					return;
				}
			}

			$channel = import_channel($data['channel'], $account_id, $seize);
		}
		else {
			$moving  = false;
			$channel = \App::get_channel();
		}

		if(! $channel) {
			logger('Channel not found. ', print_r($channel,true));
			notice( t('No channel. Import failed.') . EOL);
			return;
		}

		if(is_array($data['config'])) {
			import_config($channel,$data['config']);
		}

		logger('import step 2');

		if(array_key_exists('channel',$data)) {
			if($data['photo']) {
				require_once('include/photo/photo_driver.php');
				import_channel_photo(base64url_decode($data['photo']['data']),$data['photo']['type'],$account_id,$channel['channel_id']);
			}

			if(is_array($data['profile']))
				import_profiles($channel,$data['profile']);
		}

		logger('import step 3');

		if(is_array($data['hubloc'])) {
			import_hublocs($channel,$data['hubloc'],$seize,$moving);
		}

		logger('import step 4');

		// create new hubloc for the new channel at this site

		if(array_key_exists('channel',$data)) {
			$r = hubloc_store_lowlevel(
				[
					'hubloc_guid'     => $channel['channel_guid'],
					'hubloc_guid_sig' => $channel['channel_guid_sig'],
					'hubloc_hash'     => $channel['channel_hash'],
					'hubloc_addr'     => channel_reddress($channel),
					'hubloc_network'  => 'zot',
					'hubloc_primary'  => (($seize) ? 1 : 0),
					'hubloc_url'      => z_root(),
					'hubloc_url_sig'  => base64url_encode(rsa_sign(z_root(),$channel['channel_prvkey'])),
					'hubloc_host'     => \App::get_hostname(),
					'hubloc_callback' => z_root() . '/post',
					'hubloc_sitekey'  => get_config('system','pubkey'),
					'hubloc_updated'  => datetime_convert()
				]
			);

			// reset the original primary hubloc if it is being seized

			if($seize) {
				$r = q("update hubloc set hubloc_primary = 0 where hubloc_primary = 1 and hubloc_hash = '%s' and hubloc_url != '%s' ",
					dbesc($channel['channel_hash']),
					dbesc(z_root())
				);
			}
		}

		logger('import step 5');


		// import xchans and contact photos

		if(array_key_exists('channel',$data) && $seize) {

			// replace any existing xchan we may have on this site if we're seizing control

			$r = q("delete from xchan where xchan_hash = '%s'",
				dbesc($channel['channel_hash'])
			);

			$r = xchan_store_lowlevel(
				[
					'xchan_hash'           => $channel['channel_hash'],
					'xchan_guid'           => $channel['channel_guid'],
					'xchan_guid_sig'       => $channel['channel_guid_sig'],
					'xchan_pubkey'         => $channel['channel_pubkey'],
					'xchan_photo_l'        => z_root() . "/photo/profile/l/" . $channel['channel_id'],
					'xchan_photo_m'        => z_root() . "/photo/profile/m/" . $channel['channel_id'],
					'xchan_photo_s'        => z_root() . "/photo/profile/s/" . $channel['channel_id'],
					'xchan_addr'           => channel_reddress($channel),
					'xchan_url'            => z_root() . '/channel/' . $channel['channel_address'],
					'xchan_connurl'        => z_root() . '/poco/' . $channel['channel_address'],
					'xchan_follow'         => z_root() . '/follow?f=&url=%s',
					'xchan_name'           => $channel['channel_name'],
					'xchan_network'        => 'zot',
					'xchan_photo_date'     => datetime_convert(),
					'xchan_name_date'      => datetime_convert()
				]
			);
		}

		logger('import step 6');

		// import xchans
		$xchans = $data['xchan'];
		if($xchans) {
			foreach($xchans as $xchan) {

				$hash = make_xchan_hash($xchan['xchan_guid'],$xchan['xchan_guid_sig']);
				if($xchan['xchan_network'] === 'zot' && $hash !== $xchan['xchan_hash']) {
					logger('forged xchan: ' . print_r($xchan,true));
					continue;
				}

				if(! array_key_exists('xchan_hidden',$xchan)) {
					$xchan['xchan_hidden']       = (($xchan['xchan_flags'] & 0x0001) ? 1 : 0);
					$xchan['xchan_orphan']       = (($xchan['xchan_flags'] & 0x0002) ? 1 : 0);
					$xchan['xchan_censored']     = (($xchan['xchan_flags'] & 0x0004) ? 1 : 0);
					$xchan['xchan_selfcensored'] = (($xchan['xchan_flags'] & 0x0008) ? 1 : 0);
					$xchan['xchan_system']       = (($xchan['xchan_flags'] & 0x0010) ? 1 : 0);
					$xchan['xchan_pubforum']     = (($xchan['xchan_flags'] & 0x0020) ? 1 : 0);
					$xchan['xchan_deleted']      = (($xchan['xchan_flags'] & 0x1000) ? 1 : 0);
				}

				$r = q("select xchan_hash from xchan where xchan_hash = '%s' limit 1",
					dbesc($xchan['xchan_hash'])
				);
				if($r)
					continue;

				create_table_from_array('xchan',$xchan);

				require_once('include/photo/photo_driver.php');

				if($xchan['xchan_hash'] === $channel['channel_hash']) {
					$r = q("update xchan set xchan_photo_l = '%s', xchan_photo_m = '%s', xchan_photo_s = '%s' where xchan_hash = '%s'",
						dbesc(z_root() . '/photo/profile/l/' . $channel['channel_id']),
						dbesc(z_root() . '/photo/profile/m/' . $channel['channel_id']),
						dbesc(z_root() . '/photo/profile/s/' . $channel['channel_id']),
						dbesc($xchan['xchan_hash'])
					);
				}
				else {
					$photos = import_xchan_photo($xchan['xchan_photo_l'],$xchan['xchan_hash']);
					if($photos[4])
						$photodate = NULL_DATE;
					else
						$photodate = $xchan['xchan_photo_date'];

					$r = q("update xchan set xchan_photo_l = '%s', xchan_photo_m = '%s', xchan_photo_s = '%s', xchan_photo_mimetype = '%s', xchan_photo_date = '%s' where xchan_hash = '%s'",
						dbesc($photos[0]),
						dbesc($photos[1]),
						dbesc($photos[2]),
						dbesc($photos[3]),
						dbesc($photodate),
						dbesc($xchan['xchan_hash'])
					);
				}
			}

			logger('import step 7');
		}

		$friends = 0;
		$feeds = 0;

		// import contacts
		$abooks = $data['abook'];
		if($abooks) {
			foreach($abooks as $abook) {

				$abook_copy = $abook;

				$abconfig = null;
				if(array_key_exists('abconfig',$abook) && is_array($abook['abconfig']) && count($abook['abconfig']))
					$abconfig = $abook['abconfig'];

				unset($abook['abook_id']);
				unset($abook['abook_rating']);
				unset($abook['abook_rating_text']);
				unset($abook['abconfig']);
				unset($abook['abook_their_perms']);
				unset($abook['abook_my_perms']);
				unset($abook['abook_not_here']);

				$abook['abook_account'] = $account_id;
				$abook['abook_channel'] = $channel['channel_id'];
				if(! array_key_exists('abook_blocked',$abook)) {
					$abook['abook_blocked']     = (($abook['abook_flags'] & 0x0001 ) ? 1 : 0);
					$abook['abook_ignored']     = (($abook['abook_flags'] & 0x0002 ) ? 1 : 0);
					$abook['abook_hidden']      = (($abook['abook_flags'] & 0x0004 ) ? 1 : 0);
					$abook['abook_archived']    = (($abook['abook_flags'] & 0x0008 ) ? 1 : 0);
					$abook['abook_pending']     = (($abook['abook_flags'] & 0x0010 ) ? 1 : 0);
					$abook['abook_unconnected'] = (($abook['abook_flags'] & 0x0020 ) ? 1 : 0);
					$abook['abook_self']        = (($abook['abook_flags'] & 0x0080 ) ? 1 : 0);
					$abook['abook_feed']        = (($abook['abook_flags'] & 0x0100 ) ? 1 : 0);
				}

				if(array_key_exists('abook_instance',$abook) && $abook['abook_instance'] && strpos($abook['abook_instance'],z_root()) === false) {
					$abook['abook_not_here'] = 1;
				} 

				if($abook['abook_self']) {
					$role = get_pconfig($channel['channel_id'],'system','permissions_role');
					if(($role === 'forum') || ($abook['abook_my_perms'] & PERMS_W_TAGWALL)) {
						q("update xchan set xchan_pubforum = 1 where xchan_hash = '%s' ",
							dbesc($abook['abook_xchan'])
						);
					}
				}
				else {
					if($max_friends !== false && $friends > $max_friends)
						continue;
					if($max_feeds !== false && intval($abook['abook_feed']) && ($feeds > $max_feeds))
						continue;
				}

				abook_store_lowlevel($abook);

				$friends ++;
				if(intval($abook['abook_feed']))
					$feeds ++;

				translate_abook_perms_inbound($channel,$abook_copy);

				if($abconfig) {
					/// @FIXME does not handle sync of del_abconfig
					foreach($abconfig as $abc) {
						set_abconfig($channel['channel_id'],$abc['xchan'],$abc['cat'],$abc['k'],$abc['v']);
					}
				}
			}

			logger('import step 8');
		}

		// import groups
		$groups = $data['group'];
		if($groups) {
			$saved = array();
			foreach($groups as $group) {
				$saved[$group['hash']] = array('old' => $group['id']);
				if(array_key_exists('name', $group)) {
					$group['gname'] = $group['name'];
					unset($group['name']);
				}
				unset($group['id']);
				$group['uid'] = $channel['channel_id'];

				create_table_from_array('groups', $group);
			}
			$r = q("select * from groups where uid = %d",
				intval($channel['channel_id'])
			);
			if($r) {
				foreach($r as $rr) {
					$saved[$rr['hash']]['new'] = $rr['id'];
				}
			}
		}

		// import group members
		$group_members = $data['group_member'];
		if($group_members) {
			foreach($group_members as $group_member) {
				unset($group_member['id']);
				$group_member['uid'] = $channel['channel_id'];
				foreach($saved as $x) {
					if($x['old'] == $group_member['gid'])
						$group_member['gid'] = $x['new'];
				}
				create_table_from_array('group_member', $group_member);
			}
		}

		logger('import step 9');

		if(is_array($data['obj']))
			import_objs($channel,$data['obj']);

		if(is_array($data['likes']))
			import_likes($channel,$data['likes']);

		if(is_array($data['app']))
			import_apps($channel,$data['app']);

		if(is_array($data['chatroom']))
			import_chatrooms($channel,$data['chatroom']);

		if(is_array($data['conv']))
			import_conv($channel,$data['conv']);

		if(is_array($data['mail']))
			import_mail($channel,$data['mail']);

		if(is_array($data['event']))
			import_events($channel,$data['event']);

		if(is_array($data['event_item']))
			import_items($channel,$data['event_item'],false,$relocate);

		if(is_array($data['menu']))
			import_menus($channel,$data['menu']);

		if(is_array($data['wiki']))
			import_items($channel,$data['wiki'],false,$relocate);

		if(is_array($data['webpages']))
			import_items($channel,$data['webpages'],false,$relocate);

		$addon = array('channel' => $channel,'data' => $data);
		call_hooks('import_channel',$addon);

		$saved_notification_flags = notifications_off($channel['channel_id']);

		if($import_posts && array_key_exists('item',$data) && $data['item'])
			import_items($channel,$data['item'],false,$relocate);

		notifications_on($channel['channel_id'],$saved_notification_flags);

		if(array_key_exists('item_id',$data) && $data['item_id'])
			import_item_ids($channel,$data['item_id']);

		// send out refresh requests
		// notify old server that it may no longer be primary.

		\Zotlabs\Daemon\Master::Summon(array('Notifier','location',$channel['channel_id']));

		// This will indirectly perform a refresh_all *and* update the directory

		\Zotlabs\Daemon\Master::Summon(array('Directory', $channel['channel_id']));


		notice( t('Import completed.') . EOL);

		change_channel($channel['channel_id']);

		goaway(z_root() . '/network' );
	}

	/**
	 * @brief Handle POST action on channel import page.
	 */
	function post() {
		$account_id = get_account_id();
		if(! $account_id)
			return;

		check_form_security_token_redirectOnErr('/import', 'channel_import');

		$this->import_account($account_id);
	}

	/**
	 * @brief Generate channel import page.
	 *
	 * @return string with parsed HTML.
	 */
	function get() {

		if(! get_account_id()) {
			notice( t('You must be logged in to use this feature.') . EOL);
			return '';
		}

		$o = replace_macros(get_markup_template('channel_import.tpl'),array(
			'$title' => t('Import Channel'),
			'$desc' => t('Use this form to import an existing channel from a different server/hub. You may retrieve the channel identity from the old server/hub via the network or provide an export file.'),
			'$label_filename' => t('File to Upload'),
			'$choice' => t('Or provide the old server/hub details'),
			'$label_old_address' => t('Your old identity address (xyz@example.com)'),
			'$label_old_email' => t('Your old login email address'),
			'$label_old_pass' => t('Your old login password'),
			'$common' => t('For either option, please choose whether to make this hub your new primary address, or whether your old location should continue this role. You will be able to post from either location, but only one can be marked as the primary location for files, photos, and media.'),
			'$label_import_primary' => t('Make this hub my primary location'),
			'$label_import_moving' => t('Move this channel (disable all previous locations)'),
			'$label_import_posts' => t('Import a few months of posts if possible (limited by available memory'),
			'$pleasewait' => t('This process may take several minutes to complete. Please submit the form only once and leave this page open until finished.'),
			'$email' => '',
			'$pass' => '',
			'$form_security_token' => get_form_security_token('channel_import'),
			'$submit' => t('Submit')
		));

		return $o;
	}

}
