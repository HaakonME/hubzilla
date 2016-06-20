<?php
/**
 * @file include/dir_fns.php
 */

require_once('include/permissions.php');

/**
 * @brief
 *
 * @param int $dirmode
 * @return array
 */
function find_upstream_directory($dirmode) {
	global $DIRECTORY_FALLBACK_SERVERS;

	$preferred = get_config('system','directory_server');

	// Thwart attempts to use a private directory

	if(($preferred) && ($preferred != z_root())) {
		$r = q("select * from site where site_url = '%s' limit 1",
			dbesc($preferred)
		);
		if(($r) && ($r[0]['site_flags'] & DIRECTORY_MODE_STANDALONE)) {
			$preferred = '';
		}		
	}


	if (! $preferred) {

		/*
		 * No directory has yet been set. For most sites, pick one at random
		 * from our list of directory servers. However, if we're a directory
		 * server ourself, point at the local instance
		 * We will then set this value so this should only ever happen once.
		 * Ideally there will be an admin setting to change to a different 
		 * directory server if you don't like our choice or if circumstances change.
		 */

		$dirmode = intval(get_config('system','directory_mode'));
		if ($dirmode == DIRECTORY_MODE_NORMAL) {
			$toss = mt_rand(0,count($DIRECTORY_FALLBACK_SERVERS));
			$preferred = $DIRECTORY_FALLBACK_SERVERS[$toss];
			set_config('system','directory_server',$preferred);
		} else{
			set_config('system','directory_server',z_root());
		}
	}

	return array('url' => $preferred);
}

/**
 * Directories may come and go over time. We will need to check that our
 * directory server is still valid occasionally, and reset to something that
 * is if our directory has gone offline for any reason
 */
function check_upstream_directory() {

	$directory = get_config('system', 'directory_server');

	// it's possible there is no directory server configured and the local hub is being used.
	// If so, default to preserving the absence of a specific server setting.

	$isadir = true;

	if ($directory) {
		$h = parse_url($directory);
		if ($h) {
			$j = Zotlabs\Zot\Finger::run('[system]@' . $h['host']);
			if ($j['success']) {
				if (array_key_exists('site', $j) && array_key_exists('directory_mode', $j['site'])) {
					if ($j['site']['directory_mode'] === 'normal') {
						$isadir = false;
					}
				}
			}
		}
	}

	if (! $isadir)
		set_config('system', 'directory_server', '');
}

function get_directory_setting($observer, $setting) {

	if ($observer)
		$ret = get_xconfig($observer, 'directory', $setting);
	else
		$ret = ((array_key_exists($setting,$_SESSION)) ? intval($_SESSION[$setting]) : false);

	if($ret === false)
		$ret = get_config('directory', $setting);


	// 'safemode' is the default if there is no observer or no established preference. 

	if($setting == 'safemode' && $ret === false)
		$ret = 1;

	return $ret;
}

/**
 * @brief Called by the directory_sort widget.
 */
function dir_sort_links() {

	$safe_mode = 1;

	$observer = get_observer_hash();

	$safe_mode = get_directory_setting($observer, 'safemode');
	$globaldir = get_directory_setting($observer, 'globaldir');
	$pubforums = get_directory_setting($observer, 'pubforums');

	// Build urls without order and pubforums so it's easy to tack on the changed value
	// Probably there's an easier way to do this

	$directory_sort_order = get_config('system','directory_sort_order');
	if(! $directory_sort_order)
		$directory_sort_order = 'date';

	$current_order = (($_REQUEST['order']) ? $_REQUEST['order'] : $directory_sort_order);
	$suggest = (($_REQUEST['suggest']) ? '&suggest=' . $_REQUEST['suggest'] : '');

	$url = 'directory?f=';

	$tmp = array_merge($_GET,$_POST);
	unset($tmp['suggest']);
	unset($tmp['pubforums']);
	unset($tmp['global']);
	unset($tmp['safe']);
	unset($tmp['q']);
	unset($tmp['f']);
	$forumsurl = $url . http_build_query($tmp) . $suggest;

	$o = replace_macros(get_markup_template('dir_sort_links.tpl'), array(
		'$header' => t('Directory Options'),
		'$forumsurl' => $forumsurl,
		'$safemode' => array('safemode', t('Safe Mode'),$safe_mode,'',array(t('No'), t('Yes')),' onchange=\'window.location.href="' . $forumsurl . '&safe="+(this.checked ? 1 : 0)\''),
		'$pubforums' => array('pubforums', t('Public Forums Only'),$pubforums,'',array(t('No'), t('Yes')),' onchange=\'window.location.href="' . $forumsurl . '&pubforums="+(this.checked ? 1 : 0)\''),
		'$globaldir' => array('globaldir', t('This Website Only'), 1-intval($globaldir),'',array(t('No'), t('Yes')),' onchange=\'window.location.href="' . $forumsurl . '&global="+(this.checked ? 0 : 1)\''),
	));

	return $o;
}

