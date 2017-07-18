<?php
namespace Zotlabs\Module;

require_once('include/zot.php');


class Ap_probe extends \Zotlabs\Web\Controller {

	function get() {
	
		$o .= '<h3>ActivityPub Probe Diagnostic</h3>';
	
		$o .= '<form action="ap_probe" method="get">';
		$o .= 'Lookup URI: <input type="text" style="width: 250px;" name="addr" value="' . $_GET['addr'] .'" />';
		$o .= '<input type="submit" name="submit" value="Submit" /></form>'; 
	
		$o .= '<br /><br />';
	
		if(x($_GET,'addr')) {
			$addr = $_GET['addr'];

			$redirects = 0;
		    $x = z_fetch_url($addr,true,$redirects,
	        [ 'headers' => [ 'Accept: application/ld+json; profile="https://www.w3.org/ns/activitystreams"']]);
logger('fetch: ' . print_r($x,true));

	    	if($x['success'])
				$o .= '<pre>' . str_replace('\\','',jindent($x['body'])) . '</pre>';
		}
		return $o;
	}
	
}
