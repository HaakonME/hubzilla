<?php


namespace Zotlabs\Access;

use Zotlabs\Lib as Zlib;

class PermissionRoles {

	static private $role_limits = array();
	static private $role_perms  = array();

	static public function roles() {
	    $roles = [
			t('Social Networking') => [
				'social' => t('Social - Mostly Public'), 
				'social_restricted' => t('Social - Restricted'), 
				'social_private' => t('Social - Private')
			],

			t('Community Forum') => [
				'forum' => t('Forum - Mostly Public'), 
				'forum_restricted' => t('Forum - Restricted'), 
				'forum_private' => t('Forum - Private')
			],

			t('Feed Republish') => [
				'feed' => t('Feed - Mostly Public'), 
				'feed_restricted' => t('Feed - Restricted')
			],

			t('Special Purpose') => [
				'soapbox' => t('Special - Celebrity/Soapbox'), 
				'repository' => t('Special - Group Repository')
			],

			t('Other') => [
				'custom' => t('Custom/Expert Mode')
			]
    
		];

    	return $roles;
	}


	static public function LimitSet($permission,$limit,$roles) {
		if(is_array($roles)) {
			foreach($roles as $role) {
				self::$role_limits[$role][$permission] = $limit;
			}
		}
		else {
			self::$role_limits[$role][$permission] = $limit;
		}
	}		

	static public function PermSet($permission,$roles) {
		if(is_array($roles)) {
			foreach($roles as $role) {
				self::$role_perms[$role][] = $permission;
			}
		}
		else {
			self::$role_perms[$role][] = $permission;
		}
	}


}