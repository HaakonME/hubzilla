<?php
/**
 * @file Zotlabs/Module/Dav.php
 * @brief Initialize Hubzilla's cloud (SabreDAV).
 *
 * Module for accessing the DAV storage area from a DAV client.
 */

namespace Zotlabs\Module;

use \Sabre\DAV as SDAV;
use \Zotlabs\Storage;

require_once('include/attach.php');
require_once('include/auth.php');
require_once('include/security.php');


class Dav extends \Zotlabs\Web\Controller {

	/**
	 * @brief Fires up the SabreDAV server.
	 *
	 */
	function init() {

		foreach([ 'REDIRECT_REMOTE_USER', 'HTTP_AUTHORIZATION' ] as $head) {

			/* Basic authentication */

			if(array_key_exists($head,$_SERVER) && substr(trim($_SERVER[$head]),0,5) === 'Basic') {
				$userpass = @base64_decode(substr(trim($_SERVER[$head]),6)) ;
				if(strlen($userpass)) {
					list($name, $password) = explode(':', $userpass);
					$_SERVER['PHP_AUTH_USER'] = $name;
					$_SERVER['PHP_AUTH_PW']   = $password;
				}
				break;
			}

			/* Signature authentication */

			if(array_key_exists($head,$_SERVER) && substr(trim($_SERVER[$head]),0,9) === 'Signature') {
				if($head !== 'HTTP_AUTHORIZATION') {
					$_SERVER['HTTP_AUTHORIZATION'] = $_SERVER[$head];
					continue;
				}

				$sigblock = \Zotlabs\Web\HTTPSig::parse_sigheader($_SERVER[$head]);
				if($sigblock) {
					$keyId = $sigblock['keyId'];
					if($keyId) {
						$r = q("select * from hubloc where hubloc_addr = '%s' limit 1",
							dbesc($keyId)
						);
						if($r) {
							$c = channelx_by_hash($r[0]['hubloc_hash']);
							if($c) {
								$a = q("select * from account where account_id = %d limit 1",
									intval($c['channel_account_id'])
								);
								if($a) {
									$record = [ 'channel' => $c, 'account' => $a[0] ];
									$channel_login = $c['channel_id'];
								}
							}
						}
						if(! $record)
							continue;

						if($record) {
							$verified = \Zotlabs\Web\HTTPSig::verify('',$record['channel']['channel_pubkey']);
							if(! ($verified && $verified['header_signed'] && $verified['header_valid'])) {
								$record = null;
							}
							if($record['account']) {
						        authenticate_success($record['account']);
						        if($channel_login) {
						            change_channel($channel_login);
								}
							}
							break;
						}
					}
				}
			}
		}

		if (! is_dir('store'))
			os_mkdir('store', STORAGE_DEFAULT_PERMISSIONS, false);

		if (argc() > 1)
			profile_load(argv(1),0);


		$auth = new \Zotlabs\Storage\BasicAuth();
		$auth->setRealm(ucfirst(\Zotlabs\Lib\System::get_platform_name()) . ' ' . 'WebDAV');

		$rootDirectory = new \Zotlabs\Storage\Directory('/', $auth);

		// A SabreDAV server-object
		$server = new SDAV\Server($rootDirectory);


		$authPlugin = new \Sabre\DAV\Auth\Plugin($auth);
		$server->addPlugin($authPlugin);


		// prevent overwriting changes each other with a lock backend
		$lockBackend = new SDAV\Locks\Backend\File('store/[data]/locks');
		$lockPlugin = new SDAV\Locks\Plugin($lockBackend);

		$server->addPlugin($lockPlugin);

		// provide a directory view for the cloud in Hubzilla
		$browser = new \Zotlabs\Storage\Browser($auth);
		$auth->setBrowserPlugin($browser);

		// Experimental QuotaPlugin
		// $server->addPlugin(new \Zotlabs\Storage\QuotaPlugin($auth));

		// All we need to do now, is to fire up the server
		$server->exec();

		killme();
	}

}
