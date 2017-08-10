<?php

namespace Zotlabs\Module;

/* Ofeed: Broken feed for software which requires broken feeds */

require_once('include/items.php');

class Ofeed extends \Zotlabs\Web\Controller {

	function init() {
	
		$params = [];
	
		$params['begin']     = ((x($_REQUEST,'date_begin')) ? $_REQUEST['date_begin']       : NULL_DATE);
		$params['end']       = ((x($_REQUEST,'date_end'))   ? $_REQUEST['date_end']         : '');
		$params['type']      = ((stristr(argv(0),'json'))   ? 'json'                        : 'xml');
		$params['pages']     = ((x($_REQUEST,'pages'))      ? intval($_REQUEST['pages'])    : 0);
		$params['top']       = ((x($_REQUEST,'top'))        ? intval($_REQUEST['top'])      : 0);
		$params['start']     = ((x($params,'start'))        ? intval($params['start'])      : 0);
		$params['records']   = ((x($params,'records'))      ? intval($params['records'])    : 10);
		$params['direction'] = ((x($params,'direction'))    ? dbesc($params['direction'])   : 'desc');
		$params['cat']       = ((x($_REQUEST,'cat'))        ? escape_tags($_REQUEST['cat']) : '');
		$params['compat']    = ((x($_REQUEST,'compat'))     ? intval($_REQUEST['compat'])   : 1);	


		if(argc() > 1) {

			if(observer_prohibited(true)) {
				killme();
			}

			$channel = channelx_by_nick(argv(1));
			if(! $channel) {
				killme();
			}
	
	 
			logger('public feed request from ' . $_SERVER['REMOTE_ADDR'] . ' for ' . $channel['channel_address']);
	
			echo get_public_feed($channel,$params);
	
			killme();
		}
	
	}
	
}
