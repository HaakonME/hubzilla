<?php

namespace Zotlabs\Widget;

class Random_block {

	function widget($arr) {

		$channel_id = 0;
		if(array_key_exists('channel_id',$arr) && intval($arr['channel_id']))
			$channel_id = intval($arr['channel_id']);
		if(! $channel_id)
			$channel_id = \App::$profile_uid;
		if(! $channel_id)
			return '';

		if(array_key_exists('contains',$arr))
			$contains = $arr['contains'];

		$o = '';

		require_once('include/security.php');
		$sql_options = item_permissions_sql($channel_id);

		$randfunc = db_getfunc('RAND');

		$r = q("select item.* from item left join iconfig on item.id = iconfig.iid
			where item.uid = %d and iconfig.cat = 'system' and iconfig.v like '%s' and iconfig.k = 'BUILDBLOCK' and
			item_type = %d $sql_options order by $randfunc limit 1",
			intval($channel_id),
			dbesc('%' . $contains . '%'),
			intval(ITEM_TYPE_BLOCK)
		);

		if($r) {
			$o = '<div class="widget bblock">';
			if($r[0]['title'])
				$o .= '<h3>' . $r[0]['title'] . '</h3>';

			$o .= prepare_text($r[0]['body'],$r[0]['mimetype']);
			$o .= '</div>';
		}

		return $o;
	}
}
