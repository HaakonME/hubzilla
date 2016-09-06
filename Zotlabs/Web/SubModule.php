<?php

namespace Zotlabs\Web;


class SubModule {

	private $controller = false;

	function __construct() {

		if(argc() < 2)
			return;

		$filename = 'Zotlabs/Module/' . ucfirst(argv(0)) . '/'. ucfirst(argv(1)) . '.php';
		$modname = '\\Zotlabs\\Module\\' . ucfirst(argv(0)) . '\\' . ucfirst(argv(1));
		if(file_exists($filename)) {
			$this->controller = new $modname();
		}
	}

	function call($method) {
		if(! $this->controller)
			return false;
		if(method_exists($this->controller,$method))
			return $this->controller->$method();
		return false;
	}

}

