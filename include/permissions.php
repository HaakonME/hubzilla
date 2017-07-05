<?php

require_once('include/security.php');

/**
 * @file include/permissions.php
 *
 * This file conntains functions to check and work with permissions.
 * 
 */



/**
 * get_all_perms($uid,$observer_xchan)
 *
 * @param int $uid The channel_id associated with the resource owner
 * @param string $observer_xchan The xchan_hash representing the observer
 * @param bool $internal_use (default true)
 *
 * @returns array of all permissions, key is permission name, value is true or false
 */
function get_all_perms($uid, $observer_xchan, $internal_use = true) {

	$api = App::get_oauth_key();
	if($api)
		return get_all_api_perms($uid,$api);	

	$global_perms = \Zotlabs\Access\Permissions::Perms();

	// Save lots of individual lookups

	$r = null;
	$c = null;
	$x = null;

	$channel_checked = false;
	$onsite_checked  = false;
	$abook_checked   = false;

	$ret = array();

	$abperms = (($uid && $observer_xchan) ? load_abconfig($uid,$observer_xchan,'my_perms') : array());

	foreach($global_perms as $perm_name => $permission) {

		// First find out what the channel owner declared permissions to be.

		$channel_perm = \Zotlabs\Access\PermissionLimits::Get($uid,$perm_name);

		if(! $channel_checked) {
			$r = q("select * from channel where channel_id = %d limit 1",
				intval($uid)
			);
			$channel_checked = true;
		}

		// The uid provided doesn't exist. This would be a big fail.

		if(! $r) {
			$ret[$perm_name] = false;
			continue;
		}

		// Next we're going to check for blocked or ignored contacts.
		// These take priority over all other settings.

		if($observer_xchan) {
			if($channel_perm & PERMS_AUTHED) {
				$ret[$perm_name] = true;
				continue;
			}

			if(! $abook_checked) {
				$x = q("select abook_my_perms, abook_blocked, abook_ignored, abook_pending, xchan_network from abook left join xchan on abook_xchan = xchan_hash
					where abook_channel = %d and abook_xchan = '%s' and abook_self = 0 limit 1",
					intval($uid),
					dbesc($observer_xchan)
				);
				if(! $x) {
					// see if they've got a guest access token; these are treated as connections
					$y = atoken_abook($uid,$observer_xchan);
					if($y)
						$x = array($y);

					if(! $x) {
						// not in address book and no guest token, see if they've got an xchan
						// these *may* have individual (PERMS_SPECIFIC) permissions, but are not connections
						$y = q("select xchan_network from xchan where xchan_hash = '%s' limit 1",
							dbesc($observer_xchan)
						);
						if($y) {
							$x = array(pseudo_abook($y[0]));
						}
					}
				}

				$abook_checked = true;
			}

			// If they're blocked - they can't read or write

			if(($x) && intval($x[0]['abook_blocked'])) {
				$ret[$perm_name] = false;
				continue;
			}

			// Check if this is a write permission and they are being ignored
			// This flag is only visible internally.

			$blocked_anon_perms = \Zotlabs\Access\Permissions::BlockedAnonPerms();


			if(($x) && ($internal_use) && in_array($perm_name,$blocked_anon_perms) && intval($x[0]['abook_ignored'])) {
				$ret[$perm_name] = false;
				continue;
			}
		}

		// system is blocked to anybody who is not authenticated

		if((! $observer_xchan) && intval(get_config('system', 'block_public'))) {
			$ret[$perm_name] = false;
			continue;
		}

		// Check if this $uid is actually the $observer_xchan - if it's your content
		// you always have permission to do anything
		// if you've moved elsewhere, you will only have read only access

		if(($observer_xchan) && ($r[0]['channel_hash'] === $observer_xchan)) {
			if($r[0]['channel_moved'] && (in_array($perm_name,$blocked_anon_perms)))
				$ret[$perm_name] = false;
			else
				$ret[$perm_name] = true;
			continue;
		}

		// Anybody at all (that wasn't blocked or ignored). They have permission.

		if($channel_perm & PERMS_PUBLIC) {
			$ret[$perm_name] = true;
			continue;
		}

		// From here on out, we need to know who they are. If we can't figure it
		// out, permission is denied.

		if(! $observer_xchan) {
			$ret[$perm_name] = false;
			continue;
		}

		// If we're still here, we have an observer, check the network.

		if($channel_perm & PERMS_NETWORK) {
			if($x && $x[0]['xchan_network'] === 'zot') {
				$ret[$perm_name] = true;
				continue;
			}
		}

		// If PERMS_SITE is specified, find out if they've got an account on this hub

		if($channel_perm & PERMS_SITE) {
			if(! $onsite_checked) {
				$c = q("select channel_hash from channel where channel_hash = '%s' limit 1",
					dbesc($observer_xchan)
				);

				$onsite_checked = true;
			}

			if($c)
				$ret[$perm_name] = true;
			else
				$ret[$perm_name] = false;

			continue;
		}

		// From here on we require that the observer be a connection and
		// handle whether we're allowing any, approved or specific ones

		if(! $x) {
			$ret[$perm_name] = false;
			continue;
		}

		// They are in your address book, but haven't been approved

		if($channel_perm & PERMS_PENDING) {
			$ret[$perm_name] = true;
			continue;
		}

		if(intval($x[0]['abook_pending'])) {
			$ret[$perm_name] = false;
			continue;
		}

		// They're a contact, so they have permission

		if($channel_perm & PERMS_CONTACTS) {
			// it was a fake abook entry, not really a connection
			if(array_key_exists('abook_pseudo',$x[0]) && intval($x[0]['abook_pseudo'])) {
				$ret[$perm_name] = false;
				continue;
			}
				
			$ret[$perm_name] = true;
			continue;
		}

		// Permission granted to certain channels. Let's see if the observer is one of them

		if($channel_perm & PERMS_SPECIFIC) {
			if($abperms) {
				foreach($abperms as $ab) {
					if(($ab['cat'] == 'my_perms') && ($ab['k'] == $perm_name)) {
						$ret[$perm_name] = (intval($ab['v']) ? true : false);
						break;
					}
				}
				continue;
			}
		}

		// No permissions allowed.

		$ret[$perm_name] = false;
		continue;
	}

	$arr = array(
		'channel_id'    => $uid,
		'observer_hash' => $observer_xchan,
		'permissions'   => $ret);

	call_hooks('get_all_perms',$arr);

	return $arr['permissions'];
}

