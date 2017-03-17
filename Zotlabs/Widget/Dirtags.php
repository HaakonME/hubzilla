<?php

namespace Zotlabs\Widget;

require_once('include/dir_fns.php');

class Dirtags {

	function widget($arr) {
		return dir_tagblock(z_root() . '/directory', null);
	}

}
