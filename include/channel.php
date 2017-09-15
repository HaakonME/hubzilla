<?php
/**
 * @file include/channel.php
 */

require_once('include/zot.php');
require_once('include/crypto.php');
require_once('include/menu.php');
require_once('include/perm_upgrade.php');
require_once('include/photo/photo_driver.php');

/**
 * @brief Called when creating a new channel.
 *
 * Checks the account's service class and number of current channels to determine
 * whether creating a new channel is within the current service class constraints.
 *
 * @param int $account_id
 *     Account_id used for this request
 *
 * @returns associative array with:
 *  * \e boolean \b success boolean true if creating a new channel is allowed for this account
 *  * \e string \b message (optional) if success is false, optional error text
 *  * \e int \b total_identities
 */
function identity_check_service_class($account_id) {
	$ret = array('success' => false, 'message' => '');

	$r = q("select count(channel_id) as total from channel where channel_account_id = %d and channel_removed = 0 ",
		intval($account_id)
	);
	if(! ($r && count($r))) {
		$ret['total_identities'] = 0;
		$ret['message'] = t('Unable to obtain identity information from database');
		return $ret;
	}

	$ret['total_identities'] = intval($r[0]['total']);

	if (! account_service_class_allows($account_id, 'total_identities', $r[0]['total'])) {
		$ret['message'] .= upgrade_message();
		return $ret;
	}

	$ret['success'] = true;

	return $ret;
}


/**
 * @brief Determine if the channel name is allowed when creating a new channel.
 *
 * This action is pluggable.
 * We're currently only checking for an empty name or one that exceeds our
 * storage limit (191 chars). 191 chars is probably going to create a mess on
 * some pages.
 * Plugins can set additional policies such as full name requirements, character
 * sets, multi-byte length, etc.
 *
 * @hooks validate_channelname
 *   * \e array \b name
 * @param string $name
 * @returns nil return if name is valid, or string describing the error state.
 */
function validate_channelname($name) {

	if (! $name)
		return t('Empty name');

	if (mb_strlen($name) > 191)
		return t('Name too long');

	$arr = ['name' => $name];
	call_hooks('validate_channelname', $arr);

	if (x($arr, 'message'))
		return $arr['message'];
}


/**
 * @brief Create a system channel - which has no account attached.
 *
 */
function create_sys_channel() {
	if (get_sys_channel())
		return;

	// Ensure that there is a host keypair.

	if ((! get_config('system', 'pubkey')) && (! get_config('system', 'prvkey'))) {
		require_once('include/crypto.php');
		$hostkey = new_keypair(4096);
		set_config('system', 'pubkey', $hostkey['pubkey']);
		set_config('system', 'prvkey', $hostkey['prvkey']);
	}

	create_identity(array(
		'account_id' => 'xxx',  // This will create an identity with an (integer) account_id of 0, but account_id is required
		'nickname' => 'sys',
		'name' => 'System',
		'pageflags' => 0,
		'publish' => 0,
		'system' => 1
	));
}


/**
 * @brief Returns the sys channel.
 *
 * @return array|boolean
 */
function get_sys_channel() {
	$r = q("select * from channel left join xchan on channel_hash = xchan_hash where channel_system = 1 limit 1");

	if ($r)
		return $r[0];

	return false;
}


/**
 * @brief Checks if $channel_id is sys channel.
 *
 * @param int $channel_id
 * @return boolean
 */
function is_sys_channel($channel_id) {
	$r = q("select channel_system from channel where channel_id = %d and channel_system = 1 limit 1",
		intval($channel_id)
	);

	if($r)
		return true;

	return false;
}


/**
 * @brief Return the total number of channels on this site.
 *
 * No filtering is performed except to check PAGE_REMOVED.
 *
 * @returns int|booleean
 *   on error returns boolean false
 */
function channel_total() {
	$r = q("select channel_id from channel where channel_removed = 0");

	if (is_array($r))
		return count($r);

	return false;
}


/**
 * @brief Create a new channel.
 *
 * Also creates the related xchan, hubloc, profile, and "self" abook records,
 * and an empty "Friends" group/collection for the new channel.
 *
 * @param array $arr associative array with:
 *  * \e string \b name full name of channel
 *  * \e string \b nickname "email/url-compliant" nickname
 *  * \e int \b account_id to attach with this channel
 *  * [other identity fields as desired]
 *
 * @returns array
 *     'success' => boolean true or false
 *     'message' => optional error text if success is false
 *     'channel' => if successful the created channel array
 */
function create_identity($arr) {

	$ret = array('success' => false);

	if(! $arr['account_id']) {
	$ret['message'] = t('No account identifier');
		return $ret;
	}
	$ret = identity_check_service_class($arr['account_id']);
	if (!$ret['success']) {
		return $ret;
	}
	// save this for auto_friending
	$total_identities = $ret['total_identities'];

	$nick = mb_strtolower(trim($arr['nickname']));
	if(! $nick) {
		$ret['message'] = t('Nickname is required.');
		return $ret;
	}

	$name = escape_tags($arr['name']);
	$pageflags = ((x($arr,'pageflags')) ? intval($arr['pageflags']) : PAGE_NORMAL);
	$system = ((x($arr,'system')) ? intval($arr['system']) : 0);
	$name_error = validate_channelname($arr['name']);
	if($name_error) {
		$ret['message'] = $name_error;
		return $ret;
	}

	if($nick === 'sys' && (! $system)) {
		$ret['message'] = t('Reserved nickname. Please choose another.');
		return $ret;
	}

	if(check_webbie(array($nick)) !== $nick) {
		$ret['message'] = t('Nickname has unsupported characters or is already being used on this site.');
		return $ret;
	}

	$guid = zot_new_uid($nick);
	$key = new_keypair(4096);

	$sig = base64url_encode(rsa_sign($guid,$key['prvkey']));
	$hash = make_xchan_hash($guid,$sig);

	// Force a few things on the short term until we can provide a theme or app with choice

	$publish = 1;

	if(array_key_exists('publish', $arr))
		$publish = intval($arr['publish']);

	$role_permissions = null;

	if(array_key_exists('permissions_role',$arr) && $arr['permissions_role']) {
		$role_permissions = \Zotlabs\Access\PermissionRoles::role_perms($arr['permissions_role']);
	}

	if($role_permissions && array_key_exists('directory_publish',$role_permissions))
		$publish = intval($role_permissions['directory_publish']);

	$primary = true;

	if(array_key_exists('primary', $arr))
		$primary = intval($arr['primary']);

	$expire = 0;

	$r = channel_store_lowlevel(
		[
			'channel_account_id'  => intval($arr['account_id']),
			'channel_primary'     => intval($primary),
			'channel_name'        => $name,
			'channel_address'     => $nick,
			'channel_guid'        => $guid,
			'channel_guid_sig'    => $sig,
			'channel_hash'        => $hash,
			'channel_prvkey'      => $key['prvkey'],
			'channel_pubkey'      => $key['pubkey'],
			'channel_pageflags'   => intval($pageflags),
			'channel_system'      => intval($system),
			'channel_expire_days' => intval($expire),
			'channel_timezone'    => App::$timezone

		]
	);

	$r = q("select * from channel where channel_account_id = %d
		and channel_guid = '%s' limit 1",
		intval($arr['account_id']),
		dbesc($guid)
	);

	if(! $r) {
		$ret['message'] = t('Unable to retrieve created identity');
		return $ret;
	}

	$a = q("select * from account where account_id = %d",
		intval($arr['account_id'])
	);

	$z = [ 'account' => $a[0], 'channel' => $r[0], 'photo_url' => '' ];
	call_hooks('create_channel_photo',$z);
 
	if($z['photo_url']) {
		import_channel_photo_from_url($z['photo_url'],$arr['account_id'],$r[0]['channel_id']);
	}

	if($role_permissions && array_key_exists('limits',$role_permissions))
		$perm_limits = $role_permissions['limits'];
	else
		$perm_limits = site_default_perms();

	foreach($perm_limits as $p => $v)
		\Zotlabs\Access\PermissionLimits::Set($r[0]['channel_id'],$p,$v);

	if($role_permissions && array_key_exists('perms_auto',$role_permissions))
		set_pconfig($r[0]['channel_id'],'system','autoperms',intval($role_permissions['perms_auto']));

	$ret['channel'] = $r[0];

	if(intval($arr['account_id']))
		set_default_login_identity($arr['account_id'],$ret['channel']['channel_id'],false);

	// Create a verified hub location pointing to this site.

	$r = hubloc_store_lowlevel(
		[
			'hubloc_guid'     => $guid,
			'hubloc_guid_sig' => $sig,
			'hubloc_hash'     => $hash,
			'hubloc_addr'     => channel_reddress($ret['channel']),
			'hubloc_primary'  => $primary,
			'hubloc_url'      => z_root(),
			'hubloc_url_sig'  => base64url_encode(rsa_sign(z_root(),$ret['channel']['channel_prvkey'])),
			'hubloc_host'     => App::get_hostname(),
			'hubloc_callback' => z_root() . '/post',
			'hubloc_sitekey'  => get_config('system','pubkey'),
			'hubloc_network'  => 'zot',
			'hubloc_updated'  => datetime_convert()
		]
	);
	if(! $r)
		logger('create_identity: Unable to store hub location');

	$newuid = $ret['channel']['channel_id'];

	$r = xchan_store_lowlevel(
		[
			'xchan_hash'       => $hash,
			'xchan_guid'       => $guid,
			'xchan_guid_sig'   => $sig,
			'xchan_pubkey'     => $key['pubkey'],
			'xchan_photo_l'    => z_root() . "/photo/profile/l/{$newuid}",
			'xchan_photo_m'    => z_root() . "/photo/profile/m/{$newuid}",
			'xchan_photo_s'    => z_root() . "/photo/profile/s/{$newuid}",
			'xchan_addr'       => channel_reddress($ret['channel']),
			'xchan_url'        => z_root() . '/channel/' . $ret['channel']['channel_address'],
			'xchan_follow'     => z_root() . '/follow?f=&url=%s',
			'xchan_connurl'    => z_root() . '/poco/' . $ret['channel']['channel_address'],
			'xchan_name'       => $ret['channel']['channel_name'],
			'xchan_network'    => 'zot',
			'xchan_photo_date' => datetime_convert(),
			'xchan_name_date'  => datetime_convert(),
			'xchan_system'     => $system
		]
	);

	// Not checking return value.
	// It's ok for this to fail if it's an imported channel, and therefore the hash is a duplicate

	$r = profile_store_lowlevel(
		[
			'aid'          => intval($ret['channel']['channel_account_id']),
			'uid'          => intval($newuid),
			'profile_guid' => random_string(),
			'profile_name' => t('Default Profile'),
			'is_default'   => 1,
			'publish'      => $publish,
			'fullname'     => $ret['channel']['channel_name'],
			'photo'        => z_root() . "/photo/profile/l/{$newuid}",
			'thumb'        => z_root() . "/photo/profile/m/{$newuid}"
		]
	);

	if($role_permissions) {
		$myperms = ((array_key_exists('perms_connect',$role_permissions)) ? $role_permissions['perms_connect'] : array());
	}
	else {
		$x = \Zotlabs\Access\PermissionRoles::role_perms('social');
		$myperms = $x['perms_connect'];
	}

	$r = abook_store_lowlevel(
		[
			'abook_account'   => intval($ret['channel']['channel_account_id']),
			'abook_channel'   => intval($newuid),
			'abook_xchan'     => $hash,
			'abook_closeness' => 0,
			'abook_created'   => datetime_convert(),
			'abook_updated'   => datetime_convert(),
			'abook_self'      => 1
		]
	);

	$x = \Zotlabs\Access\Permissions::FilledPerms($myperms);
	foreach($x as $k => $v) {
		set_abconfig($newuid,$hash,'my_perms',$k,$v);
	}

	if(intval($ret['channel']['channel_account_id'])) {

		// Save our permissions role so we can perhaps call it up and modify it later.

		if($role_permissions) {
			set_pconfig($newuid,'system','permissions_role',$arr['permissions_role']);
			if(array_key_exists('online',$role_permissions))
				set_pconfig($newuid,'system','hide_presence',1-intval($role_permissions['online']));
			if(array_key_exists('perms_auto',$role_permissions)) {
				$autoperms = intval($role_permissions['perms_auto']);
				set_pconfig($newuid,'system','autoperms',$autoperms);
				if($autoperms) {
					$x = \Zotlabs\Access\Permissions::FilledPerms($role_permissions['perms_connect']);
					foreach($x as $k => $v) {
						set_pconfig($newuid,'autoperms',$k,$v);
					}
				}
				// as this is a new channel, this shouldn't do anything and probaby is not needed
				else {
					$r = q("delete from pconfig where uid = %d and cat = 'autoperms'",
						intval($newuid)
					);
				}
			}
		}

		// Create a group with yourself as a member. This allows somebody to use it
		// right away as a default group for new contacts.

		require_once('include/group.php');
		group_add($newuid, t('Friends'));
		group_add_member($newuid,t('Friends'),$ret['channel']['channel_hash']);

		// if our role_permissions indicate that we're using a default collection ACL, add it.

		if(is_array($role_permissions) && $role_permissions['default_collection']) {
			$r = q("select hash from groups where uid = %d and gname = '%s' limit 1",
				intval($newuid),
				dbesc( t('Friends') )
			);
			if($r) {
				q("update channel set channel_default_group = '%s', channel_allow_gid = '%s' where channel_id = %d",
					dbesc($r[0]['hash']),
					dbesc('<' . $r[0]['hash'] . '>'),
					intval($newuid)
				);
			}
		}

		if(! $system) {
			set_pconfig($ret['channel']['channel_id'],'system','photo_path', '%Y-%m');
			set_pconfig($ret['channel']['channel_id'],'system','attach_path','%Y-%m');
		}

		// auto-follow any of the hub's pre-configured channel choices.
		// Only do this if it's the first channel for this account;
		// otherwise it could get annoying. Don't make this list too big
		// or it will impact registration time.

		$accts = get_config('system','auto_follow');
		if(($accts) && (! $total_identities)) {
			require_once('include/follow.php');
			if(! is_array($accts))
				$accts = array($accts);
			foreach($accts as $acct) {
				if(trim($acct))
					new_contact($newuid,trim($acct),$ret['channel'],false);
			}
		}

		call_hooks('create_identity', $newuid);

		Zotlabs\Daemon\Master::Summon(array('Directory', $ret['channel']['channel_id']));
	}

	$ret['success'] = true;
	return $ret;
}


