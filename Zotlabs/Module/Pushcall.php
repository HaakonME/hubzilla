<?php
namespace Zotlabs\Module;


class Pushcall extends \Zotlabs\Web\Controller {

	function init() {
		logger('pushcall: received');
	
		$xml = file_get_contents('php://input');
	
		logger('received: ' . $xml);
	
	
	
	
	killme();
	}
}
