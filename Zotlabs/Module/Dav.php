<?php

namespace Zotlabs\Module;
/**
 * @file mod/dav.php
 * @brief Initialize Hubzilla's cloud (SabreDAV).
 *
 * Module for accessing the DAV storage area from a DAV client.
 */

use \Sabre\DAV as SDAV;
use \Zotlabs\Storage;

// composer autoloader for SabreDAV
require_once('vendor/autoload.php');


/**
 * @brief Fires up the SabreDAV server.
 *
 * @param App &$a
 */

class Dav extends \Zotlabs\Web\Controller {

	function init() {
	
		// workaround for HTTP-auth in CGI mode
		if (x($_SERVER, 'REDIRECT_REMOTE_USER')) {
 			$userpass = base64_decode(substr($_SERVER["REDIRECT_REMOTE_USER"], 6)) ;
			if(strlen($userpass)) {
			 	list($name, $password) = explode(':', $userpass);
				$_SERVER['PHP_AUTH_USER'] = $name;
				$_SERVER['PHP_AUTH_PW'] = $password;
			}
		}

		if (x($_SERVER, 'HTTP_AUTHORIZATION')) {
			$userpass = base64_decode(substr($_SERVER["HTTP_AUTHORIZATION"], 6)) ;
			if(strlen($userpass)) {
				list($name, $password) = explode(':', $userpass);
				$_SERVER['PHP_AUTH_USER'] = $name;
				$_SERVER['PHP_AUTH_PW'] = $password;
			}
		}

		require_once('include/reddav.php');
	
		if (! is_dir('store'))
			os_mkdir('store', STORAGE_DEFAULT_PERMISSIONS, false);
	
		$which = null;
		if (argc() > 1)
			$which = argv(1);
	
		$profile = 0;
	
		\App::$page['htmlhead'] .= '<link rel="alternate" type="application/atom+xml" href="' . z_root() . '/feed/' . $which . '" />' . "\r\n";
	
		if ($which)
			profile_load($a, $which, $profile);
	



		$auth = new \Zotlabs\Storage\BasicAuth();
		$auth->setRealm(ucfirst(\Zotlabs\Lib\System::get_platform_name()) . 'WebDAV');

//		$authBackend = new \Sabre\DAV\Auth\Backend\BasicCallBack(function($userName,$password) {
//			if(account_verify_password($userName,$password))
//				return true;
//			return false;
//		});

//		$ob_hash = get_observer_hash();
	
//		if ($ob_hash) {
//			if (local_channel()) {
//				$channel = \App::get_channel();
//				$auth->setCurrentUser($channel['channel_address']);
//				$auth->channel_id = $channel['channel_id'];
//				$auth->channel_hash = $channel['channel_hash'];
//				$auth->channel_account_id = $channel['channel_account_id'];
//				if($channel['channel_timezone'])
//					$auth->setTimezone($channel['channel_timezone']);
//			}
//			$auth->observer = $ob_hash;
//		}
	
//		if ($_GET['davguest'])
//			$_SESSION['davguest'] = true;
	
//		$_SERVER['QUERY_STRING'] = str_replace(array('?f=', '&f='), array('', ''), $_SERVER['QUERY_STRING']);
//		$_SERVER['QUERY_STRING'] = strip_zids($_SERVER['QUERY_STRING']);
//		$_SERVER['QUERY_STRING'] = preg_replace('/[\?&]davguest=(.*?)([\?&]|$)/ism', '', $_SERVER['QUERY_STRING']);
//	
//		$_SERVER['REQUEST_URI'] = str_replace(array('?f=', '&f='), array('', ''), $_SERVER['REQUEST_URI']);
//		$_SERVER['REQUEST_URI'] = strip_zids($_SERVER['REQUEST_URI']);
//		$_SERVER['REQUEST_URI'] = preg_replace('/[\?&]davguest=(.*?)([\?&]|$)/ism', '', $_SERVER['REQUEST_URI']);
	
		$rootDirectory = new \Zotlabs\Storage\Directory('/', $auth);
	
		// A SabreDAV server-object
		$server = new SDAV\Server($rootDirectory);


		$authPlugin = new \Sabre\DAV\Auth\Plugin($auth);
		$server->addPlugin($authPlugin);


		// prevent overwriting changes each other with a lock backend
		$lockBackend = new SDAV\Locks\Backend\File('store/[data]/locks');
		$lockPlugin = new SDAV\Locks\Plugin($lockBackend);
	
		$server->addPlugin($lockPlugin);
	
		// The next section of code allows us to bypass prompting for http-auth if a
		// FILE is being accessed anonymously and permissions allow this. This way
		// one can create hotlinks to public media files in their cloud and anonymous
		// viewers won't get asked to login.
		// If a DIRECTORY is accessed or there are permission issues accessing the
		// file and we aren't previously authenticated via zot, prompt for HTTP-auth.
		// This will be the default case for mounting a DAV directory. 
		// In order to avoid prompting for passwords for viewing a DIRECTORY, add
		// the URL query parameter 'davguest=1'.
	
//		$isapublic_file = false;
//		$davguest = ((x($_SESSION, 'davguest')) ? true : false);
	
//		if ((! $auth->observer) && ($_SERVER['REQUEST_METHOD'] === 'GET')) {
//			try { 
//				$x = RedFileData('/' . \App::$cmd, $auth);
//				if($x instanceof \Zotlabs\Storage\File)
//					$isapublic_file = true;
//			}
//			catch (Exception $e) {
//				$isapublic_file = false;
//			}
//		}
	
//		if ((! $auth->observer) && (! $isapublic_file) && (! $davguest)) {
//			try {
//				$auth->Authenticate($server, t('$Projectname channel'));
//			}
//			catch (Exception $e) {
//				logger('mod_cloud: auth exception' . $e->getMessage());
//				http_status_exit($e->getHTTPCode(), $e->getMessage());
//			}
//		}
	
	//	require_once('Zotlabs/Storage/Browser.php');
		// provide a directory view for the cloud in Hubzilla
		$browser = new \Zotlabs\Storage\Browser($auth);
		$auth->setBrowserPlugin($browser);
	
		// Experimental QuotaPlugin
	//	require_once('Zotlabs/Storage/QuotaPlugin.php');
	//	$server->addPlugin(new \Zotlabs\Storage\QuotaPlugin($auth));
	
		// All we need to do now, is to fire up the server
		$server->exec();
	
		killme();
	}
	
}