function change_channel_keys($channel) {

	$ret = array('success' => false);

	$stored = [];

	$key = new_keypair(4096);

	$sig = base64url_encode(rsa_sign($channel['channel_guid'],$key['prvkey']));
	$hash = make_xchan_hash($channel['channel_guid'],$sig);

	$stored['old_guid']     = $channel['channel_guid'];
	$stored['old_guid_sig'] = $channel['channel_guid_sig'];
	$stored['old_key']      = $channel['channel_pubkey'];
	$stored['old_hash']     = $channel['channel_hash'];

	$stored['new_key']      = $key['pubkey'];
	$stored['new_sig']      = base64url_encode(rsa_sign($key['pubkey'],$channel['channel_prvkey']));

	// Save this info for the notifier to collect

	set_pconfig($channel['channel_id'],'system','keychange',$stored);

	$r = q("update channel set channel_prvkey = '%s', channel_pubkey = '%s', channel_guid_sig = '%s', channel_hash = '%s' where channel_id = %d",
		dbesc($key['prvkey']),
		dbesc($key['pubkey']),
		dbesc($sig),
		dbesc($hash),
		intval($channel['channel_id'])
	);
	if(! $r) {
		return $ret;
 	}

	$r = q("select * from channel where channel_id = %d",
		intval($channel['channel_id'])
	);

	if(! $r) {
		$ret['message'] = t('Unable to retrieve modified identity');
		return $ret;
	}

	$modified = $r[0];

	$h = q("select * from hubloc where hubloc_hash = '%s' and hubloc_url = '%s' ",
		dbesc($stored['old_hash']),
		dbesc(z_root())
	);

	if($h) {
		foreach($h as $hv) {
			$hv['hubloc_guid_sig'] = $sig;
			$hv['hubloc_hash']     = $hash;
			$hv['hubloc_url_sig']  = base64url_encode(rsa_sign(z_root(),$modifed['channel_prvkey']));
			hubloc_store_lowlevel($hv);
		}
	}

	$x = q("select * from xchan where xchan_hash = '%s' ",
		dbesc($stored['old_hash'])
	);

	$check = q("select * from xchan where xchan_hash = '%s'",
		dbesc($hash)
	);

	if(($x) && (! $check)) {
		$oldxchan = $x[0];
		foreach($x as $xv) {
			$xv['xchan_guid_sig']  = $sig;
			$xv['xchan_hash']      = $hash;
			$xv['xchan_pubkey']    = $key['pubkey'];
			xchan_store_lowlevel($xv);
			$newxchan = $xv;
		}
	}

	build_sync_packet($channel['channel_id'], [ 'keychange' => $stored ]);

	$a = q("select * from abook where abook_xchan = '%s' and abook_self = 1",
		dbesc($stored['old_hash'])
	);

	if($a) {
		q("update abook set abook_xchan = '%s' where abook_id = %d",
			dbesc($hash),
			intval($a[0]['abook_id'])
		);
	}

	xchan_change_key($oldxchan,$newxchan,$stored);

	Zotlabs\Daemon\Master::Summon(array('Notifier', 'keychange', $channel['channel_id']));

	$ret['success'] = true;
	return $ret;
}

function channel_change_address($channel,$new_address) {

	$ret = array('success' => false);

	$old_address = $channel['channel_address'];

	if($new_address === 'sys') {
        $ret['message'] = t('Reserved nickname. Please choose another.');
        return $ret;
    }

    if(check_webbie(array($new_address)) !== $new_address) {
        $ret['message'] = t('Nickname has unsupported characters or is already being used on this site.');
        return $ret;
    }

	$r = q("update channel set channel_address = '%s' where channel_id = %d",
		dbesc($new_address),
		intval($channel['channel_id'])
	);
	if(! $r) {
		return $ret;
 	}

	$r = q("select * from channel where channel_id = %d",
		intval($channel['channel_id'])
	);

	if(! $r) {
		$ret['message'] = t('Unable to retrieve modified identity');
		return $ret;
	}

	$r = q("update xchan set xchan_addr = '%s' where xchan_hash = '%s'",
		dbesc($new_address . '@' . App::get_hostname()),
		dbesc($channel['channel_hash'])
	);

	$h = q("select * from hubloc where hubloc_hash = '%s' and hubloc_url = '%s' ",
		dbesc($channel['channel_hash']),
		dbesc(z_root())
	);

	if($h) {
		foreach($h as $hv) {
			if($hv['hubloc_primary']) {
				q("update hubloc set hubloc_primary = 0 where hubloc_id = %d",
					intval($hv['hubloc_id'])
				);
			}
			q("update hubloc set hubloc_deleted = 1 where hubloc_id = %d",
				intval($hv['hubloc_id'])
			);

			unset($hv['hubloc_id']);
			$hv['hubloc_addr'] = $new_address . '@' . App::get_hostname();
			hubloc_store_lowlevel($hv);
		}
	}

	// fix apps which were stored with the actual name rather than a macro

	$r = q("select * from app where app_channel = %d and app_system = 1",
		intval($channel['channel_id'])
	);
	if($r) {
		foreach($r as $rv) {
			$replace = preg_replace('/([\=\/])(' . $old_address . ')($|[\%\/])/ism','$1' . $new_address . '$3',$rv['app_url']);
			if($replace != $rv['app_url']) {
				q("update app set app_url = '%s' where id = %d",
					dbesc($replace),
					intval($rv['id'])
				);
			}
		}
	}		

	Zotlabs\Daemon\Master::Summon(array('Notifier', 'refresh_all', $channel['channel_id']));

	$ret['success'] = true;
	return $ret;
}






/**
 * @brief Set default channel to be used on login.
 *
 * @param int $account_id
 *       login account
 * @param int $channel_id
 *       channel id to set as default for this account
 * @param boolean $force
 *       if true, set this default unconditionally
 *       if $force is false only do this if there is no existing default
 */
function set_default_login_identity($account_id, $channel_id, $force = true) {
	$r = q("select account_default_channel from account where account_id = %d limit 1",
		intval($account_id)
	);
	if ($r) {
		if ((intval($r[0]['account_default_channel']) == 0) || ($force)) {
			$r = q("update account set account_default_channel = %d where account_id = %d",
				intval($channel_id),
				intval($account_id)
			);
		}
	}
}

/**
 * @brief Return an array with default list of sections to export.
 *
 * @hooks get_default_export_sections
 *   * \e array \b sections
 * @return array with default section names to export
 */
function get_default_export_sections() {
	$sections = [
			'channel',
			'connections',
			'config',
			'apps',
			'chatrooms',
			'events',
			'webpages',
			'mail',
			'wikis'
	];

	$cb = [ 'sections' => $sections ];
	call_hooks('get_default_export_sections', $cb);

	return $cb['sections'];
}


/**
 * @brief Create an array representing the important channel information
 * which would be necessary to create a nomadic identity clone. This includes
 * most channel resources and connection information with the exception of content.
 *
 * @hooks identity_basic_export
 *   * \e int \b channel_id
 *   * \e array \b sections
 *   * \e array \b data
 * @param int $channel_id
 *     Channel_id to export
 * @param array $sections (optional)
 *     Which sections to include in the export, default see get_default_export_sections()
 * @returns array
 *     See function for details
 */
