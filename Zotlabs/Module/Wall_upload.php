<?php
namespace Zotlabs\Module;

require_once('include/photo/photo_driver.php');
require_once('include/channel.php');
require_once('include/photos.php');




class Wall_upload extends \Zotlabs\Web\Controller {

	function post() {
	
	
		$using_api = ((x($_FILES,'media')) ? true : false); 
	
		if($using_api) {
			require_once('include/api.php');
			$user_info = api_get_user($a);
			$nick = $user_info['screen_name'];
		}
		else {
			if(argc() > 1)
				$nick = argv(1);
		}
	
		$channel = (($nick) ? get_channel_by_nick($nick) : false);
	
		if(! $channel) {
			if($using_api)
				return;
			notice( t('Channel not found.') . EOL);
			killme();
		}
	
		$observer = \App::get_observer();
	
		$args = array( 'source' => 'editor', 'visible' => 0, 'contact_allow' => array($channel['channel_hash']));
	
	 	$ret = photo_upload($channel,$observer,$args);
	
		if(! $ret['success']) {
			if($using_api)
				return;
			notice($ret['message']);
			killme();
		}
	
		if($using_api)
			return("\n\n" . $ret['body'] . "\n\n");
		else
			echo  "\n\n" . $ret['body'] . "\n\n";
		killme();
	}
	
}
