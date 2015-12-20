<?php /** @file */

/**
 * API Login via basic-auth or OAuth
 */

function api_login(&$a){

	$record = null;

	require_once('include/oauth.php');

	// login with oauth
	try {
		$oauth = new ZotOAuth1();
		$req = OAuth1Request::from_request();

		list($consumer,$token) = $oauth->verify_request($req);

		if (!is_null($token)){
			$oauth->loginUser($token->uid);

			$a->set_oauth_key($consumer->key);

			call_hooks('logged_in', $a->user);
			return;
		}
		killme();
	}
	catch(Exception $e) {
		logger($e->getMessage());
	}
		
	// workarounds for HTTP-auth in CGI mode

	if(x($_SERVER,'REDIRECT_REMOTE_USER')) {
		$userpass = base64_decode(substr($_SERVER["REDIRECT_REMOTE_USER"],6)) ;
		if(strlen($userpass)) {
			list($name, $password) = explode(':', $userpass);
			$_SERVER['PHP_AUTH_USER'] = $name;
			$_SERVER['PHP_AUTH_PW'] = $password;
		}
	}

	if(x($_SERVER,'HTTP_AUTHORIZATION')) {
		$userpass = base64_decode(substr($_SERVER["HTTP_AUTHORIZATION"],6)) ;
		if(strlen($userpass)) {
			list($name, $password) = explode(':', $userpass);
			$_SERVER['PHP_AUTH_USER'] = $name;
			$_SERVER['PHP_AUTH_PW'] = $password;
		}
	}

	require_once('include/auth.php');
	require_once('include/security.php');

	// process normal login request

	if(isset($_SERVER['PHP_AUTH_USER'])) {
		$channel_login = 0;
		$record = account_verify_password($_SERVER['PHP_AUTH_USER'],$_SERVER['PHP_AUTH_PW']);
		if(! $record) {
	        $r = q("select * from channel left join account on account.account_id = channel.channel_account_id 
				where channel.channel_address = '%s' limit 1",
		       dbesc($_SERVER['PHP_AUTH_USER'])
			);
        	if ($r) {
				$record = account_verify_password($r[0]['account_email'],$_SERVER['PHP_AUTH_PW']);
				if($record)
					$channel_login = $r[0]['channel_id'];
			}
		}
	}

	if($record) {
		authenticate_success($record);

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


function retry_basic_auth() {
	header('WWW-Authenticate: Basic realm="Hubzilla"');
	header('HTTP/1.0 401 Unauthorized');
	echo('This api requires login');
	killme();
}