function identity_basic_export($channel_id, $sections = null) {

	/*
	 * basic channel export
	 */

	if(! $sections) {
		$sections = get_default_export_sections();
	}

	$ret = [];

	// use constants here as otherwise we will have no idea if we can import from a site
	// with a non-standard platform and version.

	$ret['compatibility'] = [
		'project' => PLATFORM_NAME,
		'version' => STD_VERSION,
		'database' => DB_UPDATE_VERSION,
		'server_role' => Zotlabs\Lib\System::get_server_role()
	];

	/*
	 * Process channel information regardless of it is one of the sections desired
	 * because we need the channel relocation information in all export files/streams.
	 */

	$r = q("select * from channel where channel_id = %d limit 1",
		intval($channel_id)
	);
	if($r) {
		translate_channel_perms_outbound($r[0]);
		$ret['relocate'] = [ 'channel_address' => $r[0]['channel_address'], 'url' => z_root()];
		if(in_array('channel',$sections)) {
			$ret['channel'] = $r[0];
			unset($ret['channel']['channel_password']);
			unset($ret['channel']['channel_salt']);
		}
	}

	if(in_array('channel',$sections)) {
		$r = q("select * from profile where uid = %d",
			intval($channel_id)
		);
		if($r)
			$ret['profile'] = $r;

		$r = q("select mimetype, content, os_storage from photo
			where imgscale = 4 and photo_usage = %d and uid = %d limit 1",
			intval(PHOTO_PROFILE),
			intval($channel_id)
		);

		if($r) {
			$ret['photo'] = [
				'type' => $r[0]['mimetype'],
				'data' => (($r[0]['os_storage'])
					? base64url_encode(file_get_contents($r[0]['content'])) : base64url_encode($r[0]['content']))
			];
		}
	}

	if(in_array('connections',$sections)) {
		$xchans = array();
		$r = q("select * from abook where abook_channel = %d ",
			intval($channel_id)
		);
		if($r) {
			$ret['abook'] = $r;

			for($x = 0; $x < count($ret['abook']); $x ++) {
				$xchans[] = $ret['abook'][$x]['abook_xchan'];
				$abconfig = load_abconfig($channel_id,$ret['abook'][$x]['abook_xchan']);
				if($abconfig)
					$ret['abook'][$x]['abconfig'] = $abconfig;
				translate_abook_perms_outbound($ret['abook'][$x]);
			}
			stringify_array_elms($xchans);
		}

		if($xchans) {
			$r = q("select * from xchan where xchan_hash in ( " . implode(',',$xchans) . " ) ");
			if($r)
				$ret['xchan'] = $r;

			$r = q("select * from hubloc where hubloc_hash in ( " . implode(',',$xchans) . " ) ");
			if($r)
				$ret['hubloc'] = $r;
		}

		$r = q("select * from groups where uid = %d ",
			intval($channel_id)
		);

		if($r)
			$ret['group'] = $r;

		$r = q("select * from group_member where uid = %d ",
			intval($channel_id)
		);
		if($r)
			$ret['group_member'] = $r;
	}

	if(in_array('config',$sections)) {
		$r = q("select * from pconfig where uid = %d",
			intval($channel_id)
		);
		if($r)
			$ret['config'] = $r;

		// All other term types will be included in items, if requested.

		$r = q("select * from term where ttype in (%d,%d) and uid = %d",
			intval(TERM_SAVEDSEARCH),
			intval(TERM_THING),
			intval($channel_id)
		);
		if($r)
			$ret['term'] = $r;

		// add psuedo-column obj_baseurl to aid in relocations

		$r = q("select obj.*, '%s' as obj_baseurl from obj where obj_channel = %d",
			dbesc(z_root()),
			intval($channel_id)
		);

		if($r)
			$ret['obj'] = $r;

		$r = q("select * from likes where channel_id = %d",
			intval($channel_id)
		);

		if($r)
			$ret['likes'] = $r;
	}

	if(in_array('apps',$sections)) {
		$r = q("select * from app where app_channel = %d and app_system = 0",
			intval($channel_id)
		);
		if($r) {
			for($x = 0; $x < count($r); $x ++) {
				$r[$x]['term'] = q("select * from term where otype = %d and oid = %d",
					intval(TERM_OBJ_APP),
					intval($r[$x]['id'])
				);
			}
			$ret['app'] = $r;
		}
	}

	if(in_array('chatrooms',$sections)) {
		$r = q("select * from chatroom where cr_uid = %d",
			intval($channel_id)
		);
		if($r)
			$ret['chatroom'] = $r;
	}

	if(in_array('events',$sections)) {
		$r = q("select * from event where uid = %d",
			intval($channel_id)
		);
		if($r)
			$ret['event'] = $r;

		$r = q("select * from item where resource_type = 'event' and uid = %d",
			intval($channel_id)
		);
		if($r) {
			$ret['event_item'] = array();
			xchan_query($r);
			$r = fetch_post_tags($r,true);
			foreach($r as $rr)
				$ret['event_item'][] = encode_item($rr,true);
		}
	}

	if(in_array('webpages',$sections)) {
		$x = menu_list($channel_id);
		if($x) {
			$ret['menu'] = array();
			for($y = 0; $y < count($x); $y ++) {
				$m = menu_fetch($x[$y]['menu_name'],$channel_id,$ret['channel']['channel_hash']);
				if($m)
					$ret['menu'][] = menu_element($ret['channel'],$m);
			}
		}
		$r = q("select * from item where item_type in ( "
			. ITEM_TYPE_BLOCK . "," . ITEM_TYPE_PDL . "," . ITEM_TYPE_WEBPAGE . " ) and uid = %d",
			intval($channel_id)
		);
		if($r) {
			$ret['webpages'] = array();
			xchan_query($r);
			$r = fetch_post_tags($r,true);
			foreach($r as $rr)
				$ret['webpages'][] = encode_item($rr,true);
		}
	}

	if(in_array('mail',$sections)) {
		$r = q("select * from conv where uid = %d",
			intval($channel_id)
		);
		if($r) {
			for($x = 0; $x < count($r); $x ++) {
				$r[$x]['subject'] = base64url_decode(str_rot47($r[$x]['subject']));
			}
			$ret['conv'] = $r;
		}

		$r = q("select * from mail where channel_id = %d",
			intval($channel_id)
		);
		if($r) {
			$m = array();
			foreach($r as $rr) {
				xchan_mail_query($rr);
				$m[] = encode_mail($rr,true);
			}
			$ret['mail'] = $m;
		}
	}

	if(in_array('wikis',$sections)) {
		$r = q("select * from item where resource_type like 'nwiki%%' and uid = %d order by created",
			intval($channel_id)
		);
		if($r) {
			$ret['wiki'] = array();
			xchan_query($r);
			$r = fetch_post_tags($r,true);
			foreach($r as $rv) {
				$ret['wiki'][] = encode_item($rv,true);
			}
		}
	}

	if(in_array('items',$sections)) {
		/** @warning this may run into memory limits on smaller systems */

		/** export three months of posts. If you want to export and import all posts you have to start with
		 * the first year and export/import them in ascending order.
		 *
		 * Don't export linked resource items. we'll have to pull those out separately.
		 */

		$r = q("select * from item where item_wall = 1 and item_deleted = 0 and uid = %d
			and created > %s - INTERVAL %s and resource_type = '' order by created",
			intval($channel_id),
			db_utcnow(),
			db_quoteinterval('3 MONTH')
		);
		if($r) {
			$ret['item'] = array();
			xchan_query($r);
			$r = fetch_post_tags($r,true);
			foreach($r as $rr)
				$ret['item'][] = encode_item($rr,true);
		}
	}

	$addon = [ 'channel_id' => $channel_id, 'sections' => $sections, 'data' => $ret];
	call_hooks('identity_basic_export',$addon);
	$ret = $addon['data'];

	return $ret;
}


function identity_export_year($channel_id,$year,$month = 0) {

	if(! $year)
		return array();

	if($month && $month <= 12) {
		$target_month = sprintf('%02d',$month);
		$target_month_plus = sprintf('%02d',$month+1);
	}
	else
		$target_month = '01';

	$ret = array();

	$ch = channelx_by_n($channel_id);
	if($ch) {
		$ret['relocate'] = [ 'channel_address' => $ch['channel_address'], 'url' => z_root()];
	}
	$mindate = datetime_convert('UTC','UTC',$year . '-' . $target_month . '-01 00:00:00');
	if($month && $month < 12)
		$maxdate = datetime_convert('UTC','UTC',$year . '-' . $target_month_plus . '-01 00:00:00');
	else
		$maxdate = datetime_convert('UTC','UTC',$year+1 . '-01-01 00:00:00');

	$r = q("select * from item where ( item_wall = 1 or item_type != %d ) and item_deleted = 0 and uid = %d and created >= '%s' and created < '%s'  and resource_type = '' order by created",
		intval(ITEM_TYPE_POST),
		intval($channel_id),
		dbesc($mindate),
		dbesc($maxdate)
	);

	if($r) {
		$ret['item'] = array();
		xchan_query($r);
		$r = fetch_post_tags($r,true);
		foreach($r as $rr)
			$ret['item'][] = encode_item($rr,true);
	}

	return $ret;
}

/**
 * @brief Export items within an arbitrary date range.
 *
 * Date/time is in UTC.
 *
 * @param int $channel_id The channel ID
 * @param string $start
 * @param string $finish
 * @return array
 */
function channel_export_items($channel_id, $start, $finish) {

	if(! $start)
		return array();
	else
		$start = datetime_convert('UTC', 'UTC', $start);

	$finish = datetime_convert('UTC', 'UTC', (($finish) ? $finish : 'now'));
	if($finish < $start)
		return array();

	$ret = array();

	$ch = channelx_by_n($channel_id);
	if($ch) {
		$ret['relocate'] = [ 'channel_address' => $ch['channel_address'], 'url' => z_root()];
	}

	$r = q("select * from item where ( item_wall = 1 or item_type != %d ) and item_deleted = 0 and uid = %d and created >= '%s' and created <= '%s'  and resource_type = '' order by created",
		intval(ITEM_TYPE_POST),
		intval($channel_id),
		dbesc($start),
		dbesc($finish)
	);

	if($r) {
		$ret['item'] = array();
		xchan_query($r);
		$r = fetch_post_tags($r, true);
		foreach($r as $rr)
			$ret['item'][] = encode_item($rr, true);
	}

	return $ret;
}


/**
 * @brief Loads a profile into the App structure.
 *
 * The function requires a writeable copy of the main App structure, and the
 * nickname of a valid channel.
 *
 * Permissions of the current observer are checked. If a restricted profile is available
 * to the current observer, that will be loaded instead of the channel default profile.
 *
 * The channel owner can set $profile to a valid profile_guid to preview that profile.
 *
 * The channel default theme is also selected for use, unless over-riden elsewhere.
 *
 * @param string $nickname
 * @param string $profile
 */
