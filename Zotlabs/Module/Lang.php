<?php
namespace Zotlabs\Module;


class Lang extends \Zotlabs\Web\Controller {

	function get() {
		nav_set_selected('Language');
		return lang_selector();
	}
	
	
}