/**
 * @brief Checks the directory mode of this hub.
 *
 * Checks the directory mode of this hub to see if it is some form of directory server. If it is,
 * get the directory realm of this hub. Fetch a list of all other directory servers in this realm and request
 * a directory sync packet. This will contain both directory updates and new ratings. Store these all in the DB. 
 * In the case of updates, we will query each of them asynchronously from a poller task. Ratings are stored 
 * directly if the rater's signature matches.
 *
 * @param int $dirmode;
 */
function sync_directories($dirmode) {

	if ($dirmode == DIRECTORY_MODE_STANDALONE || $dirmode == DIRECTORY_MODE_NORMAL)
		return;

	$realm = get_directory_realm();
	if ($realm == DIRECTORY_REALM) {
		$r = q("select * from site where (site_flags & %d) > 0 and site_url != '%s' and site_type = %d and ( site_realm = '%s' or site_realm = '') ",
			intval(DIRECTORY_MODE_PRIMARY|DIRECTORY_MODE_SECONDARY),
			dbesc(z_root()),
			intval(SITE_TYPE_ZOT),
			dbesc($realm)
		);
	} else {
		$r = q("select * from site where (site_flags & %d) > 0 and site_url != '%s' and site_realm like '%s' and site_type = %d ",
			intval(DIRECTORY_MODE_PRIMARY|DIRECTORY_MODE_SECONDARY),
			dbesc(z_root()),
			dbesc(protect_sprintf('%' . $realm . '%')),
			intval(SITE_TYPE_ZOT)
		);
	}

	// If there are no directory servers, setup the fallback master
	/** @FIXME What to do if we're in a different realm? */

	if ((! $r) && (z_root() != DIRECTORY_FALLBACK_MASTER)) {
		$r = array();
		$r[] = array(
			'site_url' => DIRECTORY_FALLBACK_MASTER,
			'site_flags' => DIRECTORY_MODE_PRIMARY,
			'site_update' => NULL_DATE, 
			'site_directory' => DIRECTORY_FALLBACK_MASTER . '/dirsearch',
			'site_realm' => DIRECTORY_REALM,
			'site_valid' => 1
			
		);
		$x = q("insert into site ( site_url, site_flags, site_update, site_directory, site_realm, site_valid )
			values ( '%s', %d, '%s', '%s', '%s', %d ) ",
			dbesc($r[0]['site_url']),
			intval($r[0]['site_flags']),
			dbesc($r[0]['site_update']),
			dbesc($r[0]['site_directory']),
			dbesc($r[0]['site_realm']),
			intval($r[0]['site_valid'])
		);

		$r = q("select * from site where site_flags in (%d, %d) and site_url != '%s' and site_type = %d ",
			intval(DIRECTORY_MODE_PRIMARY),
			intval(DIRECTORY_MODE_SECONDARY),
			dbesc(z_root()),
			intval(SITE_TYPE_ZOT)
		);
	}
	if (! $r)
		return;

	foreach ($r as $rr) {
		if (! $rr['site_directory'])
			continue;

		logger('sync directories: ' . $rr['site_directory']);

		// for brand new directory servers, only load the last couple of days.
		// It will take about a month for a new directory to obtain the full current repertoire of channels.
		/** @FIXME Go back and pick up earlier ratings if this is a new directory server. These do not get refreshed. */

		$token = get_config('system','realm_token');

		$syncdate = (($rr['site_sync'] === NULL_DATE) ? datetime_convert('UTC','UTC','now - 2 days') : $rr['site_sync']);
		$x = z_fetch_url($rr['site_directory'] . '?f=&sync=' . urlencode($syncdate) . (($token) ? '&t=' . $token : ''));

		if (! $x['success'])
			continue;

		$j = json_decode($x['body'],true);
		if (!($j['transactions']) || ($j['ratings']))
			continue;

		q("update site set site_sync = '%s' where site_url = '%s'",
			dbesc(datetime_convert()),
			dbesc($rr['site_url'])
		);

		logger('sync_directories: ' . $rr['site_url'] . ': ' . print_r($j,true), LOGGER_DATA);

		if (is_array($j['transactions']) && count($j['transactions'])) {
			foreach ($j['transactions'] as $t) {
				$r = q("select * from updates where ud_guid = '%s' limit 1",
					dbesc($t['transaction_id'])
				);
				if($r)
					continue;

				$ud_flags = 0;
				if (is_array($t['flags']) && in_array('deleted',$t['flags']))
					$ud_flags |= UPDATE_FLAGS_DELETED;
				if (is_array($t['flags']) && in_array('forced',$t['flags']))
					$ud_flags |= UPDATE_FLAGS_FORCED;

				$z = q("insert into updates ( ud_hash, ud_guid, ud_date, ud_flags, ud_addr )
					values ( '%s', '%s', '%s', %d, '%s' ) ",
					dbesc($t['hash']),
					dbesc($t['transaction_id']),
					dbesc($t['timestamp']),
					intval($ud_flags),
					dbesc($t['address'])
				);
			}
		}
		if (is_array($j['ratings']) && count($j['ratings'])) {
			foreach ($j['ratings'] as $rr) {
				$x = q("select * from xlink where xlink_xchan = '%s' and xlink_link = '%s' and xlink_static = 1",
					dbesc($rr['channel']),
					dbesc($rr['target'])
				);
				if ($x && $x[0]['xlink_updated'] >= $rr['edited'])
					continue;

				// Ratings are signed by the rater. We need to verify before we can accept it.
				/** @TODO Queue or defer if the xchan is not yet present on our site */

				$y = q("select xchan_pubkey from xchan where xchan_hash = '%s' limit 1",
					dbesc($rr['channel'])
				);
				if (! $y) {
					logger('key unavailable on this site for ' . $rr['channel']);
					continue;
				}
				if (! rsa_verify($rr['target'] . '.' . $rr['rating'] . '.' . $rr['rating_text'], base64url_decode($rr['signature']),$y[0]['xchan_pubkey'])) {
					logger('failed to verify rating');
					continue;
				}

				if ($x) {
					$z = q("update xlink set xlink_rating = %d, xlink_rating_text = '%s', xlink_sig = '%s', xlink_updated = '%s' where xlink_id = %d",
						intval($rr['rating']),
						dbesc($rr['rating_text']),
						dbesc($rr['signature']),
						dbesc(datetime_convert()),
						intval($x[0]['xlink_id'])
					);
					logger('rating updated');
				} else {
					$z = q("insert into xlink ( xlink_xchan, xlink_link, xlink_rating, xlink_rating_text, xlink_sig, xlink_updated, xlink_static ) values( '%s', '%s', %d, '%s', '%s', '%s', 1 ) ",
						dbesc($rr['channel']),
						dbesc($rr['target']),
						intval($rr['rating']),
						dbesc($rr['rating_text']),
						dbesc($rr['signature']),
						dbesc(datetime_convert())
					);
					logger('rating created');
				}
			}
		}
	}
}