function profile_load($nickname, $profile = '') {

//	logger('profile_load: ' . $nickname . (($profile) ? ' profile: ' . $profile : ''));

	$user = q("select channel_id from channel where channel_address = '%s' and channel_removed = 0  limit 1",
		dbesc($nickname)
	);

	if(! $user) {
		logger('profile error: ' . App::$query_string, LOGGER_DEBUG);
		notice( t('Requested channel is not available.') . EOL );
		App::$error = 404;
		return;
	}

	// get the current observer
	$observer = App::get_observer();

	$can_view_profile = true;

	// Can the observer see our profile?
	require_once('include/permissions.php');
	if(! perm_is_allowed($user[0]['channel_id'],$observer['xchan_hash'],'view_profile')) {
		$can_view_profile = false;
	}

	if(! $profile) {
		$r = q("SELECT abook_profile FROM abook WHERE abook_xchan = '%s' and abook_channel = '%d' limit 1",
			dbesc($observer['xchan_hash']),
			intval($user[0]['channel_id'])
		);
		if($r)
			$profile = $r[0]['abook_profile'];
	}
	$p = null;

	if($profile) {
		$p = q("SELECT profile.uid AS profile_uid, profile.*, channel.* FROM profile
				LEFT JOIN channel ON profile.uid = channel.channel_id
				WHERE channel.channel_address = '%s' AND profile.profile_guid = '%s' LIMIT 1",
				dbesc($nickname),
				dbesc($profile)
		);
	}

	if(! $p) {
		$p = q("SELECT profile.uid AS profile_uid, profile.*, channel.* FROM profile
			LEFT JOIN channel ON profile.uid = channel.channel_id
			WHERE channel.channel_address = '%s' and channel_removed = 0
			AND profile.is_default = 1 LIMIT 1",
			dbesc($nickname)
		);
	}

	if(! $p) {
		logger('profile error: ' . App::$query_string, LOGGER_DEBUG);
		notice( t('Requested profile is not available.') . EOL );
		App::$error = 404;
		return;
	}

	$q = q("select * from profext where hash = '%s' and channel_id = %d",
		dbesc($p[0]['profile_guid']),
		intval($p[0]['profile_uid'])
	);
	if($q) {
		$extra_fields = array();

		require_once('include/channel.php');
		$profile_fields_basic    = get_profile_fields_basic();
		$profile_fields_advanced = get_profile_fields_advanced();

		$advanced = ((feature_enabled(local_channel(),'advanced_profiles')) ? true : false);
		if($advanced)
			$fields = $profile_fields_advanced;
		else
			$fields = $profile_fields_basic;

		foreach($q as $qq) {
			foreach($fields as $k => $f) {
				if($k == $qq['k']) {
					$p[0][$k] = $qq['v'];
					$extra_fields[] = $k;
					break;
				}
			}
		}
	}

	$p[0]['extra_fields'] = $extra_fields;

	$z = q("select xchan_photo_date, xchan_addr from xchan where xchan_hash = '%s' limit 1",
		dbesc($p[0]['channel_hash'])
	);
	if($z) {
		$p[0]['picdate'] = $z[0]['xchan_photo_date'];
		$p[0]['reddress'] = str_replace('@','&#x40;',$z[0]['xchan_addr']);
	}

	// fetch user tags if this isn't the default profile

	if(! $p[0]['is_default']) {
		$x = q("select keywords from profile where uid = %d and is_default = 1 limit 1",
				intval($p[0]['profile_uid'])
		);
		if($x && $can_view_profile)
			$p[0]['keywords'] = $x[0]['keywords'];
	}

	if($p[0]['keywords']) {
		$keywords = str_replace(array('#',',',' ',',,'),array('',' ',',',','),$p[0]['keywords']);
		if(strlen($keywords) && $can_view_profile)
			App::$page['htmlhead'] .= '<meta name="keywords" content="' . htmlentities($keywords,ENT_COMPAT,'UTF-8') . '" />' . "\r\n" ;
	}

	App::$profile = $p[0];
	App::$profile_uid = $p[0]['profile_uid'];
	App::$page['title'] = App::$profile['channel_name'] . " - " . channel_reddress(App::$profile);

	App::$profile['permission_to_view'] = $can_view_profile;

	if($can_view_profile) {
		$online = get_online_status($nickname);
		App::$profile['online_status'] = $online['result'];
	}

	if(local_channel()) {
		App::$profile['channel_mobile_theme'] = get_pconfig(local_channel(),'system', 'mobile_theme');
		$_SESSION['mobile_theme'] = App::$profile['channel_mobile_theme'];
	}

	/*
	 * load/reload current theme info
	 */

//	$_SESSION['theme'] = $p[0]['channel_theme'];

}

function profile_edit_menu($uid) {

	$ret = array();

	$is_owner = (($uid == local_channel()) ? true : false);

	// show edit profile to profile owner
	if($is_owner) {
		$ret['menu'] = array(
			'chg_photo' => t('Change profile photo'),
			'entries' => array(),
		);

		$multi_profiles = feature_enabled(local_channel(), 'multi_profiles');
		if($multi_profiles) {
			$ret['multi'] = 1;
			$ret['edit'] = array(z_root(). '/profiles', t('Edit Profiles'), '', t('Edit'));
			$ret['menu']['cr_new'] = t('Create New Profile');
		}
		else {
			$ret['edit'] = array(z_root() . '/profiles/' . $uid, t('Edit Profile'), '', t('Edit'));
		}

		$r = q("SELECT * FROM profile WHERE uid = %d",
				local_channel()
		);

		if($r) {
			foreach($r as $rr) {
				if(!($multi_profiles || $rr['is_default']))
					 continue;
				$ret['menu']['entries'][] = array(
					'photo'                => $rr['thumb'],
					'id'                   => $rr['id'],
					'alt'                  => t('Profile Image'),
					'profile_name'         => $rr['profile_name'],
					'isdefault'            => $rr['is_default'],
					'visible_to_everybody' => t('Visible to everybody'),
					'edit_visibility'      => t('Edit visibility'),
				);
			}
		}
	}

	return $ret;
}

/**
 * @brief Formats a profile for display in the sidebar.
 *
 * It is very difficult to templatise the HTML completely
 * because of all the conditional logic.
 *
 * @param array $profile
 * @param int $block
 * @param boolean $show_connect
 * @param mixed $zcard
 *
 * @return HTML string suitable for sidebar inclusion
 * Exceptions: Returns empty string if passed $profile is wrong type or not populated
 */
function profile_sidebar($profile, $block = 0, $show_connect = true, $zcard = false) {

	$observer = App::get_observer();

	$o = '';
	$location = false;
	$pdesc = true;
	$reddress = true;

	if(! perm_is_allowed($profile['uid'],((is_array($observer)) ? $observer['xchan_hash'] : ''),'view_profile')) {
		$block = true;
	}

	if((! is_array($profile)) && (! count($profile)))
		return $o;

	head_set_icon($profile['thumb']);

	if(is_sys_channel($profile['uid']))
		$show_connect = false;

	$profile['picdate'] = urlencode($profile['picdate']);

	call_hooks('profile_sidebar_enter', $profile);

	if($show_connect) {

		// This will return an empty string if we're already connected.

		$connect_url = rconnect_url($profile['uid'],get_observer_hash());
		$connect = (($connect_url) ? t('Connect') : '');
		if($connect_url)
			$connect_url = sprintf($connect_url,urlencode(channel_reddress($profile)));

		// premium channel - over-ride

		if($profile['channel_pageflags'] & PAGE_PREMIUM)
			$connect_url = z_root() . '/connect/' . $profile['channel_address'];
	}

	if((x($profile,'address') == 1)
		|| (x($profile,'locality') == 1)
		|| (x($profile,'region') == 1)
		|| (x($profile,'postal_code') == 1)
		|| (x($profile,'country_name') == 1))
		$location = t('Location:');

	$profile['homepage'] = linkify($profile['homepage'],true);

	$gender   = ((x($profile,'gender')   == 1) ? t('Gender:')   : False);
	$marital  = ((x($profile,'marital')  == 1) ? t('Status:')   : False);
	$homepage = ((x($profile,'homepage') == 1) ? t('Homepage:') : False);
	$profile['online']   = (($profile['online_status'] === 'online') ? t('Online Now') : False);

//	logger('online: ' . $profile['online']);


	if(($profile['hidewall'] && (! local_channel()) && (! remote_channel())) || $block ) {
		$location = $reddress = $pdesc = $gender = $marital = $homepage = False;
	}

	if($profile['gender']) {
		$profile['gender_icon'] = gender_icon($profile['gender']);
	}

	$firstname = ((strpos($profile['channel_name'],' '))
		? trim(substr($profile['channel_name'],0,strpos($profile['channel_name'],' '))) : $profile['channel_name']);
	$lastname = (($firstname === $profile['channel_name']) ? '' : trim(substr($profile['channel_name'],strlen($firstname))));

	// @fixme move this to the diaspora plugin itself

	$contact_block = contact_block();

	$channel_menu = false;
	$menu = get_pconfig($profile['uid'],'system','channel_menu');
	if($menu && ! $block) {
		require_once('include/menu.php');
		$m = menu_fetch($menu,$profile['uid'],$observer['xchan_hash']);
		if($m)
			$channel_menu = menu_render($m);
	}
	$menublock = get_pconfig($profile['uid'],'system','channel_menublock');
	if ($menublock && (! $block)) {
		$comanche = new Zotlabs\Render\Comanche();
		$channel_menu .= $comanche->block($menublock);
	}

	if($zcard)
		$tpl = get_markup_template('profile_vcard_short.tpl');
	else
		$tpl = get_markup_template('profile_vcard.tpl');

	$o .= replace_macros($tpl, array(
		'$zcard'         => $zcard,
		'$profile'       => $profile,
		'$connect'       => $connect,
		'$connect_url'   => $connect_url,
		'$location'      => $location,
		'$gender'        => $gender,
		'$pdesc'         => $pdesc,
		'$marital'       => $marital,
		'$homepage'      => $homepage,
		'$chanmenu'      => $channel_menu,
		'$reddress'      => $reddress,
		'$rating'        => '',
		'$contact_block' => $contact_block,
		'$editmenu'	 => profile_edit_menu($profile['uid'])
	));

	$arr = array('profile' => $profile, 'entry' => $o);

	call_hooks('profile_sidebar', $arr);

	return $arr['entry'];

}

function gender_icon($gender) {

//	logger('gender: ' . $gender);

	// This can easily get throw off if the observer language is different 
	// than the channel owner language.

	if(strpos(strtolower($gender),strtolower(t('Female'))) !== false)
		return 'venus';
	if(strpos(strtolower($gender),strtolower(t('Male'))) !== false)
		return 'mars';
	if(strpos(strtolower($gender),strtolower(t('Trans'))) !== false)
		return 'transgender';
	if(strpos(strtolower($gender),strtolower(t('Neuter'))) !== false)
		return 'neuter';
	if(strpos(strtolower($gender),strtolower(t('Non-specific'))) !== false)
		return 'genderless';

	return '';
}


