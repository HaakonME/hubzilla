<?php

namespace Zotlabs\Access;

use Zotlabs\Lib as Zlib;

/**
 * @brief Extensible permissions.
 *
 * To add new permissions, add to the list of $perms below, with a simple description.
 *
 * Also visit PermissionRoles.php and add to the $ret['perms_connect'] property for any role
 * if this permission should be granted to new connections.
 *
 * Next look at PermissionRoles::new_custom_perms() and provide a handler for updating custom
 * permission roles. You will want to set a default PermissionLimit for each channel and also
 * provide a sane default for any existing connections. You may or may not wish to provide a
 * default auto permission. If in doubt, leave this alone as custom permissions by definition
 * are the responsibility of the channel owner to manage. You just don't want to create any
 * suprises or break things so you have an opportunity to provide sane settings.
 *
 * Update the version here and in PermissionRoles.
 *
 *
 * Permissions with 'view' in the name are considered read permissions. Anything
 * else requires authentication. Read permission limits are PERMS_PUBLIC and anything else
 * is given PERMS_SPECIFIC.
 *
 * PermissionLimits::Std_limits() retrieves the standard limits. A permission role
 * MAY alter an individual setting after retrieving the Std_limits if you require
 * something different for a specific permission within the given role.
 *
 */
class Permissions {

	static public function version() {
		// This must match the version in PermissionRoles.php before permission updates can run.
		return 2;
	}

	/**
	 * @brief Return an array with Permissions.
	 *
	 * @hooks permissions_list
	 *   * \e array \b permissions
	 *   * \e string \b filter
	 * @param string $filter (optional) only passed to hook permission_list
	 * @return Associative array with permissions and short description.
	 */
	static public function Perms($filter = '') {

		$perms = [
			'view_stream'   => t('Can view my channel stream and posts'),
			'send_stream'   => t('Can send me their channel stream and posts'),
			'view_profile'  => t('Can view my default channel profile'),
			'view_contacts' => t('Can view my connections'),
			'view_storage'  => t('Can view my file storage and photos'),
			'write_storage' => t('Can upload/modify my file storage and photos'),
			'view_pages'    => t('Can view my channel webpages'),
			'view_wiki'     => t('Can view my wiki pages'),
			'write_pages'   => t('Can create/edit my channel webpages'),
			'write_wiki'    => t('Can write to my wiki pages'),
			'post_wall'     => t('Can post on my channel (wall) page'),
			'post_comments' => t('Can comment on or like my posts'),
			'post_mail'     => t('Can send me private mail messages'),
			'post_like'     => t('Can like/dislike profiles and profile things'),
			'tag_deliver'   => t('Can forward to all my channel connections via @+ mentions in posts'),
			'chat'          => t('Can chat with me'),
			'republish'     => t('Can source my public posts in derived channels'),
			'delegate'      => t('Can administer my channel')
		];

		$x = [
			'permissions' => $perms,
			'filter' => $filter
		];
		call_hooks('permissions_list', $x);

		return($x['permissions']);
	}

	/**
	 * @brief Perms from the above list that are blocked from anonymous observers.
	 *
	 * e.g. you must be authenticated.
	 *
	 * @hooks write_perms
	 *   * \e array \b permissions
	 * @return Associative array with permissions and short description.
	 */
	static public function BlockedAnonPerms() {

		$res = [];
		$perms = PermissionLimits::Std_limits();
		foreach($perms as $perm => $limit) {
			if($limit != PERMS_PUBLIC) {
				$res[] = $perm;
			}
		}

		$x = ['permissions' => $res];
		call_hooks('write_perms', $x);

		return($x['permissions']);
	}

	/**
	 * @brief Converts indexed perms array to associative perms array.
	 *
	 * Converts [ 0 => 'view_stream', ... ]
	 * to [ 'view_stream' => 1 ] for any permissions in $arr;
	 * Undeclared permissions which exist in Perms() are added and set to 0.
 	 *
	 * @param array $arr
	 * @return array
	 */
	static public function FilledPerms($arr) {
		if(is_null($arr)) {
			btlogger('FilledPerms: null');
		}

		$everything = self::Perms();
		$ret = [];
		foreach($everything as $k => $v) {
			if(in_array($k, $arr))
				$ret[$k] = 1;
			else
				$ret[$k] = 0;
		}

		return $ret;
	}

