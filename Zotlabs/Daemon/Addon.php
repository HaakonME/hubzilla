<?php

namespace Zotlabs\Daemon;

require_once('include/zot.php');

class Addon {

	static public function run($argc,$argv) {

		call_hooks('daemon_addon',$argv);

	}
}