function advanced_profile(&$a) {
	require_once('include/text.php');
	if(! perm_is_allowed(App::$profile['profile_uid'],get_observer_hash(),'view_profile'))
		return '';

	if(App::$profile['fullname']) {

		$profile_fields_basic    = get_profile_fields_basic();
		$profile_fields_advanced = get_profile_fields_advanced();

		$advanced = ((feature_enabled(App::$profile['profile_uid'],'advanced_profiles')) ? true : false);
		if($advanced)
			$fields = $profile_fields_advanced;
		else
			$fields = $profile_fields_basic;

		$clean_fields = array();
		if($fields) {
			foreach($fields as $k => $v) {
				$clean_fields[] = trim($k);
			}
		}


		$tpl = get_markup_template('profile_advanced.tpl');

		$profile = array();

		$profile['fullname'] = array( t('Full Name:'), App::$profile['fullname'] ) ;

		if(App::$profile['gender']) $profile['gender'] = array( t('Gender:'),  App::$profile['gender'] );

		$ob_hash = get_observer_hash();
		if($ob_hash && perm_is_allowed(App::$profile['profile_uid'],$ob_hash,'post_like')) {
			$profile['canlike'] = true;
			$profile['likethis'] = t('Like this channel');
			$profile['profile_guid'] = App::$profile['profile_guid'];
		}

		$likers = q("select liker, xchan.*  from likes left join xchan on liker = xchan_hash where channel_id = %d and target_type = '%s' and verb = '%s'",
			intval(App::$profile['profile_uid']),
			dbesc(ACTIVITY_OBJ_PROFILE),
			dbesc(ACTIVITY_LIKE)
		);
		$profile['likers'] = array();
		$profile['like_count'] = count($likers);
		$profile['like_button_label'] = tt('Like','Likes',$profile['like_count'],'noun');
		if($likers) {
			foreach($likers as $l)
				$profile['likers'][] = array('name' => $l['xchan_name'],'photo' => zid($l['xchan_photo_s']), 'url' => zid($l['xchan_url']));
		}

		if((App::$profile['dob']) && (App::$profile['dob'] != '0000-00-00')) {

			$val = '';

			if((substr(App::$profile['dob'],5,2) === '00') || (substr(App::$profile['dob'],8,2) === '00'))
				$val = substr(App::$profile['dob'],0,4);

			$year_bd_format = t('j F, Y');
			$short_bd_format = t('j F');

			if(! $val) {
				$val = ((intval(App::$profile['dob']))
					? day_translate(datetime_convert('UTC','UTC',App::$profile['dob'] . ' 00:00 +00:00',$year_bd_format))
					: day_translate(datetime_convert('UTC','UTC','2001-' . substr(App::$profile['dob'],5) . ' 00:00 +00:00',$short_bd_format)));
			}
			$profile['birthday'] = array( t('Birthday:'), $val);
		}

		if($age = age(App::$profile['dob'],App::$profile['timezone'],''))
			$profile['age'] = array( t('Age:'), $age );

		if(App::$profile['marital'])
			$profile['marital'] = array( t('Status:'), App::$profile['marital']);

		if(App::$profile['partner'])
			$profile['marital']['partner'] = zidify_links(bbcode(App::$profile['partner']));

		if(strlen(App::$profile['howlong']) && App::$profile['howlong'] > NULL_DATE) {
			$profile['howlong'] = relative_date(App::$profile['howlong'], t('for %1$d %2$s'));
		}

		if(App::$profile['sexual']) $profile['sexual'] = array( t('Sexual Preference:'), App::$profile['sexual'] );

		if(App::$profile['homepage']) $profile['homepage'] = array( t('Homepage:'), linkify(App::$profile['homepage']) );

		if(App::$profile['hometown']) $profile['hometown'] = array( t('Hometown:'), linkify(App::$profile['hometown']) );

		if(App::$profile['keywords']) $profile['keywords'] = array( t('Tags:'), App::$profile['keywords']);

		if(App::$profile['politic']) $profile['politic'] = array( t('Political Views:'), App::$profile['politic']);

		if(App::$profile['religion']) $profile['religion'] = array( t('Religion:'), App::$profile['religion']);

		if($txt = prepare_text(App::$profile['about'])) $profile['about'] = array( t('About:'), $txt );

		if($txt = prepare_text(App::$profile['interest'])) $profile['interest'] = array( t('Hobbies/Interests:'), $txt);

		if($txt = prepare_text(App::$profile['likes'])) $profile['likes'] = array( t('Likes:'), $txt);

		if($txt = prepare_text(App::$profile['dislikes'])) $profile['dislikes'] = array( t('Dislikes:'), $txt);

		if($txt = prepare_text(App::$profile['contact'])) $profile['contact'] = array( t('Contact information and Social Networks:'), $txt);

		if($txt = prepare_text(App::$profile['channels'])) $profile['channels'] = array( t('My other channels:'), $txt);

		if($txt = prepare_text(App::$profile['music'])) $profile['music'] = array( t('Musical interests:'), $txt);

		if($txt = prepare_text(App::$profile['book'])) $profile['book'] = array( t('Books, literature:'), $txt);

		if($txt = prepare_text(App::$profile['tv'])) $profile['tv'] = array( t('Television:'), $txt);

		if($txt = prepare_text(App::$profile['film'])) $profile['film'] = array( t('Film/dance/culture/entertainment:'), $txt);

		if($txt = prepare_text(App::$profile['romance'])) $profile['romance'] = array( t('Love/Romance:'), $txt);

		if($txt = prepare_text(App::$profile['employment'])) $profile['employment'] = array( t('Work/employment:'), $txt);

		if($txt = prepare_text(App::$profile['education'])) $profile['education'] = array( t('School/education:'), $txt );

		if(App::$profile['extra_fields']) {
			foreach(App::$profile['extra_fields'] as $f) {
				$x = q("select * from profdef where field_name = '%s' limit 1",
					dbesc($f)
				);
				if($x && $txt = prepare_text(App::$profile[$f]))
					$profile[$f] = array( $x[0]['field_desc'] . ':',$txt);
			}
			$profile['extra_fields'] = App::$profile['extra_fields'];
		}

		$things = get_things(App::$profile['profile_guid'],App::$profile['profile_uid']);


//		logger('mod_profile: things: ' . print_r($things,true), LOGGER_DATA);

		$exportlink = ((App::$profile['profile_vcard']) ? zid(z_root() . '/profile/' . App::$profile['channel_address'] . '/vcard') : '');

		return replace_macros($tpl, array(
			'$title' => t('Profile'),
			'$canlike' => (($profile['canlike'])? true : false),
			'$likethis' => t('Like this thing'),
			'$export'   => t('Export'),
			'$exportlink' => $exportlink,
			'$profile' => $profile,
			'$fields' => $clean_fields,
			'$editmenu' => profile_edit_menu(App::$profile['profile_uid']),
			'$things' => $things
		));
	}

	return '';
}


function get_my_url() {
	if(x($_SESSION, 'zrl_override'))
		return $_SESSION['zrl_override'];
	if(x($_SESSION, 'my_url'))
		return $_SESSION['my_url'];

	return false;
}

function get_my_address() {
	if(x($_SESSION, 'zid_override'))
		return $_SESSION['zid_override'];
	if(x($_SESSION, 'my_address'))
		return $_SESSION['my_address'];

	return false;
}

/**
 * @brief Add visitor's zid to our xchan and attempt authentication.
 *
 * If somebody arrives at our site using a zid, add their xchan to our DB if we
 * don't have it already.
 * And if they aren't already authenticated here, attempt reverse magic auth.
 *
 * @hooks zid_init
 *   * \e string \b zid - their zid
 *   * \e string \b url - the destination url
 */
function zid_init() {
	$tmp_str = get_my_address();
	if(validate_email($tmp_str)) {
		$arr = array('zid' => $tmp_str, 'url' => App::$cmd);
		call_hooks('zid_init',$arr);
		if(! local_channel()) {
			$r = q("select * from hubloc where hubloc_addr = '%s' order by hubloc_connected desc limit 1",
				dbesc($tmp_str)
			);
			if(! $r) {
				Zotlabs\Daemon\Master::Summon(array('Gprobe',bin2hex($tmp_str)));
			}
			if($r && remote_channel() && remote_channel() === $r[0]['hubloc_hash'])
				return;
			logger('zid_init: not authenticated. Invoking reverse magic-auth for ' . $tmp_str);
			// try to avoid recursion - but send them home to do a proper magic auth
			$query = App::$query_string;
			$query = str_replace(array('?zid=','&zid='),array('?rzid=','&rzid='),$query);
			$dest = '/' . urlencode($query);
			if($r && ($r[0]['hubloc_url'] != z_root()) && (! strstr($dest,'/magic')) && (! strstr($dest,'/rmagic'))) {
				goaway($r[0]['hubloc_url'] . '/magic' . '?f=&rev=1&owa=1&dest=' . z_root() . $dest);
			}
			else
				logger('zid_init: no hubloc found.');
		}
	}
}

/**
 * @brief If somebody arrives at our site using a zat, authenticate them.
 *
 */
function zat_init() {
	if(local_channel() || remote_channel())
		return;

	$r = q("select * from atoken where atoken_token = '%s' limit 1",
		dbesc($_REQUEST['zat'])
	);
	if($r) {
		$xchan = atoken_xchan($r[0]);
		atoken_login($xchan);
	}
}




// Used from within PCSS themes to set theme parameters. If there's a
// puid request variable, that is the "page owner" and normally their theme
// settings take precedence; unless a local user sets the "always_my_theme"
// system pconfig, which means they don't want to see anybody else's theme
// settings except their own while on this site.

function get_theme_uid() {
	$uid = (($_REQUEST['puid']) ? intval($_REQUEST['puid']) : 0);
	if(local_channel()) {
		if((get_pconfig(local_channel(),'system','always_my_theme')) || (! $uid))
			return local_channel();
	}
	if(! $uid) {
		$x = get_sys_channel();
		if($x)
			return $x['channel_id'];
	}

	return $uid;
}

/**
* @brief Retrieves the path of the default_profile_photo for this system
* with the specified size.
*
* @param int $size
*  one of (300, 80, 48)
* @returns string with path to profile photo
*/
function get_default_profile_photo($size = 300) {
	$scheme = get_config('system','default_profile_photo');
	if(! $scheme)
		$scheme = 'rainbow_man';

	return 'images/default_profile_photos/' . $scheme . '/' . $size . '.png';
}

/**
 * @brief Test whether a given identity is NOT a member of the Hubzilla.
 *
 * @param string $s;
 *    xchan_hash of the identity in question
 * @returns boolean true or false
 */
