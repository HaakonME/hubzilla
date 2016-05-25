<?php /** @file */

require_once('boot.php');

// Everything we need to boot standalone 'background' processes

function cli_startup() {

	global $default_timezone;

	$a = new miniApp;

	App::init();
  
	@include(".htconfig.php");

	$a->convert();

	if(! defined('UNO'))
		define('UNO', 0);

	App::$timezone = ((x($default_timezone)) ? $default_timezone : 'UTC');
	date_default_timezone_set(App::$timezone);

    require_once('include/dba/dba_driver.php');
	DBA::dba_factory($db_host, $db_port, $db_user, $db_pass, $db_data, $db_type);
    unset($db_host, $db_port, $db_user, $db_pass, $db_data, $db_type);

	App::$session = new Zotlabs\Web\Session();
	App::$session->init();

	load_config('system');

	App::set_baseurl(get_config('system','baseurl'));

	load_hooks();

}