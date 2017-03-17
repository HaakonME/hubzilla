<?php

namespace Zotlabs\Widget;

require_once('include/security.php');

class Item {

	function widget($arr) {

		$channel_id = 0;
		if(array_key_exists('channel_id',$arr) && intval($arr['channel_id']))
			$channel_id = intval($arr['channel_id']);
		if(! $channel_id)
			$channel_id = \App::$profile_uid;
		if(! $channel_id)
			return '';


		if((! $arr['mid']) && (! $arr['title']))
			return '';

		if(! perm_is_allowed($channel_id, get_observer_hash(), 'view_pages'))
			return '';

		$sql_extra = item_permissions_sql($channel_id);

		if($arr['title']) {
			$r = q("select item.* from item left join iconfig on item.id = iconfig.iid
				where item.uid = %d and iconfig.cat = 'system' and iconfig.v = '%s'
				and iconfig.k = 'WEBPAGE' and item_type = %d $sql_options $revision limit 1",
				intval($channel_id),
				dbesc($arr['title']),
				intval(ITEM_TYPE_WEBPAGE)
			);
		}
		else {
			$r = q("select * from item where mid = '%s' and uid = %d and item_type = " 
				. intval(ITEM_TYPE_WEBPAGE) . " $sql_extra limit 1",
				dbesc($arr['mid']),
				intval($channel_id)
			);
		}

		if(! $r)
			return '';

		xchan_query($r);
		$r = fetch_post_tags($r, true);

		$o = prepare_page($r[0]);
		return $o;
	}
}
