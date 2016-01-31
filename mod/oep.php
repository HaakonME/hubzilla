<?php

// oembed provider



function oep_init(&$a) {



	$url = $_REQUEST['url'];
	if(! $url)
		http_status_exit(404, 'Not found');

	$maxwidth  = $_REQUEST['maxwidth'];
	$maxheight = $_REQUEST['maxheight'];
	$format = $_REQUEST['format'];
	if($format && $format !== 'json')
		http_status_exit(501, 'Not implemented');

	if(fnmatch('*/photos/*/album/*',$url))
		$arr = oep_album_reply($_REQUEST);
	elseif(fnmatch('*/photos/*',$url))
		$arr = oep_photo_reply($_REQUEST);


	if($arr) {
		header('Content-Type: application/json+oembed');
		echo json_encode($arr);
		killme();
	}

	http_status_exit(404,'Not found');

}


function oep_album_reply() {

}

function oep_photo_reply($args) {

	$ret = array();
	$url = $args['url'];
	$maxwidth  = intval($args['maxwidth']);
	$maxheight = intval($args['maxheight']);

	if(preg_match('|//(.*?)/(.*?)/(.*?)/image/|',$url,$matches)) {
		$chn = $matches[3];
		$res = basename($url);
	}

	if(! ($chn && $res))
		return;
	$c = q("select * from channel where channel_address = '%s' limit 1",
		dbesc($chn)
	);

	if(! $c)
		return;

	$sql_extra = permissions_sql($c[0]['channel_id']);


	$r = q("select height, width, scale, resource_id from photo where uid = %d and resource_id = '%s' $sql_extra order by scale asc",
		intval($c[0]['channel_id']),
		dbesc($res)
	);

	if($r) {
		foreach($r as $rr) {
			$foundres = false;
			if($maxheight && $rr['height'] > $maxheight)
				continue;
			if($maxwidth && $rr['width'] > $maxwidth)
				continue;
			$foundres = true;			
			break;
		}

		if($foundres) {
			$ret['type'] = 'link';
			$ret['thumbnail_url'] = z_root() . '/photo/' . '/' . $rr['resource_id'] . '-' . $rr['scale'];
			$ret['thumbnail_width'] = $rr['width'];
			$ret['thumbnail_height'] = $rr['height'];
		}
			

	}
	return $ret;

}