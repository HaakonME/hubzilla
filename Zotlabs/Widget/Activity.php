<?php

namespace Zotlabs\Widget;

class Activity {

	function widget($arr) {

		if(! local_channel())
			return '';

		$o = '';

		if(is_array($arr) && array_key_exists('limit',$arr))
			$limit = " limit " . intval($limit) . " ";
		else
			$limit = '';

		$perms_sql = item_permissions_sql(local_channel()) . item_normal();

		$r = q("select author_xchan from item where item_unseen = 1 and uid = %d $perms_sql",
			intval(local_channel())
		);

		$contributors = [];
		$arr = [];

		if($r) {
			foreach($r as $rv) {
				if(array_key_exists($rv['author_xchan'],$contributors)) {
					$contributors[$rv['author_xchan']] ++;
				}
				else {
					$contributors[$rv['author_xchan']] = 1;
				}
			}
			foreach($contributors as $k => $v) {
				$arr[] = [ 'author_xchan' => $k, 'total' => $v	];	
			}
			usort($arr,'total_sort');
			xchan_query($arr);
		}

		$x = [ 'entries' => $arr ];
		call_hooks('activity_widget',$x);
		$arr = $x['entries']; 

		if($arr) {
			$o .= '<div class="widget">';
			$o .= '<h3>' . t('Activity','widget') . '</h3><ul class="nav nav-pills nav-stacked">';

			foreach($arr as $rv) {
				$o .= '<li><a href="network?f=&xchan=' . urlencode($rv['author_xchan']) . '" ><span class="badge pull-right">' . ((intval($rv['total'])) ? intval($rv['total']) : '') . '</span><img src="' . $rv['author']['xchan_photo_s'] . '" style="width: 16px; height: 16px;" /> ' . $rv['author']['xchan_name'] . '</a></li>';
			}
			$o .= '</ul></div>';
		}
		return $o;
	}

}	