function is_foreigner($s) {
	return((strpbrk($s, '.:@')) ? true : false);
}

/**
 * @brief Test whether a given identity is a member of the Hubzilla.
 *
 * @param string $s;
 *    xchan_hash of the identity in question
 * @returns boolean true or false
 */
function is_member($s) {
	return((is_foreigner($s)) ? false : true);
}

function get_online_status($nick) {

	$ret = array('result' => false);

	if(observer_prohibited())
		return $ret;

	$r = q("select channel_id, channel_hash from channel where channel_address = '%s' limit 1",
		dbesc(argv(1))
	);
	if($r) {
		$hide = get_pconfig($r[0]['channel_id'],'system','hide_online_status');
		if($hide)
			return $ret;
		$x = q("select cp_status from chatpresence where cp_xchan = '%s' and cp_room = 0 limit 1",
			dbesc($r[0]['channel_hash'])
		);
		if($x)
			$ret['result'] = $x[0]['cp_status'];
	}

	return $ret;
}


function remote_online_status($webbie) {

	$result = false;
	$r = q("select * from hubloc where hubloc_addr = '%s' limit 1",
		dbesc($webbie)
	);
	if(! $r)
		return $result;

	$url = $r[0]['hubloc_url'] . '/online/' . substr($webbie,0,strpos($webbie,'@'));

	$x = z_fetch_url($url);
	if($x['success']) {
		$j = json_decode($x['body'],true);
		if($j)
			$result = (($j['result']) ? $j['result'] : false);
	}

	return $result;
}


/**
 * @brief
 *
 * @return string
 */

function identity_selector() {
	if(local_channel()) {
		$r = q("select channel.*, xchan.* from channel left join xchan on channel.channel_hash = xchan.xchan_hash where channel.channel_account_id = %d and channel_removed = 0 order by channel_name ",
			intval(get_account_id())
		);
		if($r && count($r) > 1) {
			//$account = App::get_account();
			$o = replace_macros(get_markup_template('channel_id_select.tpl'), array(
				'$channels' => $r,
				'$selected' => local_channel()
			));
			return $o;
		}
	}

	return '';
}


function is_public_profile() {
	if(! local_channel())
		return false;
	if(intval(get_config('system','block_public')))
		return false;
	$channel = App::get_channel();
	if($channel) {
		$perm = \Zotlabs\Access\PermissionLimits::Get($channel['channel_id'],'view_profile');
		if($perm == PERMS_PUBLIC)
			return true;
	}
	return false;
}

function get_profile_fields_basic($filter = 0) {

	$profile_fields_basic = (($filter == 0) ? get_config('system','profile_fields_basic') : null);
	if(! $profile_fields_basic)
		$profile_fields_basic = array('fullname','pdesc','chandesc','comms','gender','dob','dob_tz','address','locality','region','postal_code','country_name','marital','sexual','homepage','hometown','keywords','about','contact');

	$x = array();
	if($profile_fields_basic)
		foreach($profile_fields_basic as $f)
			$x[$f] = 1;

	return $x;
}


function get_profile_fields_advanced($filter = 0) {
	$basic = get_profile_fields_basic($filter);
	$profile_fields_advanced = (($filter == 0) ? get_config('system','profile_fields_advanced') : null);
	if(! $profile_fields_advanced)
		$profile_fields_advanced = array('partner','howlong','politic','religion','likes','dislikes','interest','channels','music','book','film','tv','romance','employment','education');

	$x = array();
	if($basic)
		foreach($basic as $f => $v)
			$x[$f] = $v;

	if($profile_fields_advanced)
		foreach($profile_fields_advanced as $f)
			$x[$f] = 1;

	return $x;
}

/**
 * @brief Clear notifyflags for a channel.
 *
 * Most likely during bulk import of content or other activity that is likely
 * to generate huge amounts of undesired notifications.
 *
 * @param int $channel_id
 *    The channel to disable notifications for
 * @returns int
 *    Current notification flag value. Send this to notifications_on() to restore the channel settings when finished
 *    with the activity requiring notifications_off();
 */
function notifications_off($channel_id) {
	$r = q("select channel_notifyflags from channel where channel_id = %d limit 1",
		intval($channel_id)
	);
	q("update channel set channel_notifyflags = 0 where channel_id = %d",
		intval($channel_id)
	);

	return intval($r[0]['channel_notifyflags']);
}


function notifications_on($channel_id, $value) {
	$x = q("update channel set channel_notifyflags = %d where channel_id = %d",
		intval($value),
		intval($channel_id)
	);

	return $x;
}


function get_channel_default_perms($uid) {

	$ret = [];

	$r = q("select abook_xchan from abook where abook_channel = %d and abook_self = 1 limit 1",
		intval($uid)
	);
	if($r) {
		$x = load_abconfig($uid,$r[0]['abook_xchan'],'my_perms');
		if($x) {
			foreach($x as $xv) {
				if(intval($xv['v'])) {
					$ret[] = $xv['k'];
				}
			}
		}
	}

	return $ret;
}


function profiles_build_sync($channel_id) {
	$r = q("select * from profile where uid = %d",
		intval($channel_id)
	);
	if($r) {
		build_sync_packet($channel_id,array('profile' => $r));
	}
}


function auto_channel_create($account_id) {

	if(! $account_id)
		return false;

	$arr = array();
	$arr['account_id'] = $account_id;
	$arr['name'] = get_aconfig($account_id,'register','channel_name');
	$arr['nickname'] = legal_webbie(get_aconfig($account_id,'register','channel_address'));
	$arr['permissions_role'] = get_aconfig($account_id,'register','permissions_role');

	del_aconfig($account_id,'register','channel_name');
	del_aconfig($account_id,'register','channel_address');
	del_aconfig($account_id,'register','permissions_role');

	if((! $arr['name']) || (! $arr['nickname'])) {
		$x = q("select * from account where account_id = %d limit 1",
			intval($account_id)
		);
		if($x) {
			if(! $arr['name'])
				$arr['name'] = substr($x[0]['account_email'],0,strpos($x[0]['account_email'],'@'));
			if(! $arr['nickname'])
				$arr['nickname'] = legal_webbie(substr($x[0]['account_email'],0,strpos($x[0]['account_email'],'@')));
		}
	}
	if(! $arr['permissions_role'])
		$arr['permissions_role'] = 'social';

	if(validate_channelname($arr['name']))
		return false;
	if($arr['nickname'] === 'sys')
		$arr['nickname'] = $arr['nickname'] . mt_rand(1000,9999);

	$arr['nickname'] = check_webbie(array($arr['nickname'], $arr['nickname'] . mt_rand(1000,9999)));

	return create_identity($arr);
}

function get_cover_photo($channel_id,$format = 'bbcode', $res = PHOTO_RES_COVER_1200) {

	$r = q("select height, width, resource_id, mimetype from photo where uid = %d and imgscale = %d and photo_usage = %d",
		intval($channel_id),
		intval($res),
		intval(PHOTO_COVER)
	);
	if(! $r)
		return false;

	$output = false;

	$url = z_root() . '/photo/' . $r[0]['resource_id'] . '-' . $res ;

	switch($format) {
		case 'bbcode':
			$output = '[zrl=' . $r[0]['width'] . 'x' . $r[0]['height'] . ']' . $url . '[/zrl]';
			break;
		case 'html':
 			$output = '<img class="zrl" width="' . $r[0]['width'] . '" height="' . $r[0]['height'] . '" src="' . $url . '" alt="' . t('cover photo') . '" />';
			break;
		case 'array':
		default:
			$output = array(
				'width' => $r[0]['width'],
				'height' => $r[0]['height'],
				'type' => $r[0]['mimetype'],
				'url' => $url
			);
			break;
	}

	return $output;
}

/**
 * @brief
 *
 * @param array $channel
 * @param string $observer_hash
 * @param array $args
 * @return string
 */
function get_zcard($channel, $observer_hash = '', $args = array()) {

	logger('get_zcard');

	$maxwidth = (($args['width']) ? intval($args['width']) : 0);
	$maxheight = (($args['height']) ? intval($args['height']) : 0);

	if(($maxwidth > 1200) || ($maxwidth < 1)) {
		$maxwidth = 1200;
		$cover_width = 1200;
	}

	if($maxwidth <= 425) {
		$width = 425;
		$cover_width = 425;
		$size = 'hz_small';
		$cover_size = PHOTO_RES_COVER_425;
		$pphoto = array('mimetype' => $channel['xchan_photo_mimetype'], 'width' => 80 , 'height' => 80, 'href' => $channel['xchan_photo_m']);
	} elseif($maxwidth <= 900) {
		$width = 900;
		$cover_width = 850;
		$size = 'hz_medium';
		$cover_size = PHOTO_RES_COVER_850;
		$pphoto = array('mimetype' => $channel['xchan_photo_mimetype'], 'width' => 160 , 'height' => 160, 'href' => $channel['xchan_photo_l']);
	} elseif($maxwidth <= 1200) {
		$width = 1200;
		$cover_width = 1200;
		$size = 'hz_large';
		$cover_size = PHOTO_RES_COVER_1200;
		$pphoto = array('mimetype' => $channel['xchan_photo_mimetype'], 'width' => 300 , 'height' => 300, 'href' => $channel['xchan_photo_l']);
	}

//	$scale = (float) $maxwidth / $width;
//	$translate = intval(($scale / 1.0) * 100);

	$channel['channel_addr'] = channel_reddress($channel);
	$zcard = array('chan' => $channel);

	$r = q("select height, width, resource_id, imgscale, mimetype from photo where uid = %d and imgscale = %d and photo_usage = %d",
		intval($channel['channel_id']),
		intval($cover_size),
		intval(PHOTO_COVER)
	);

	if($r) {
		$cover = $r[0];
		$cover['href'] = z_root() . '/photo/' . $r[0]['resource_id'] . '-' . $r[0]['imgscale'];
	} else {
		$default_cover = get_config('system','default_cover_photo','pexels-94622');
		$cover = [ 'href' => z_root() . '/images/default_cover_photos/' . $default_cover . '/' . $cover_width . '.jpg' ];
	}

	$o .= replace_macros(get_markup_template('zcard.tpl'), array(
		'$maxwidth' => $maxwidth,
		'$scale' => $scale,
		'$translate' => $translate,
		'$size' => $size,
		'$cover' => $cover,
		'$pphoto' => $pphoto,
		'$zcard' => $zcard
	));

	return $o;
}


