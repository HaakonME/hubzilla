<?php

namespace Zotlabs\Module;

require_once('include/conversation.php');


class Moderate extends \Zotlabs\Web\Controller {


	function get() {
		if(! local_channel()) {
			notice( t('Permission denied.') . EOL);
			return;
		}


		if(argc() > 2) {
			$post_id = intval(argv(1));
			if(! $post_id)
				goaway(z_root() . '/moderate');

			$action = argv(2);

			$r = q("select * from item where uid = %d and id = %d and item_blocked = %d limit 1",
				intval(local_channel()),
				intval($post_id),
				intval(ITEM_MODERATED)
			);

			if($r) {
				if($action === 'approve') {
					q("update item set item_blocked = 0 where uid = %d and id = %d",
						intval(local_channel()),
						intval($post_id)
					);
					notice( t('Comment approved') . EOL);
				}
				elseif($action === 'drop') {
					drop_item($post_id,false);
					notice( t('Comment deleted') . EOL);
				} 
			
				$r = q("select * from item where id = %d",
					intval($post_id)
				);
				if($r) {
					xchan_query($r);
					$sync_item = fetch_post_tags($r);
					build_sync_packet(local_channel(),array('item' => array(encode_item($sync_item[0],true))));
				}
				if($action === 'approve') {
					\Zotlabs\Daemon\Master::Summon(array('Notifier', 'comment-new', $post_id));
				}
				goaway(z_root() . '/moderate');
			}
		}
		$r = q("select item.id as item_id, item.* from item where item.uid = %d and item_blocked = %d and item_deleted = 0 order by created desc limit 60",
			intval(local_channel()),
			intval(ITEM_MODERATED)
		);

		if($r) {
			xchan_query($r);
			$items = fetch_post_tags($r,true);
		}
		else {
			$items = array();
		}

		$o = conversation($items,'moderate',false,'traditional');
		return $o;

	}

}
