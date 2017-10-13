<?php

namespace Zotlabs\Module;

class Logout extends \Zotlabs\Web\Controller {

	function init() {
		\App::$session->nuke();
		goaway(z_root());

	}
}