function get_zcard_embed($channel, $observer_hash = '', $args = array()) {

	logger('get_zcard_embed');

	$maxwidth = (($args['width']) ? intval($args['width']) : 0);
	$maxheight = (($args['height']) ? intval($args['height']) : 0);

	if(($maxwidth > 1200) || ($maxwidth < 1)) {
		$maxwidth = 1200;
		$cover_width = 1200;
	}

	if($maxwidth <= 425) {
		$width = 425;
		$cover_width = 425;
		$size = 'hz_small';
		$cover_size = PHOTO_RES_COVER_425;
		$pphoto = array('mimetype' => $channel['xchan_photo_mimetype'],  'width' => 80 , 'height' => 80, 'href' => $channel['xchan_photo_m']);
	}
	elseif($maxwidth <= 900) {
		$width = 900;
		$cover_width = 850;
		$size = 'hz_medium';
		$cover_size = PHOTO_RES_COVER_850;
		$pphoto = array('mimetype' => $channel['xchan_photo_mimetype'],  'width' => 160 , 'height' => 160, 'href' => $channel['xchan_photo_l']);
	}
	elseif($maxwidth <= 1200) {
		$width = 1200;
		$cover_width = 1200;
		$size = 'hz_large';
		$cover_size = PHOTO_RES_COVER_1200;
		$pphoto = array('mimetype' => $channel['xchan_photo_mimetype'],  'width' => 300 , 'height' => 300, 'href' => $channel['xchan_photo_l']);
	}

	$channel['channel_addr'] = channel_reddress($channel);
	$zcard = array('chan' => $channel);

	$r = q("select height, width, resource_id, imgscale, mimetype from photo where uid = %d and imgscale = %d and photo_usage = %d",
		intval($channel['channel_id']),
		intval($cover_size),
		intval(PHOTO_COVER)
	);

	if($r) {
		$cover = $r[0];
		$cover['href'] = z_root() . '/photo/' . $r[0]['resource_id'] . '-' . $r[0]['imgscale'];
	}
	else {
		$default_cover = get_config('system','default_cover_photo','pexels-94622');
		$cover = [ 'href' => z_root() . '/images/default_cover_photos/' . $default_cover . '/' . $cover_width . '.jpg' ];
	}

	$o .= replace_macros(get_markup_template('zcard_embed.tpl'),array(
		'$maxwidth' => $maxwidth,
		'$scale' => $scale,
		'$translate' => $translate,
		'$size' => $size,
		'$cover' => $cover,
		'$pphoto' => $pphoto,
		'$zcard' => $zcard
	));

	return $o;
}

/**
 * @brief
 *
 * @param string $nick
 * @return mixed
 */
function channelx_by_nick($nick) {
	$r = q("SELECT * FROM channel left join xchan on channel_hash = xchan_hash WHERE channel_address = '%s'  and channel_removed = 0 LIMIT 1",
		dbesc($nick)
	);

	return(($r) ? $r[0] : false);
}

/**
 * @brief
 *
 * @param string $hash
 * @return mixed
 */
function channelx_by_hash($hash) {
	$r = q("SELECT * FROM channel left join xchan on channel_hash = xchan_hash WHERE channel_hash = '%s' and channel_removed = 0 LIMIT 1",
		dbesc($hash)
	);

	return(($r) ? $r[0] : false);
}

/**
 * @brief
 *
 * @param int $id
 * @return mixed
 */
function channelx_by_n($id) {
	$r = q("SELECT * FROM channel left join xchan on channel_hash = xchan_hash WHERE channel_id = %d and channel_removed = 0 LIMIT 1",
		dbesc($id)
	);

	return(($r) ? $r[0] : false);
}

/**
 * @brief
 *
 * @param string $channel
 * @return string
 */
function channel_reddress($channel) {
	if(! ($channel && array_key_exists('channel_address', $channel)))
		return '';

	return strtolower($channel['channel_address'] . '@' . App::get_hostname());
}


function channel_manual_conv_update($channel_id) {

	$x = get_pconfig($channel_id, 'system','manual_conversation_update');
	if($x === false)
		$x = get_config('system','manual_conversation_update', 1);

	return intval($x);
}


function remote_login() {

		$o = replace_macros(get_markup_template('remote_login.tpl'),array(
			'$title' => t('Remote Authentication'),
			'$desc' => t('Enter your channel address (e.g. channel@example.com)'),
			'$submit' => t('Authenticate')
		));
		return $o;

}

function channel_store_lowlevel($arr) {
    $store = [
        'channel_account_id'      => ((array_key_exists('channel_account_id',$arr))      ? $arr['channel_account_id']      : '0'),
        'channel_primary'         => ((array_key_exists('channel_primary',$arr))         ? $arr['channel_primary']         : '0'),
        'channel_name'            => ((array_key_exists('channel_name',$arr))            ? $arr['channel_name']            : ''),
        'channel_address'         => ((array_key_exists('channel_address',$arr))         ? $arr['channel_address']         : ''),
        'channel_guid'            => ((array_key_exists('channel_guid',$arr))            ? $arr['channel_guid']            : ''),
        'channel_guid_sig'        => ((array_key_exists('channel_guid_sig',$arr))        ? $arr['channel_guid_sig']        : ''),
        'channel_hash'            => ((array_key_exists('channel_hash',$arr))            ? $arr['channel_hash']            : ''),
        'channel_timezone'        => ((array_key_exists('channel_timezone',$arr))        ? $arr['channel_timezone']        : 'UTC'),
        'channel_location'        => ((array_key_exists('channel_location',$arr))        ? $arr['channel_location']        : ''),
        'channel_theme'           => ((array_key_exists('channel_theme',$arr))           ? $arr['channel_theme']           : ''),
        'channel_startpage'       => ((array_key_exists('channel_startpage',$arr))       ? $arr['channel_startpage']       : ''),
        'channel_pubkey'          => ((array_key_exists('channel_pubkey',$arr))          ? $arr['channel_pubkey']          : ''),
        'channel_prvkey'          => ((array_key_exists('channel_prvkey',$arr))          ? $arr['channel_prvkey']          : ''),
        'channel_notifyflags'     => ((array_key_exists('channel_notifyflags',$arr))     ? $arr['channel_notifyflags']     : '65535'),
        'channel_pageflags'       => ((array_key_exists('channel_pageflags',$arr))       ? $arr['channel_pageflags']       : '0'),
        'channel_dirdate'         => ((array_key_exists('channel_dirdate',$arr))         ? $arr['channel_dirdate']         : NULL_DATE),
        'channel_lastpost'        => ((array_key_exists('channel_lastpost',$arr))        ? $arr['channel_lastpost']        : NULL_DATE),
        'channel_deleted'         => ((array_key_exists('channel_deleted',$arr))         ? $arr['channel_deleted']         : NULL_DATE),
        'channel_max_anon_mail'   => ((array_key_exists('channel_max_anon_mail',$arr))   ? $arr['channel_max_anon_mail']   : '10'),
        'channel_max_friend_req'  => ((array_key_exists('channel_max_friend_req',$arr))  ? $arr['channel_max_friend_req']  : '10'),
        'channel_expire_days'     => ((array_key_exists('channel_expire_days',$arr))     ? $arr['channel_expire_days']     : '0'),
        'channel_passwd_reset'    => ((array_key_exists('channel_passwd_reset',$arr))    ? $arr['channel_passwd_reset']    : ''),
        'channel_default_group'   => ((array_key_exists('channel_default_group',$arr))   ? $arr['channel_default_group']   : ''),
        'channel_allow_cid'       => ((array_key_exists('channel_allow_cid',$arr))       ? $arr['channel_allow_cid']       : ''),
        'channel_allow_gid'       => ((array_key_exists('channel_allow_gid',$arr))       ? $arr['channel_allow_gid']       : ''),
        'channel_deny_cid'        => ((array_key_exists('channel_deny_cid',$arr))        ? $arr['channel_deny_cid']        : ''),
        'channel_deny_gid'        => ((array_key_exists('channel_deny_gid',$arr))        ? $arr['channel_deny_gid']        : ''),
        'channel_removed'         => ((array_key_exists('channel_removed',$arr))         ? $arr['channel_removed']         : '0'),
        'channel_system'          => ((array_key_exists('channel_system',$arr))          ? $arr['channel_system']          : '0'),

		'channel_moved'           => ((array_key_exists('channel_moved',$arr))           ? $arr['channel_moved']           : ''),
		'channel_password'        => ((array_key_exists('channel_password',$arr))        ? $arr['channel_password']        : ''),
		'channel_salt'            => ((array_key_exists('channel_salt',$arr))            ? $arr['channel_salt']            : '')

	];

	return create_table_from_array('channel',$store);

}

function profile_store_lowlevel($arr) {

    $store = [
        'profile_guid'  => ((array_key_exists('profile_guid',$arr))  ? $arr['profile_guid']  : ''),
        'aid'           => ((array_key_exists('aid',$arr))           ? $arr['aid']           : 0),
        'uid'           => ((array_key_exists('uid',$arr))           ? $arr['uid']           : 0),
        'profile_name'  => ((array_key_exists('profile_name',$arr))  ? $arr['profile_name']  : ''),
        'is_default'    => ((array_key_exists('is_default',$arr))    ? $arr['is_default']    : 0),
        'hide_friends'  => ((array_key_exists('hide_friends',$arr))  ? $arr['hide_friends']  : 0),
        'fullname'      => ((array_key_exists('fullname',$arr))      ? $arr['fullname']      : ''),
        'pdesc'         => ((array_key_exists('pdesc',$arr))         ? $arr['pdesc']         : ''),
        'chandesc'      => ((array_key_exists('chandesc',$arr))      ? $arr['chandesc']      : ''),
        'dob'           => ((array_key_exists('dob',$arr))           ? $arr['dob']           : ''),
        'dob_tz'        => ((array_key_exists('dob_tz',$arr))        ? $arr['dob_tz']        : ''),
        'address'       => ((array_key_exists('address',$arr))       ? $arr['address']       : ''),
        'locality'      => ((array_key_exists('locality',$arr))      ? $arr['locality']      : ''),
        'region'        => ((array_key_exists('region',$arr))        ? $arr['region']        : ''),
        'postal_code'   => ((array_key_exists('postal_code',$arr))   ? $arr['postal_code']   : ''),
        'country_name'  => ((array_key_exists('country_name',$arr))  ? $arr['country_name']  : ''),
        'hometown'      => ((array_key_exists('hometown',$arr))      ? $arr['hometown']      : ''),
        'gender'        => ((array_key_exists('gender',$arr))        ? $arr['gender']        : ''),
        'marital'       => ((array_key_exists('marital',$arr))       ? $arr['marital']       : ''),
        'partner'       => ((array_key_exists('partner',$arr))       ? $arr['partner']       : ''),
        'howlong'       => ((array_key_exists('howlong',$arr))       ? $arr['howlong']       : NULL_DATE),
        'sexual'        => ((array_key_exists('sexual',$arr))        ? $arr['sexual']        : ''),
        'politic'       => ((array_key_exists('politic',$arr))       ? $arr['politic']       : ''),
        'religion'      => ((array_key_exists('religion',$arr))      ? $arr['religion']      : ''),
        'keywords'      => ((array_key_exists('keywords',$arr))      ? $arr['keywords']      : ''),
        'likes'         => ((array_key_exists('likes',$arr))         ? $arr['likes']         : ''),
        'dislikes'      => ((array_key_exists('dislikes',$arr))      ? $arr['dislikes']      : ''),
        'about'         => ((array_key_exists('about',$arr))         ? $arr['about']         : ''),
        'summary'       => ((array_key_exists('summary',$arr))       ? $arr['summary']       : ''),
        'music'         => ((array_key_exists('music',$arr))         ? $arr['music']         : ''),
        'book'          => ((array_key_exists('book',$arr))          ? $arr['book']          : ''),
        'tv'            => ((array_key_exists('tv',$arr))            ? $arr['tv']            : ''),
        'film'          => ((array_key_exists('film',$arr))          ? $arr['film']          : ''),
        'interest'      => ((array_key_exists('interest',$arr))      ? $arr['interest']      : ''),
        'romance'       => ((array_key_exists('romance',$arr))       ? $arr['romance']       : ''),
        'employment'    => ((array_key_exists('employment',$arr))    ? $arr['employment']    : ''),
        'education'     => ((array_key_exists('education',$arr))     ? $arr['education']     : ''),
        'contact'       => ((array_key_exists('contact',$arr))       ? $arr['contact']       : ''),
        'channels'      => ((array_key_exists('channels',$arr))      ? $arr['channels']      : ''),
        'homepage'      => ((array_key_exists('homepage',$arr))      ? $arr['homepage']      : ''),
        'photo'         => ((array_key_exists('photo',$arr))         ? $arr['photo']         : ''),
        'thumb'         => ((array_key_exists('thumb',$arr))         ? $arr['thumb']         : ''),
        'publish'       => ((array_key_exists('publish',$arr))       ? $arr['publish']       : 0),
        'profile_vcard' => ((array_key_exists('profile_vcard',$arr)) ? $arr['profile_vcard'] : '')
	];

	return create_table_from_array('profile',$store);
}


