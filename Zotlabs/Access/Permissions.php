<?php


namespace Zotlabs\Access;

use Zotlabs\Lib as Zlib;

class Permissions {

	static public function Perms($filter = '') {

		$perms = [
			[ 'view_stream'   => t('Can view my normal stream and posts') ],
			[ 'send_stream'   => t('Can send me their channel stream and posts') ],
			[ 'view_profile'  => t('Can view my default channel profile') ],
			[ 'view_contacts' => t('Can view my connections') ],
			[ 'view_storage'  => t('Can view my file storage and photos') ],
			[ 'write_storage' => t('Can upload/modify my file storage and photos') ],
			[ 'view_pages'    => t('Can view my channel webpages') ],
			[ 'write_pages'   => t('Can create/edit my channel webpages') ],
			[ 'post_wall'     => t('Can post on my channel (wall) page') ],
			[ 'post_comments' => t('Can comment on or like my posts') ],
			[ 'post_mail'     => t('Can send me private mail messages') ],
			[ 'post_like'     => t('Can like/dislike profiles and profile things') ],
			[ 'tag_deliver'   => t('Can forward to all my channel connections via @+ mentions in posts') ],
			[ 'chat'          => t('Can chat with me (when available)') ],
			[ 'republish'     => t('Can source my public posts in derived channels') ],
			[ 'delegate'      => t('Can administer my channel') ]
		];

		$x = array('permissions' => $perms, 'filter' => $filter);
		call_hooks('permissions_list',$x);
		return($x['permissions']);

	}

	static public function BlockedAnonPerms() {

		// Perms from the above list that are blocked from anonymous observers.
		// e.g. you must be authenticated.

		$perms = [ 'send_stream', 'write_pages', 'post_wall', 'write_storage', 'post_comments', 'post_mail', 'post_like', 'tag_deliver', 'chat', 'republish', 'delegate' ];

		$x = array('permissions' => $perms);
		call_hooks('write_perms',$x);
		return($x['permissions']);

	}


	static public function OwnerLimitSet($channel_id,$permission,$limit) {
		return Zlib\PConfig::Set($channel_id,'perms',$permission,$limit);
	}

	static public function OwnerLimitGet($channel_id,$permission) {
		return Zlib\PConfig::Get($channel_id,'perms',$permission);
	}

	static public function Set($channel_id,$xchan_hash,$permission,$value) {
		return Zlib\AbConfig::Set($channel_id,$xchan_hash,'perms',$permission,$value);
	}
		
	static public function Get($channel_id,$xchan_hash,$permission) {
		return Zlib\AbConfig::Get($channel_id,$xchan_hash,'perms',$permission);
	}

}