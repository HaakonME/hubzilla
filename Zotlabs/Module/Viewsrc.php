<?php
namespace Zotlabs\Module;



class Viewsrc extends \Zotlabs\Web\Controller {

	function get() {
	
		$o = '';
	
		$sys = get_sys_channel();
	
		$item_id = ((argc() > 1) ? intval(argv(1)) : 0);
		$json    = ((argc() > 2 && argv(2) === 'json') ? true : false);
		$dload   = ((argc() > 2 && argv(2) === 'download') ? true : false);
	
		if(! local_channel()) {
			notice( t('Permission denied.') . EOL);
		}
	
	
		if(! $item_id) {
			\App::$error = 404;
			notice( t('Item not found.') . EOL);
		}
	
		$item_normal = item_normal();
	
		if(local_channel() && $item_id) {
			$r = q("select id, item_flags, mimetype, item_obscured, body from item where uid in (%d , %d) and id = %d $item_normal limit 1",
				intval(local_channel()),
				intval($sys['channel_id']),
				intval($item_id)
			);
	
			if($r) {
				if(intval($r[0]['item_obscured']))
					$dload = true;

				if($dload) {
					header('Content-type: ' . $r[0]['mimetype']);
					header('Content-disposition: attachment; filename="' . t('item') . '-' . $item_id . '"' );
					echo $r[0]['body'];
					killme();
				}


				$content = escape_tags($r[0]['body']);
				$o = (($json) ? json_encode($content) : str_replace("\n",'<br />',$content));
			}
		}
	
		if(is_ajax()) {
			print '<div><i class="fa fa-pencil"> ' . t('Source of Item') . ' ' . $r[0]['id'] . '</i></div>';
			echo $o;
			killme();
		} 
	
		return $o;
	}
	
	
}