// Included here for completeness, but this is a very dangerous operation.
// It is the caller's responsibility to confirm the requestor's intent and
// authorisation to do this.

function account_remove($account_id,$local = true,$unset_session=true) {

	logger('account_remove: ' . $account_id);

	if(! intval($account_id)) {
		logger('account_remove: no account.');
		return false;
	}

	// Don't let anybody nuke the only admin account.

	$r = q("select account_id from account where (account_roles & %d) > 0",
		intval(ACCOUNT_ROLE_ADMIN)
	);

	if($r !== false && count($r) == 1 && $r[0]['account_id'] == $account_id) {
		logger("Unable to remove the only remaining admin account");
		return false;
	}

	$r = q("select * from account where account_id = %d limit 1",
		intval($account_id)
	);
	$account_email=$r[0]['account_email'];

	if(! $r) {
		logger('account_remove: No account with id: ' . $account_id);
		return false;
	}

	$x = q("select channel_id from channel where channel_account_id = %d",
		intval($account_id)
	);
	if($x) {
		foreach($x as $xx) {
			channel_remove($xx['channel_id'],$local,false);
		}
	}

	$r = q("delete from account where account_id = %d",
		intval($account_id)
	);


	if ($unset_session) {
		unset($_SESSION['authenticated']);
		unset($_SESSION['uid']);
		notice( sprintf(t("User '%s' deleted"),$account_email) . EOL);
		goaway(z_root());
	}
	return $r;

}

/**
 * @brief Removes a channel.
 *
 * @hooks channel_remove
 *   * \e array \b entry from channel tabel for $channel_id
 * @param int $channel_id
 * @param boolean $local default true
 * @param boolean $unset_session default false
 */
function channel_remove($channel_id, $local = true, $unset_session = false) {

	if(! $channel_id)
		return;

	logger('Removing channel: ' . $channel_id);
	logger('local only: ' . intval($local));

	$r = q("select * from channel where channel_id = %d limit 1", intval($channel_id));
	if(! $r) {
		logger('channel not found: ' . $channel_id);
		return;
	}

	$channel = $r[0];

	call_hooks('channel_remove', $r[0]);

	if(! $local) {

		$r = q("update channel set channel_deleted = '%s', channel_removed = 1 where channel_id = %d",
			dbesc(datetime_convert()),
			intval($channel_id)
		);

		q("delete from pconfig where uid = %d",
			intval($channel_id)
		);

		logger('deleting hublocs',LOGGER_DEBUG);

		$r = q("update hubloc set hubloc_deleted = 1 where hubloc_hash = '%s'",
			dbesc($channel['channel_hash'])
		);

		$r = q("update xchan set xchan_deleted = 1 where xchan_hash = '%s'",
			dbesc($channel['channel_hash'])
		);

		Zotlabs\Daemon\Master::Summon(array('Notifier','purge_all',$channel_id));
	}


	$r = q("select * from iconfig left join item on item.id = iconfig.iid
		where item.uid = %d",
		intval($channel_id)
	);
	if($r) {
		foreach($r as $rr) {
			q("delete from iconfig where iid = %d",
				intval($rr['iid'])
			);
		}
	}


	q("DELETE FROM groups WHERE uid = %d", intval($channel_id));
	q("DELETE FROM group_member WHERE uid = %d", intval($channel_id));
	q("DELETE FROM event WHERE uid = %d", intval($channel_id));
	q("DELETE FROM item WHERE uid = %d", intval($channel_id));
	q("DELETE FROM mail WHERE channel_id = %d", intval($channel_id));
	q("DELETE FROM notify WHERE uid = %d", intval($channel_id));
	q("DELETE FROM photo WHERE uid = %d", intval($channel_id));
	q("DELETE FROM attach WHERE uid = %d", intval($channel_id));
	q("DELETE FROM profile WHERE uid = %d", intval($channel_id));
	q("DELETE FROM pconfig WHERE uid = %d", intval($channel_id));

	/// @FIXME At this stage we need to remove the file resources located under /store/$nickname

	q("delete from abook where abook_xchan = '%s' and abook_self = 1 ",
		dbesc($channel['channel_hash'])
	);

	$r = q("update channel set channel_deleted = '%s', channel_removed = 1 where channel_id = %d",
		dbesc(datetime_convert()),
		intval($channel_id)
	);

	// if this was the default channel, set another one as default
	if(App::$account['account_default_channel'] == $channel_id) {
		$r = q("select channel_id from channel where channel_account_id = %d and channel_removed = 0 limit 1",
			intval(App::$account['account_id']),
			intval(PAGE_REMOVED));
		if ($r) {
			$rr = q("update account set account_default_channel = %d where account_id = %d",
				intval($r[0]['channel_id']),
				intval(App::$account['account_id']));
			logger("Default channel deleted, changing default to channel_id " . $r[0]['channel_id']);
		}
		else {
			$rr = q("update account set account_default_channel = 0 where account_id = %d",
				intval(App::$account['account_id'])
			);
		}
	}

	logger('deleting hublocs',LOGGER_DEBUG);

	$r = q("update hubloc set hubloc_deleted = 1 where hubloc_hash = '%s' and hubloc_url = '%s' ",
		dbesc($channel['channel_hash']),
		dbesc(z_root())
	);

	// Do we have any valid hublocs remaining?

	$hublocs = 0;

	$r = q("select hubloc_id from hubloc where hubloc_hash = '%s' and hubloc_deleted = 0",
		dbesc($channel['channel_hash'])
	);
	if($r)
		$hublocs = count($r);

	if(! $hublocs) {
		$r = q("update xchan set xchan_deleted = 1 where xchan_hash = '%s' ",
			dbesc($channel['channel_hash'])
		);
	}

	//remove from file system
	$r = q("select channel_address from channel where channel_id = %d limit 1",
		intval($channel_id)
	);

	if($r) {
		$channel_address = $r[0]['channel_address'] ;
	}
	if($channel_address) {
		$f = 'store/' . $channel_address.'/';
		logger('delete '. $f);
		if(is_dir($f)) {
				@rrmdir($f);
		}
	}

	Zotlabs\Daemon\Master::Summon(array('Directory',$channel_id));

	if($channel_id == local_channel() && $unset_session) {
		App::$session->nuke();
		goaway(z_root());
	}
}

/**
 * @brief This checks if a channel is allowed to publish executable code.
 *
 * It is up to the caller to determine if the observer or local_channel
 * is in fact the resource owner whose channel_id is being checked.
 *
 * @param int $channel_id
 * @return boolean
 */
function channel_codeallowed($channel_id) {
	if(! intval($channel_id))
		return false;

	$x = channelx_by_n($channel_id);
	if(($x) && ($x['channel_pageflags'] & PAGE_ALLOWCODE))
		return true;

	return false;

}

function anon_identity_init($reqvars) {

	$x = [ 'request_vars' => $reqvars, 'xchan' => null, 'success' => 'unset' ];
	call_hooks('anon_identity_init',$x);
	if($x['success'] !== 'unset' && intval($x['success']) && $x['xchan'])
		return $x['xchan'];

	// allow a captcha handler to over-ride 
	if($x['success'] !== 'unset' && (intval($x['success']) === 0))
		return false;	
	

	$anon_name  = strip_tags(trim($reqvars['anonname']));
	$anon_email = strip_tags(trim($reqvars['anonmail']));
	$anon_url   = strip_tags(trim($reqvars['anonurl']));

	if(! ($anon_name && $anon_email)) {
		logger('anonymous commenter did not complete form');
		return false;
	}

	if(! validate_email($anon_email)) {
		logger('enonymous email not valid');
		return false;
	}

	if(! $anon_url)
		$anon_url = z_root();

	$hash = hash('md5',$anon_email);

	$x = q("select * from xchan where xchan_guid = '%s' and xchan_hash = '%s' and xchan_network = 'unknown' limit 1",
		dbesc($anon_email),
		dbesc($hash)
	);

	if(! $x) {
		xchan_store_lowlevel([ 
			'xchan_guid'    => $anon_email,
			'xchan_hash'    => $hash,
			'xchan_name'    => $anon_name,
			'xchan_url'     => $anon_url,
			'xchan_network' => 'unknown',
			'xchan_name_date' => datetime_convert()
		]);
			

		$x = q("select * from xchan where xchan_guid = '%s' and xchan_hash = '%s' and xchan_network = 'unknown' limit 1",
			dbesc($anon_email),
			dbesc($hash)
		);

		$photo = z_root() . '/' . get_default_profile_photo(300);
		$photos = import_xchan_photo($photo,$hash);
		$r = q("update xchan set xchan_photo_date = '%s', xchan_photo_l = '%s', xchan_photo_m = '%s', xchan_photo_s = '%s', xchan_photo_mimetype = '%s' where xchan_guid = '%s' and xchan_hash = '%s' and xchan_network = 'unknown' ",
			dbesc(datetime_convert()),
			dbesc($photos[0]),
			dbesc($photos[1]),
			dbesc($photos[2]),
			dbesc($photos[3]),
			dbesc($anon_email),
			dbesc($hash)
		);

	}

	return $x[0];
}