/**
 * @brief Checks if given permission is allowed for given observer on a channel.
 *
 * Checks if the given observer with the hash $observer_xchan has permission
 * $permission on channel_id $uid.
 *
 * @param int $uid The channel_id associated with the resource owner
 * @param string $observer_xchan The xchan_hash representing the observer
 * @param string $permission
 * @return bool true if permission is allowed for observer on channel
 */
function perm_is_allowed($uid, $observer_xchan, $permission) {

	$api = App::get_oauth_key();
	if($api)
		return api_perm_is_allowed($uid,$api,$permission);

	$arr = array(
		'channel_id'    => $uid,
		'observer_hash' => $observer_xchan,
		'permission'    => $permission,
		'result'        => 'unset');

	call_hooks('perm_is_allowed', $arr);
	if($arr['result'] !== 'unset') {
		return $arr['result'];
	}

	$global_perms = \Zotlabs\Access\Permissions::Perms();

	// First find out what the channel owner declared permissions to be.

	$channel_perm = \Zotlabs\Access\PermissionLimits::Get($uid,$permission);

	$r = q("select channel_pageflags, channel_moved, channel_hash from channel where channel_id = %d limit 1",
		intval($uid)
	);
	if(! $r)
		return false;


	$blocked_anon_perms = \Zotlabs\Access\Permissions::BlockedAnonPerms();

	if($observer_xchan) {
		if($channel_perm & PERMS_AUTHED)
			return true;

		$x = q("select abook_my_perms, abook_blocked, abook_ignored, abook_pending, xchan_network from abook left join xchan on abook_xchan = xchan_hash 
			where abook_channel = %d and abook_xchan = '%s' and abook_self = 0 limit 1",
			intval($uid),
			dbesc($observer_xchan)
		);

		// If they're blocked - they can't read or write
 
		if(($x) && intval($x[0]['abook_blocked']))
			return false;

		if(($x) && in_array($permission,$blocked_anon_perms) && intval($x[0]['abook_ignored']))
			return false;

		if(! $x) {
			// see if they've got a guest access token
			$y = atoken_abook($uid,$observer_xchan);
			if($y)
				$x = array($y);

			if(! $x) {
				// not in address book and no guest token, see if they've got an xchan
				$y = q("select xchan_network from xchan where xchan_hash = '%s' limit 1",
					dbesc($observer_xchan)
				);
				if($y) {
					$x = array(pseudo_abook($y[0]));
				}
			}

		}
		$abperms = load_abconfig($uid,$observer_xchan,'my_perms');
	}
	

	// system is blocked to anybody who is not authenticated

	if((! $observer_xchan) && intval(get_config('system', 'block_public')))
		return false;

	// Check if this $uid is actually the $observer_xchan
	// you will have full access unless the channel was moved - 
	// in which case you will have read_only access

	if($r[0]['channel_hash'] === $observer_xchan) {
		if($r[0]['channel_moved'] && (in_array($permission,$blocked_anon_perms)))
			return false;
		else
			return true;
	}

	if($channel_perm & PERMS_PUBLIC)
		return true;

	// If it's an unauthenticated observer, we only need to see if PERMS_PUBLIC is set

	if(! $observer_xchan) {
		return false;
	}

	// If we're still here, we have an observer, check the network.

	if($channel_perm & PERMS_NETWORK) {
		if (($x && $x[0]['xchan_network'] === 'zot') || ($y && $y[0]['xchan_network'] === 'zot'))
			return true;
	}

	// If PERMS_SITE is specified, find out if they've got an account on this hub

	if($channel_perm & PERMS_SITE) {
		$c = q("select channel_hash from channel where channel_hash = '%s' limit 1",
			dbesc($observer_xchan)
		);
		if($c)
			return true;

		return false;
	}

	// From here on we require that the observer be a connection and
	// handle whether we're allowing any, approved or specific ones

	if(! $x) {
		return false;
	}

	// They are in your address book, but haven't been approved

	if($channel_perm & PERMS_PENDING) {
		return true;
	}

	if(intval($x[0]['abook_pending'])) {
		return false;
	}

	// They're a contact, so they have permission

	if($channel_perm & PERMS_CONTACTS) {
		// it was a fake abook entry, not really a connection
		if(array_key_exists('abook_pseudo',$x[0]) && intval($x[0]['abook_pseudo'])) {
			return false;
		}
		return true;
	}

	// Permission granted to certain channels. Let's see if the observer is one of them

	if(($r) && ($channel_perm & PERMS_SPECIFIC)) {
		if($abperms) {
			foreach($abperms as $ab) {
				if($ab['cat'] == 'my_perms' && $ab['k'] == $permission) {
					return ((intval($ab['v'])) ? true : false);
				}
			}
		}
	}

	// No permissions allowed.

	return false;
}

