<?php

namespace Zotlabs\Module;


class Authorize extends \Zotlabs\Web\Controller {


	function get() {


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




	require_once('include/oauth2.php');

	$request = \OAuth2\Request::createFromGlobals();
	$response = new \OAuth2\Response();

	// validate the authorize request
	if (! $oauth2_server->validateAuthorizeRequest($request, $response)) {
	    $response->send();
    	killme();
	}

	// display an authorization form
	if (empty($_POST)) {

	  return '
<form method="post">
  <label>Do You Authorize TestClient?</label><br />
  <input type="submit" name="authorized" value="yes">
  <input type="submit" name="authorized" value="no">
</form>';
	}

	// print the authorization code if the user has authorized your client
	$is_authorized = ($_POST['authorized'] === 'yes');
	$oauth2_server->handleAuthorizeRequest($request, $response, $is_authorized);
	if ($is_authorized) {
		// this is only here so that you get to see your code in the cURL request. Otherwise, 
		// we'd redirect back to the client
		$code = substr($response->getHttpHeader('Location'), strpos($response->getHttpHeader('Location'), 'code=')+5, 40);
		echo("SUCCESS! Authorization Code: $code");
		
	}

	$response->send();
	killme();
	}

}