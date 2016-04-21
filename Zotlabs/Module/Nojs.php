<?php
namespace Zotlabs\Module;


class Nojs extends \Zotlabs\Web\Controller {

	function init() {
	
		setcookie('jsdisabled', 1, 0);
		$p = $_GET['query'];
		$hasq = strpos($p,'?');
		goaway(z_root() . (($p) ? '/' . $p : '') . (($hasq) ? '' : '?f=' ) . '&jsdisabled=1');
	
	}
}
