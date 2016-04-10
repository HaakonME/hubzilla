<?php

namespace Zotlabs\Web;

/**
 *
 * @brief This file includes session related functions.
 *
 * Session management functions. These provide database storage of PHP
 * session info.
 */


class Session {

	private static $handler = null;

	function init() {

		$gc_probability = 50;

		ini_set('session.gc_probability', $gc_probability);
		ini_set('session.use_only_cookies', 1);
		ini_set('session.cookie_httponly', 1);
	
		/*
		 * Set our session storage functions.
		 */

		$handler = new \Zotlabs\Web\SessionHandler();
		self::$handler = $handler;

		$x = session_set_save_handler($handler,true);
		if(! $x)
			logger('Session save handler initialisation failed.',LOGGER_NORMAL,LOG_ERR);

		// Force cookies to be secure (https only) if this site is SSL enabled. 
		// Must be done before session_start().

	    if(intval(\App::$config['system']['ssl_cookie_protection'])) {
	        $arr = session_get_cookie_params();
    	    session_set_cookie_params(
        	    ((isset($arr['lifetime']))  ? $arr['lifetime'] : 0),
            	((isset($arr['path']))      ? $arr['path']     : '/'),
	            ((isset($arr['domain']))    ? $arr['domain']   : App::get_hostname()),
    	        ((isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on') ? true : false),
        	    ((isset($arr['httponly']))  ? $arr['httponly'] : true)
			);
    	}
	}

	function start() {
		session_start();
	}

	/**
	 * @brief Resets the current session.
	 *
	 * @return void
	 */

	function nuke() {
		self::new_cookie(0); // 0 means delete on browser exit
		if($_SESSION && count($_SESSION)) {
			foreach($_SESSION as $k => $v) {
				unset($_SESSION[$k]);
			}
		}
	}



	function new_cookie($xtime) {

		$newxtime = (($xtime> 0) ? (time() + $xtime) : 0);

		$old_sid = session_id();

		session_regenerate_id(false);

		if(self::$handler) {
			$v = q("UPDATE session SET sid = '%s' WHERE sid = '%s'",
				dbesc(session_id()),
				dbesc($old_sid)
			);
		}
		else 
			logger('no session handler');

		if (x($_COOKIE, 'jsAvailable')) {
			setcookie('jsAvailable', $_COOKIE['jsAvailable'], $newxtime);
		}
		setcookie(session_name(),session_id(),$newxtime);

	}


}