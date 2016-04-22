<?php

namespace Zotlabs\Extend;


class Hook {

	static public function register($hook,$file,$function,$version = 1,$priority = 0) {
		$r = q("SELECT * FROM `hook` WHERE `hook` = '%s' AND `file` = '%s' AND `function` = '%s' and priority = %d and hook_version = %d LIMIT 1",
			dbesc($hook),
			dbesc($file),
			dbesc($function),
			intval($priority),
			intval($version)
		);
		if($r)
			return true;

		$r = q("INSERT INTO `hook` (`hook`, `file`, `function`, `priority`, `hook_version`) VALUES ( '%s', '%s', '%s', %d, %d )",
			dbesc($hook),
			dbesc($file),
			dbesc($function),
			intval($priority),
			intval($version)
		);

		return $r;
	}

	static public function unregister($hook,$file,$function,$version = 1,$priority = 0) {
		$r = q("DELETE FROM hook WHERE hook = '%s' AND `file` = '%s' AND `function` = '%s' and priority = %d and hook_version = %d",
			dbesc($hook),
			dbesc($file),
			dbesc($function),
			intval($priority),
			intval($version)
		);

		return $r;
	}


	/**
	 * @brief Inserts a hook into a page request.
	 *
	 * Insert a short-lived hook into the running page request.
	 * Hooks are normally persistent so that they can be called
	 * across asynchronous processes such as delivery and poll
	 * processes.
	 *
	 * insert_hook lets you attach a hook callback immediately
	 * which will not persist beyond the life of this page request
	 * or the current process.
	 *
	 * @param string $hook
	 *     name of hook to attach callback
	 * @param string $fn
	 *     function name of callback handler
	 * @param int $version
	 *     hook interface version, 0 uses two callback params, 1 uses one callback param
	 * @param int $priority
	 *     currently not implemented in this function, would require the hook array to be resorted
	 */

	static public function insert($hook, $fn, $version = 0, $priority = 0) {

		if(! is_array(App::$hooks))
			App::$hooks = array();

		if(! array_key_exists($hook, App::$hooks))
			App::$hooks[$hook] = array();

		App::$hooks[$hook][] = array('', $fn, $priority, $version);
	}

}