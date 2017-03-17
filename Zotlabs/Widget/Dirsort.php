<?php

namespace Zotlabs\Widget;

require_once('include/dir_fns.php');

class Dirsort {
	function widget($arr) {
		return dir_sort_links();
	}
}
