<?php
namespace Zotlabs\Module;

require_once('include/bbcode.php');

class Notifications extends \Zotlabs\Web\Controller {

	function get() {
	
		if(! local_channel()) {
			notice( t('Permission denied.') . EOL);
			return;
		}
	
		nav_set_selected('notifications');		
	
		$o = '';
		
		$r = q("SELECT * from notify where uid = %d and seen = 0 order by created desc",
			intval(local_channel())
		);
			
		if($r) {
			$notifications_available = 1;
			foreach ($r as $it) {
				$x = strip_tags(bbcode($it['msg']));
				if(strpos($x,','))
					$x = substr($x,strpos($x,',')+1);
				$notif_content .= replace_macros(get_markup_template('notify.tpl'),array(
					'$item_link' => z_root().'/notify/view/'. $it['id'],
					'$item_image' => $it['photo'],
					'$item_text' => $x,
					'$item_when' => relative_date($it['created'])
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
