<?php /** @file */

require_once('boot.php');
require_once('include/dba/dba_driver.php');

// Everything we need to boot standalone 'background' processes

function cli_startup() {

	global $a, $db, $default_timezone;

	if(is_null($a)) {
		$a = new miniApp;
	}

	App::init();
  
	if(! DBA::$dba) {
	    @include(".htconfig.php");

		$a->convert();

		if(! defined('UNO'))
			define('UNO', 0);

		App::$timezone = ((x($default_timezone)) ? $default_timezone : 'UTC');
		date_default_timezone_set(App::$timezone);

	    $db = DBA::dba_factory($db_host, $db_port, $db_user, $db_pass, $db_data, $db_type);
    	unset($db_host, $db_port, $db_user, $db_pass, $db_data, $db_type);
  	};

	\Zotlabs\Web\Session::init();

	load_config('system');

	App::set_baseurl(get_config('system','baseurl'));

	load_hooks();

}