<?php

namespace Zotlabs\Widget;

require_once('include/photos.php');

class Photo_rand {

	function widget($arr) {

		$style = false;

		if(array_key_exists('album', $arr) && isset($arr['album']))
			$album = $arr['album'];
		else
			$album = '';

		$channel_id = 0;
		if(array_key_exists('channel_id', $arr) && intval($arr['channel_id']))
			$channel_id = intval($arr['channel_id']);
		if(! $channel_id)
			$channel_id = \App::$profile_uid;
		if(! $channel_id)
			return '';

		$scale = ((array_key_exists('scale',$arr)) ? intval($arr['scale']) : 0);

		$ret = photos_list_photos(array('channel_id' => $channel_id),\App::get_observer(),$album);

		$filtered = array();
		if($ret['success'] && $ret['photos'])
		foreach($ret['photos'] as $p)
			if($p['imgscale'] == $scale)
				$filtered[] = $p['src'];

		if($filtered) {
			$e = mt_rand(0, count($filtered) - 1);
			$url = $filtered[$e];
		}

		if(strpos($url, 'http') !== 0)
			return '';
	
		if(array_key_exists('style', $arr) && isset($arr['style']))
			$style = $arr['style'];
	
		// ensure they can't sneak in an eval(js) function

		if(strpos($style,'(') !== false)
			return '';
	
		$url = zid($url);

		$o = '<div class="widget">';

		$o .= '<img class="zrl" '
			. (($style) ? ' style="' . $style . '"' : '')
			. ' src="' . $url . '" alt="' . t('photo/image') . '">';

		$o .= '</div>';

		return $o;
	}
}


