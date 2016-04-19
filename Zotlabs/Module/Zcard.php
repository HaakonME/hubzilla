<?php
namespace Zotlabs\Module;



class Zcard extends \Zotlabs\Web\Controller {

	function init() {
	
	/*
		if(argc() > 1)
			$which = argv(1);
		if(! $which)
			return;
	
	
		$arr = array();
		$arr['links'] = array();
	
		$r = q("select * from channel left join xchan on xchan_hash = channel_hash where channel_address = '%s' limit 1",
			dbesc($which)
		);
	
		if(! $which) {
			notice( t('Channel not found.' ) . EOL);
			return;
		}
	
		$channel = $r[0];
		$channel['channel_addr'] = $r[0]['channel_address'] . '@' . \App::get_hostname();
		$arr['chan'] = $channel;
	
		if(perm_is_allowed($channel['channel_id'],get_observer_hash(),'view_profile')) {
			$p = q("select * from profile where is_default = 1 and uid = %d limit 1",
				intval($channel['channel_id'])
			);
		}
		$profile = (($p) ? $p[0] : false);
	
		$r = q("select height, width, resource_id, scale, type from photo where uid = %d and scale >= %d and photo_usage = %d",
			intval($channel['channel_id']),
			intval(PHOTO_RES_COVER_1200),
			intval(PHOTO_COVER)
		);
	
		if($r) {
			foreach($r as $rr) {
				$arr['links'][] = array('rel' => 'cover_photo', 'type' => $rr['type'], 'width' => intval($rr['width']) , 'height' => intval($rr['height']), 'href' => z_root() . '/photo/' . $rr['resource_id'] . '-' . $rr['scale']);
			}
		}		
		
		$arr['links'][] = array('rel' => 'profile_photo', 'type' => $channel['xchan_photo_mimetype'], 'width' => 300 , 'height' => 300, 'href' => $channel['xchan_photo_l']);
		$arr['links'][] = array('rel' => 'profile_photo', 'type' => $channel['xchan_photo_mimetype'], 'width' => 80 , 'height' => 80,   'href' => $channel['xchan_photo_m']);
		$arr['links'][] = array('rel' => 'profile_photo', 'type' => $channel['xchan_photo_mimetype'], 'width' => 48 , 'height' => 48,   'href' => $channel['xchan_photo_s']);
			
		
	
		$likers = q("select liker, xchan.*  from likes left join xchan on liker = xchan_hash where channel_id = %d and target_type = '%s' and verb = '%s'",
			intval(\App::$profile['profile_uid']),
			dbesc(ACTIVITY_OBJ_PROFILE),
			dbesc(ACTIVITY_LIKE)
		);
		$profile['likers'] = array();
		$profile['like_count'] = count($likers);
		$profile['like_button_label'] = tt('Like','Likes',$profile['like_count'],'noun');
		if($likers) {
			foreach($likers as $l)
				$profile['likers'][] = array('name' => $l['xchan_name'],'url' => $l['xchan_url'], 'photo' => $l['xchan_photo_s']);
		}
	
		$arr['profile'] = $profile;
	
		logger('zcard: ' . print_r($arr,true));
	
		if(argc() > 2) 
			\App::$data['zcard'] = $arr;
		else {
			echo json_encode($arr);
			killme();
	
		}
	*/
	}
	
	
	
		function get() {
	
		$channel = channelx_by_nick(argv(1));
		if(! $channel)
			return;
	
		$o = get_zcard($channel,get_observer_hash(),array('width' => $_REQUEST['width'], 'height' => $_REQUEST['height']));
	
	//	$o .= replace_macros(get_markup_template('zcard.tpl'),array(
	//		'$scale' => $scale,
	//		'$cover' => $cover,
	//		'$pphoto' => $pphoto,
	//		'$zcard' => $zcard,
	//		'$size' => 'small'
	//	));		
		
		return $o;
	
	
	}
}