	/**
	 * @brief Convert perms array to indexed array.
	 *
	 * Converts [ 'view_stream' => 1 ] for any permissions in $arr
	 * to [ 0 => ['name' => 'view_stream', 'value' => 1], ... ]
	 *
	 * @param array $arr associative perms array 'view_stream' => 1
	 * @return Indexed array with elements that look like
	 *   * \e string \b name the perm name (e.g. view_stream)
	 *   * \e int \b value the value of the perm (e.g. 1)
	 */
	static public function OPerms($arr) {
		$ret = [];
		if($arr) {
			foreach($arr as $k => $v) {
				$ret[] = [ 'name' => $k, 'value' => $v ];
			}
		}
		return $ret;
	}

	/**
	 * @brief
	 *
	 * @param int $channel_id
	 * @return boolean|array
	 */
	static public function FilledAutoperms($channel_id) {
		if(! intval(get_pconfig($channel_id,'system','autoperms')))
			return false;

		$arr = [];
		$r = q("select * from pconfig where uid = %d and cat = 'autoperms'",
			intval($channel_id)
		);
		if($r) {
			foreach($r as $rr) {
				$arr[$rr['k']] = intval($rr['v']);
			}
		}
		return $arr;
	}

	/**
	 * @brief Compares that all Permissions from $p1 exist also in $p2.
	 *
	 * @param array $p1 The perms that have to exist in $p2
	 * @param array $p2 The perms to compare against
	 * @return boolean true if all perms from $p1 exist also in $p2
	 */
	static public function PermsCompare($p1, $p2) {
		foreach($p1 as $k => $v) {
			if(! array_key_exists($k, $p2))
				return false;

			if($p1[$k] != $p2[$k])
				return false;
		}

		return true;
	}

	/**
	 * @brief
	 *
	 * @param int $channel_id A channel id
	 * @return associative array
	 *   * \e array \b perms Permission array
	 *   * \e int \b automatic 0 or 1
	 */

	static public function connect_perms($channel_id) {

		$my_perms = [];
		$permcat = null;
		$automatic = 0;

		// If a default permcat exists, use that

		$pc = ((feature_enabled($channel_id,'permcats')) ? get_pconfig($channel_id,'system','default_permcat') : 'default');
		if(! in_array($pc, [ '','default' ])) {
			$pcp = new Zlib\Permcat($channel_id);
			$permcat = $pcp->fetch($pc);
			if($permcat && $permcat['perms']) {
				foreach($permcat['perms'] as $p) {
					$my_perms[$p['name']] = $p['value'];
				}
			}
		}

		// look up the permission role to see if it specified auto-connect
		// and if there was no permcat or a default permcat, set the perms
		// from the role

		$role = get_pconfig($channel_id,'system','permissions_role');
		if($role) {
			$xx = PermissionRoles::role_perms($role);
			if($xx['perms_auto'])
				$automatic = 1;

			if((! $my_perms) && ($xx['perms_connect'])) {
				$default_perms = $xx['perms_connect'];
				$my_perms = Permissions::FilledPerms($default_perms);
			}
		}

		// If we reached this point without having any permission information,
		// it is likely a custom permissions role. First see if there are any
		// automatic permissions.

		if(! $my_perms) {
			$m = Permissions::FilledAutoperms($channel_id);
			if($m) {
				$automatic = 1;
				$my_perms = $m;
			}
		}

		// If we reached this point with no permissions, the channel is using
		// custom perms but they are not automatic. They will be stored in abconfig with
		// the channel's channel_hash (the 'self' connection).

		if(! $my_perms) {
			$r = q("select channel_hash from channel where channel_id = %d",
				intval($channel_id)
			);
			if($r) {
				$x = q("select * from abconfig where chan = %d and xchan = '%s' and cat = 'my_perms'",
					intval($channel_id),
					dbesc($r[0]['channel_hash'])
				);
				if($x) {
					foreach($x as $xv) {
						$my_perms[$xv['k']] = intval($xv['v']);
					}
				}
			}
		}

		return ( [ 'perms' => $my_perms, 'automatic' => $automatic ] );
	}

}