/**
 * @brief
 *
 * Given an update record, probe the channel, grab a zot-info packet and refresh/sync the data.
 *
 * Ignore updating records marked as deleted.
 *
 * If successful, sets ud_last in the DB to the current datetime for this
 * reddress/webbie.
 *
 * @param array $ud Entry from update table
 */
function update_directory_entry($ud) {

	logger('update_directory_entry: ' . print_r($ud,true), LOGGER_DATA);

	if ($ud['ud_addr'] && (! ($ud['ud_flags'] & UPDATE_FLAGS_DELETED))) {
		$success = false;
		$x = zot_finger($ud['ud_addr'], '');
		if ($x['success']) {
			$j = json_decode($x['body'], true);
			if ($j)
				$success = true;

			$y = import_xchan($j, 0, $ud);
		}
		if (! $success) {
			q("update updates set ud_last = '%s' where ud_addr = '%s'",
				dbesc(datetime_convert()),
				dbesc($ud['ud_addr'])
			);
		}
	}
}


/**
 * @brief Push local channel updates to a local directory server.
 *
 * This is called from include/directory.php if a profile is to be pushed to the
 * directory and the local hub in this case is any kind of directory server.
 *
 * @param int $uid
 * @param boolean $force
 */
function local_dir_update($uid, $force) {

	logger('local_dir_update: uid: ' . $uid, LOGGER_DEBUG);

	$p = q("select channel.channel_hash, channel_address, channel_timezone, profile.* from profile left join channel on channel_id = uid where uid = %d and is_default = 1",
		intval($uid)
	);

	$profile = array();
	$profile['encoding'] = 'zot';

	if ($p) {
		$hash = $p[0]['channel_hash'];

		$profile['description'] = $p[0]['pdesc'];
		$profile['birthday']    = $p[0]['dob'];
		if ($age = age($p[0]['dob'],$p[0]['channel_timezone'],''))  
			$profile['age'] = $age;

		$profile['gender']      = $p[0]['gender'];
		$profile['marital']     = $p[0]['marital'];
		$profile['sexual']      = $p[0]['sexual'];
		$profile['locale']      = $p[0]['locality'];
		$profile['region']      = $p[0]['region'];
		$profile['postcode']    = $p[0]['postal_code'];
		$profile['country']     = $p[0]['country_name'];
		$profile['about']       = $p[0]['about'];
		$profile['homepage']    = $p[0]['homepage'];
		$profile['hometown']    = $p[0]['hometown'];

		if ($p[0]['keywords']) {
			$tags = array();
			$k = explode(' ', $p[0]['keywords']);
			if ($k)
				foreach ($k as $kk)
					if (trim($kk))
						$tags[] = trim($kk);

			if ($tags)
				$profile['keywords'] = $tags;
		}

		$hidden = (1 - intval($p[0]['publish']));

		logger('hidden: ' . $hidden);

		$r = q("select xchan_hidden from xchan where xchan_hash = '%s' limit 1",
			dbesc($p[0]['channel_hash'])
		);

		if(intval($r[0]['xchan_hidden']) != $hidden) {
			$r = q("update xchan set xchan_hidden = %d where xchan_hash = '%s'",
				intval($hidden),
				dbesc($p[0]['channel_hash'])
			);
		}

		$arr = array('channel_id' => $uid, 'hash' => $hash, 'profile' => $profile);
		call_hooks('local_dir_update', $arr);

		$address = $p[0]['channel_address'] . '@' . App::get_hostname();

		if (perm_is_allowed($uid, '', 'view_profile')) {
			import_directory_profile($hash, $arr['profile'], $address, 0);
		} else {
			// they may have made it private
			$r = q("delete from xprof where xprof_hash = '%s'",
				dbesc($hash)
			);
			$r = q("delete from xtag where xtag_hash = '%s'",
				dbesc($hash)
			);
		}
	}

	$ud_hash = random_string() . '@' . App::get_hostname();
	update_modtime($hash, $ud_hash, $p[0]['channel_address'] . '@' . App::get_hostname(),(($force) ? UPDATE_FLAGS_FORCED : UPDATE_FLAGS_UPDATED));
}
