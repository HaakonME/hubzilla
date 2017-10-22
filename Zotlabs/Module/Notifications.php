<?php
namespace Zotlabs\Module;

require_once('include/bbcode.php');

class Notifications extends \Zotlabs\Web\Controller {

	function get() {
	
		if(! local_channel()) {
			notice( t('Permission denied.') . EOL);
			return;
		}
	
		nav_set_selected('Notifications');
	
		$o = '';

		$r = q("select count(*) as total from notify where uid = %d and seen = 0",
			intval(local_channel())
		);
		if($r && intval($t[0]['total']) > 49) {
			$r = q("select * from notify where uid = %d
				and seen = 0 order by created desc limit 50",
				intval(local_channel())
			);
		} else {
			$r1 = q("select * from notify where uid = %d
				and seen = 0 order by created desc limit 50",
				intval(local_channel())
			);
			$r2 = q("select * from notify where uid = %d
				and seen = 1 order by created desc limit %d",
				intval(local_channel()),
				intval(50 - intval($t[0]['total']))
			);
			$r = array_merge($r1,$r2);
		}
			
		if($r) {
			$notifications_available = 1;
			foreach ($r as $rr) {
				$x = strip_tags(bbcode($rr['msg']));
				if(strpos($x,','))
					$x = substr($x,strpos($x,',')+1);
				$notif_content .= replace_macros(get_markup_template('notify.tpl'),array(
					'$item_link' => z_root().'/notify/view/'. $rr['id'],
					'$item_image' => $rr['photo'],
					'$item_text' => $x,
					'$item_when' => relative_date($rr['created']),
					'$item_seen' => (($rr['seen']) ? true : false),
					'$new' => t('New')
				));
			}
		}
		else {
			$notif_content .= t('No more system notifications.');
		}
			
		$o .= replace_macros(get_markup_template('notifications.tpl'),array(
			'$notif_header' => t('System Notifications'),
			'$notif_link_mark_seen' => t('Mark all seen'),
			'$notif_content' => $notif_content,
			'$notifications_available' => $notifications_available,
		));
	
		return $o;
	}
	
}
