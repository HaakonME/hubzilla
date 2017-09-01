<?php /** @file */

/**
 * API Login via basic-auth or OAuth
 */

function api_login(&$a){

	$record = null;
	$remote_auth = false;
	$sigblock = null;

	require_once('include/oauth.php');

	// login with oauth
	try {
		$oauth = new ZotOAuth1();
		$req = OAuth1Request::from_request();

		list($consumer,$token) = $oauth->verify_request($req);

		if (!is_null($token)){
			$oauth->loginUser($token->uid);

			App::set_oauth_key($consumer->key);

			call_hooks('logged_in', App::$user);
			return;
		}
		killme();
	}
	catch(Exception $e) {
		logger($e->getMessage());
	}
		
	// workarounds for HTTP-auth in CGI mode

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
							else {
								continue;
							}
						}
						else {
							continue;
						}
					}
					else {
						continue;
					}

					if($record) {					
						$verified = \Zotlabs\Web\HTTPSig::verify('',$record['channel']['channel_pubkey']);
						if(! ($verified && $verified['header_signed'] && $verified['header_valid'])) {
							$record = null;
						}
						break;
					}
				}
			}
		}
	}

	require_once('include/auth.php');
	require_once('include/security.php');

	// process normal login request

	if(isset($_SERVER['PHP_AUTH_USER'])) {
		$channel_login = 0;
		$record = account_verify_password($_SERVER['PHP_AUTH_USER'],$_SERVER['PHP_AUTH_PW']);
		if($record && $record['channel']) {
			$channel_login = $record['channel']['channel_id'];
		}
	}

	if($record['account']) {
		authenticate_success($record['account']);

		if($channel_login)
			change_channel($channel_login);

		$_SESSION['allow_api'] = true;
		return true;
	}
	else {
		$_SERVER['PHP_AUTH_PW'] = '*****';
		logger('API_login failure: ' . print_r($_SERVER,true), LOGGER_DEBUG);
		log_failed_login('API login failure');
		retry_basic_auth();
	}

}


function retry_basic_auth($method = 'Basic') {
	header('WWW-Authenticate: ' . $method . ' realm="Hubzilla"');
	header('HTTP/1.0 401 Unauthorized');
	echo('This api requires login');
	killme();
}