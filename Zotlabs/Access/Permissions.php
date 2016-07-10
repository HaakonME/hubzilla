<?php


namespace Zotlabs\Access;

use Zotlabs\Lib as Zlib;

class Permissions {

	/**
	 * Extensible permissions.
	 * To add new permissions, add to the list of $perms below, with a simple description.
	 * Also visit PermissionRoles.php and add to the $ret['perms_connect'] property for any role
	 * if this permission should be granted to new connections.
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


	static public function Perms($filter = '') {

		$perms = [
			'view_stream'   => t('Can view my channel stream and posts'),
			'send_stream'   => t('Can send me their channel stream and posts'),
			'view_profile'  => t('Can view my default channel profile'),
			'view_contacts' => t('Can view my connections'),
			'view_storage'  => t('Can view my file storage and photos'),
			'write_storage' => t('Can upload/modify my file storage and photos'),
			'view_pages'    => t('Can view my channel webpages'),
			'write_pages'   => t('Can create/edit my channel webpages'),
			'post_wall'     => t('Can post on my channel (wall) page'),
			'post_comments' => t('Can comment on or like my posts'),
			'post_mail'     => t('Can send me private mail messages'),
			'post_like'     => t('Can like/dislike profiles and profile things'),
			'tag_deliver'   => t('Can forward to all my channel connections via @+ mentions in posts'),
			'chat'          => t('Can chat with me'),
			'republish'     => t('Can source my public posts in derived channels'),
			'delegate'      => t('Can administer my channel')
		];

		$x = array('permissions' => $perms, 'filter' => $filter);
		call_hooks('permissions_list',$x);
		return($x['permissions']);

	}

	static public function BlockedAnonPerms() {

		// Perms from the above list that are blocked from anonymous observers.
		// e.g. you must be authenticated.

		$res = array();
		$perms = PermissionLimits::Std_limits();
		foreach($perms as $perm => $limit) {
			if($limit != PERMS_PUBLIC) {
				$res[] = $perm;
			}
		}

		$x = array('permissions' => $res);
		call_hooks('write_perms',$x);
		return($x['permissions']);

	}


}