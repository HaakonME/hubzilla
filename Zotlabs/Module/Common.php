<?php
namespace Zotlabs\Module;

require_once('include/socgraph.php');


class Common extends \Zotlabs\Web\Controller {

	function init() {
	
		if(argc() > 1 && intval(argv(1)))
			$channel_id = intval(argv(1));
		else {
			notice( t('No channel.') . EOL );
			\App::$error = 404;
			return;
		}
	
		$x = q("select channel_address from channel where channel_id = %d limit 1",
			intval($channel_id)
		);
	
		if($x)
			profile_load($x[0]['channel_address'],0);
	
	}
	
		function get() {
	
		$o = '';
	
		if(! \App::$profile['profile_uid'])
			return;
	
		$observer_hash = get_observer_hash();
	
	
		if(! perm_is_allowed(\App::$profile['profile_uid'],$observer_hash,'view_contacts')) {
			notice( t('Permission denied.') . EOL);
			return;
		}
	
		$o .= '<h2>' . t('Common connections') . '</h2>';
	
		$t = count_common_friends(\App::$profile['profile_uid'],$observer_hash);
	
		if(! $t) {
			notice( t('No connections in common.') . EOL);
			return $o;
		}
	
		$r = common_friends(\App::$profile['profile_uid'],$observer_hash);
	
		if($r) {
	
			$tpl = get_markup_template('common_friends.tpl');
	
			foreach($r as $rr) {
				$o .= replace_macros($tpl,array(
					'$url'   => $rr['xchan_url'],
					'$name'  => $rr['xchan_name'],
					'$photo' => $rr['xchan_photo_m'],
					'$tags'  => ''
				));
			}
	
			$o .= cleardiv();
		}
	
		return $o;
	}
	
}
