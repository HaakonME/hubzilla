<?php


namespace Zotlabs\Access;

use Zotlabs\Lib as Zlib;

class Permissions {


	static public function Perms($filter) {

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

	static public function OwnerLimitSet($channel_id,$permission,$limit) {
		return Zlib\PConfig::Set($channel_id,'perms',$permission,$limit);
	}

	static public function OwnerLimitGet($channel_id,$permission) {
		return Zlib\PConfig::Get($channel_id,'perms',$permission);
	}


	static public function Set($channel_id,$xchan_hash,$permission,$value) {
		$channel = channelx_by_n($channel_id);
		if($channel) {
			return Zlib\AbConfig::Set($channel['channel_hash'],$xchan_hash,'perms',$permission,$value);
		}
		return false;
	}
		
	static public function Get($channel_id,$xchan_hash,$permission) {
		$channel = channelx_by_n($channel_id);
		if($channel) {
			return Zlib\AbConfig::Get($channel['channel_hash'],$xchan_hash,'perms',$permission);
		}
		return false;
	}

	static public function SetHash($channel_hash,$xchan_hash,$permission,$value) {
		return Zlib\AbConfig::Set($channel_hash,$xchan_hash,'perms',$permission,$value);
	}
		
	static public function GetHash($channel_hash,$xchan_hash,$permission) {
		return Zlib\AbConfig::Get($channel_hash,$xchan_hash,'perms',$permission);
	}











}