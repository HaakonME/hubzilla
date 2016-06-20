<?php
namespace Zotlabs\Module;


class Notifications extends \Zotlabs\Web\Controller {

	function post() {
	
		if(! local_channel()) {
			goaway(z_root());
		}
		
		$request_id = ((\App::$argc > 1) ? \App::$argv[1] : 0);
		
		if($request_id === "all")
			return;
	
		if($request_id) {
	
			$r = q("SELECT * FROM `intro` WHERE `id` = %d  AND `uid` = %d LIMIT 1",
				intval($request_id),
				intval(local_channel())
			);
		
			if(count($r)) {
				$intro_id = $r[0]['id'];
				$contact_id = $r[0]['contact-id'];
			}
			else {
				notice( t('Invalid request identifier.') . EOL);
				return;
			}
	
			// If it is a friend suggestion, the contact is not a new friend but an existing friend
			// that should not be deleted.
	
			$fid = $r[0]['fid'];
	
			if($_POST['submit'] == t('Discard')) {
				$r = q("DELETE FROM `intro` WHERE `id` = %d", 
					intval($intro_id)
				);	
				if(! $fid) {
	
					// The check for blocked and pending is in case the friendship was already approved
					// and we just want to get rid of the now pointless notification
	
					$r = q("DELETE FROM `contact` WHERE `id` = %d AND `uid` = %d AND `self` = 0 AND `blocked` = 1 AND `pending` = 1", 
						intval($contact_id),
						intval(local_channel())
					);
				}
				goaway(z_root() . '/notifications/intros');
			}
			if($_POST['submit'] == t('Ignore')) {
				$r = q("UPDATE `intro` SET `ignore` = 1 WHERE `id` = %d",
					intval($intro_id));
				goaway(z_root() . '/notifications/intros');
			}
		}
	}
	
	
	
	
	
		function get() {
	
		if(! local_channel()) {
			notice( t('Permission denied.') . EOL);
			return;
		}
	
		nav_set_selected('notifications');		
	
		$o = '';
	
			$notif_tpl = get_markup_template('notifications.tpl');
			
			$not_tpl = get_markup_template('notify.tpl');
			require_once('include/bbcode.php');
	
			$r = q("SELECT * from notify where uid = %d and seen = 0 order by created desc",
				intval(local_channel())
			);
			
			if ($r > 0) {
				$notifications_available =1;
				foreach ($r as $it) {
					$notif_content .= replace_macros($not_tpl,array(
						'$item_link' => z_root().'/notify/view/'. $it['id'],
						'$item_image' => $it['photo'],
						'$item_text' => strip_tags(bbcode($it['msg'])),
						'$item_when' => relative_date($it['created'])
					));
				}
			} else {
				$notif_content .= t('No more system notifications.');
			}
			
			$o .= replace_macros($notif_tpl,array(
				'$notif_header' => t('System Notifications'),
				'$notif_link_mark_seen' => t('Mark all system notifications seen'),
				'$notif_content' => $notif_content,
				'$notifications_available' => $notifications_available,
			));
	
		return $o;
	}
	
}
