<?php
namespace Zotlabs\Module;

require_once('include/items.php');
require_once('include/zot.php');


class Zotfeed extends \Zotlabs\Web\Controller {

	function init() {
	
		$result = array('success' => false);
	
		$mindate = (($_REQUEST['mindate']) ? datetime_convert('UTC','UTC',$_REQUEST['mindate']) : '');
		if(! $mindate)
			$mindate = datetime_convert('UTC','UTC', 'now - 14 days');
	
		if(observer_prohibited()) {
			$result['message'] = 'Public access denied';
			json_return_and_die($result);
		}
	
		$observer = \App::get_observer();
	
	
		$channel_address = ((argc() > 1) ? argv(1) : '');
		if($channel_address) {
			$r = q("select channel_id, channel_name from channel where channel_address = '%s' and channel_removed = 0 limit 1",
				dbesc(argv(1))
			);
		}
		else {
			$x = get_sys_channel();
			if($x)
				$r = array($x);
			$mindate = datetime_convert('UTC','UTC', 'now - 14 days');
		}
		if(! $r) {
			$result['message'] = 'Channel not found.';
			json_return_and_die($result);
		}
	
		logger('zotfeed request: ' . $r[0]['channel_name'], LOGGER_DEBUG);
	
		$result['messages'] = zot_feed($r[0]['channel_id'],$observer['xchan_hash'],array('mindate' => $mindate));
		$result['success'] = true;
		json_return_and_die($result);
	}
	
}