function get_all_api_perms($uid,$api) {	

	$global_perms = \Zotlabs\Access\Permissions::Perms();

	$ret = array();

	$r = q("select * from xperm where xp_client = '%s' and xp_channel = %d",
		dbesc($api),
		intval($uid)
	);

	if(! $r)
		return false;

	$allow_all = false;
	$allowed = array();
	foreach($r as $rr) {
		if($rr['xp_perm'] === 'all')
			$allow_all = true;
		if(! in_array($rr['xp_perm'],$allowed))
			$allowed[] = $rr['xp_perm'];
	}

	foreach($global_perms as $perm_name => $permission) {
		if($allow_all || in_array($perm_name,$allowed))
			$ret[$perm_name] = true;
		else
			$ret[$perm_name] = false;

	}

	$arr = array(
		'channel_id'    => $uid,
		'observer_hash' => $observer_xchan,
		'permissions'   => $ret);

	call_hooks('get_all_api_perms',$arr);

	return $arr['permissions'];

}


function api_perm_is_allowed($uid,$api,$permission) {

	$arr = array(
		'channel_id'    => $uid,
		'observer_hash' => $observer_xchan,
		'permission'    => $permission,
		'result'        => false
	);

	call_hooks('api_perm_is_allowed', $arr);
	if($arr['result'])
		return true;

	$r = q("select * from xperm where xp_client = '%s' and xp_channel = %d and ( xp_perm = 'all' OR xp_perm = '%s' )",
		dbesc($api),
		intval($uid),
		dbesc($permission)
	);

	if(! $r)
		return false;

	foreach($r as $rr) {
		if($rr['xp_perm'] === 'all' || $rr['xp_perm'] === $permission)
			return true;

	}

	return false;

}



// Check a simple array of observers against a permissions
// return a simple array of those with permission

function check_list_permissions($uid, $arr, $perm) {
	$result = array();
	if($arr)
		foreach($arr as $x)
			if(perm_is_allowed($uid, $x, $perm))
				$result[] = $x;

	return($result);
}

/**
 * @brief Sets site wide default permissions.
 *
 * @return array
 */
function site_default_perms() {

	$ret = array();

	$typical = array(
		'view_stream'   => PERMS_PUBLIC,
		'view_profile'  => PERMS_PUBLIC,
		'view_contacts' => PERMS_PUBLIC,
		'view_storage'  => PERMS_PUBLIC,
		'view_pages'    => PERMS_PUBLIC,
		'view_wiki'     => PERMS_PUBLIC,
		'send_stream'   => PERMS_SPECIFIC,
		'post_wall'     => PERMS_SPECIFIC,
		'post_comments' => PERMS_SPECIFIC,
		'post_mail'     => PERMS_SPECIFIC,
		'tag_deliver'   => PERMS_SPECIFIC,
		'chat'          => PERMS_SPECIFIC,
		'write_storage' => PERMS_SPECIFIC,
		'write_pages'   => PERMS_SPECIFIC,
		'write_wiki'    => PERMS_SPECIFIC,
		'delegate'      => PERMS_SPECIFIC,
		'post_like'     => PERMS_NETWORK
	);

	$global_perms = \Zotlabs\Access\Permissions::Perms();

	foreach($global_perms as $perm => $v) {
		$x = get_config('default_perms', $perm, $typical[$perm]);
		$ret[$perm] = $x;
	}

	return $ret;
}


