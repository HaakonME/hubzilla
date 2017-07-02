<?php

namespace Zotlabs\Widget;

class Forums {

	function widget($arr) {

		if(! local_channel())
			return '';

		$o = '';

		if(is_array($arr) && array_key_exists('limit',$arr))
			$limit = " limit " . intval($limit) . " ";
		else
			$limit = '';

		$unseen = 0;
		if(is_array($arr) && array_key_exists('unseen',$arr) && intval($arr['unseen']))
			$unseen = 1;

		$perms_sql = item_permissions_sql(local_channel()) . item_normal();

		$xf = false;

		$x1 = q("select xchan from abconfig where chan = %d and cat = 'their_perms' and k = 'send_stream' and v = '0'",
			intval(local_channel())
		);
		if($x1) {
			$xc = ids_to_querystr($x1,'xchan',true);
			$x2 = q("select xchan from abconfig where chan = %d and cat = 'their_perms' and k = 'tag_deliver' and v = '1' and xchan in (" . $xc . ") ",
				intval(local_channel())
			);
			if($x2)
				$xf = ids_to_querystr($x2,'xchan',true);
		}

		$sql_extra = (($xf) ? " and ( xchan_hash in (" . $xf . ") or xchan_pubforum = 1 ) " : " and xchan_pubforum = 1 "); 

		$r1 = q("select abook_id, xchan_hash, xchan_name, xchan_url, xchan_photo_s from abook left join xchan on abook_xchan = xchan_hash where xchan_deleted = 0 and abook_channel = %d $sql_extra order by xchan_name $limit ",
			intval(local_channel())
		);
		if(! $r1)
			return $o;

		$str = '';

		// Trying to cram all this into a single query with joins and the proper group by's is tough.
		// There also should be a way to update this via ajax.

		for($x = 0; $x < count($r1); $x ++) {
			$r = q("select sum(item_unseen) as unseen from item where owner_xchan = '%s' and uid = %d and item_unseen = 1 $perms_sql ",
				dbesc($r1[$x]['xchan_hash']),
				intval(local_channel())
			);
			if($r)
				$r1[$x]['unseen'] = $r[0]['unseen'];

		/**
		 * @FIXME
		 * This SQL makes the counts correct when you get forum posts arriving from different routes/sources
		 * (like personal channels). However the network query for these posts doesn't yet include this
		 * correction and it makes the SQL for that query pretty hairy so this is left as a future exercise.
		 * It may make more sense in that query to look for the mention in the body rather than another join,
		 * but that makes it very inefficient.
		 *
		$r = q("select sum(item_unseen) as unseen from item left join term on oid = id where otype = %d and owner_xchan != '%s' and item.uid = %d and url = '%s' and ttype = %d $perms_sql ",
			intval(TERM_OBJ_POST),
			dbesc($r1[$x]['xchan_hash']),
			intval(local_channel()),
			dbesc($r1[$x]['xchan_url']),
			intval(TERM_MENTION)
		);
		if($r)
			$r1[$x]['unseen'] = ((array_key_exists('unseen',$r1[$x])) ? $r1[$x]['unseen'] + $r[0]['unseen'] : $r[0]['unseen']);
		 *
		 * end @FIXME
		 */

		}

		if($r1) {
			$o .= '<div class="widget">';
			$o .= '<h3>' . t('Forums') . '</h3><ul class="nav nav-pills flex-column">';

			foreach($r1 as $rr) {
				if($unseen && (! intval($rr['unseen'])))
					continue;
				$o .= '<li class="nav-item"><a class="nav-link" href="network?f=&pf=1&cid=' . $rr['abook_id'] . '" ><span class="badge badge-secondary float-right">' . ((intval($rr['unseen'])) ? intval($rr['unseen']) : '') . '</span><img class ="menu-img-1" src="' . $rr['xchan_photo_s'] . '" /> ' . $rr['xchan_name'] . '</a></li>';
			}
			$o .= '</ul></div>';
		}
		return $o;

	}
}
