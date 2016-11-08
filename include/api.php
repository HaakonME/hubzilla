<?php /** @file */

require_once("include/bbcode.php");
require_once("include/datetime.php");
require_once("include/conversation.php");
require_once("include/oauth.php");
require_once("include/html2plain.php");
require_once('include/security.php');
require_once('include/photos.php');
require_once('include/items.php');
require_once('include/attach.php');
require_once('include/api_auth.php');
require_once('include/api_zot.php');

	/*
	 *
	 * Hubzilla API. 
	 *
	 */


	$API = array();

	$called_api = Null;

	// All commands which require authentication accept a "channel" parameter
	// which is the left hand side of the channel address/nickname.
	// If provided, the desired channel is selected before caarying out the command.
	// If not provided, the default channel associated with the account is used.   
	// If channel selection fails, the API command requiring login will fail. 

	function api_user() {
		$aid = get_account_id();
		$channel = App::get_channel();
		
		if(($aid) && (x($_REQUEST,'channel'))) {

			// Only change channel if it is different than the current channel

			if($channel && x($channel,'channel_address') && $channel['channel_address'] != $_REQUEST['channel']) {
				$c = q("select channel_id from channel where channel_address = '%s' and channel_account_id = %d limit 1",
					dbesc($_REQUEST['channel']),
					intval($aid)
				);
				if((! $c) || (! change_channel($c[0]['channel_id'])))
					return false;
			}
		}			
		if ($_SESSION['allow_api'])
			return local_channel();
		return false;
	}


	function api_date($str){
		//Wed May 23 06:01:13 +0000 2007
		return datetime_convert('UTC', 'UTC', $str, 'D M d H:i:s +0000 Y' );
	}


	function api_register_func($path, $func, $auth = false) {
		\Zotlabs\Lib\Api_router::register($path,$func,$auth);
	}

	
	/**************************
	 *  MAIN API ENTRY POINT  *
	 **************************/

	function api_call(){

		$p    = App::$cmd;
		$type = null;

		if(strrpos($p,'.')) {
			$type = substr($p,strrpos($p,'.')+1);
			if(strpos($type,'/') === false) {
				$p = substr($p,0,strrpos($p,'.'));
				// recalculate App argc,argv since we just extracted the type from it
				App::$argv = explode('/',$p);
				App::$argc = count(App::$argv);
			}
		}

		if((! $type) || (! in_array($type, [ 'json', 'xml', 'rss', 'as', 'atom' ])))
			$type = 'json';

		$info = \Zotlabs\Lib\Api_router::find($p);

		if(in_array($type, [ 'rss', 'atom', 'as' ])) {
			// These types no longer supported.
			$info = false;
		}

		logger('API info: ' . $p . ' type: ' . $type . ' ' . print_r($info,true), LOGGER_DEBUG,LOG_INFO);

		if($info) {

			if ($info['auth'] === true && api_user() === false) {
					api_login($a);
			}

			load_contact_links(api_user());

			$channel = App::get_channel();

			logger('API call for ' . $channel['channel_name'] . ': ' . App::$query_string);
			logger('API parameters: ' . print_r($_REQUEST,true));

			$r = call_user_func($info['func'],$type);

			if($r === false) 
				return;

			switch($type) {
				case 'xml':
					header ('Content-Type: text/xml');
					return $r; 
					break;
				case 'json':
					header ('Content-Type: application/json');
					// Lookup JSONP to understand these lines. They provide cross-domain AJAX ability.
					if ($_GET['callback'])
						$r = $_GET['callback'] . '(' . $r . ')' ;
					return $r; 
					break;
			}

		}
	

		header('HTTP/1.1 404 Not Found');
		logger('API call not implemented: ' . App::$query_string . ' - ' . print_r($_REQUEST,true));
		$r = '<status><error>not implemented</error></status>';
		switch($type){
			case 'xml':
				header ('Content-Type: text/xml');
				return '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . $r;
				break;
			case "json":
				header ('Content-Type: application/json');
			    return json_encode(array('error' => 'not implemented'));
				break;
			case "rss":
				header ('Content-Type: application/rss+xml');
				return '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . $r;
				break;
			case "atom":
				header ('Content-Type: application/atom+xml');
				return '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . $r;
				break;
		}
	}

	/**
	 *  load api $templatename for $type and replace $data array
	 */

	function api_apply_template($templatename, $type, $data){

		switch($type){
			case 'xml':
				if($data) {
					foreach($data as $k => $v)
						$ret = arrtoxml(str_replace('$','',$k),$v);
				}
				break;
			case 'json':
			default:
				if($data) {
					foreach($data as $rv) {
						$ret = json_encode($rv);
					}
				}
				break;
		}

		return $ret;
	}
	


	/**
	 * Returns user info array.
	 */

	function api_get_user($contact_id = null, $contact_xchan = null){

		$user = null;
		$extra_query = '';


		if(! is_null($contact_xchan)) {
			$user = local_channel();
			$extra_query = " and abook_xchan = '" . dbesc($contact_xchan) . "' ";
		}
		else {
			if(! is_null($contact_id)){
				$user = $contact_id;
				$extra_query = " AND abook_id = %d ";
			}
		
			if(is_null($user) && x($_GET, 'user_id')) {
				$user = intval($_GET['user_id']);	
				$extra_query = " AND abook_id = %d ";
			}
			if(is_null($user) && x($_GET, 'screen_name')) {
				$user = dbesc($_GET['screen_name']);	
				$extra_query = " AND xchan_addr like '%s@%%' ";
				if(api_user() !== false)
					$extra_query .= " AND abook_channel = " . intval(api_user());
			}
		}
		
		if (! $user) {
			if (api_user() === false) {
				api_login($a); 
				return false;
			} else {
				$user = local_channel();
				$extra_query = " AND abook_channel = %d AND abook_self = 1 ";
			}
			
		}
		
		logger('api_user: ' . $extra_query . ', user: ' . $user, LOGGER_DATA, LOG_INFO);

		// user info		

		$uinfo = q("SELECT * from abook left join xchan on abook_xchan = xchan_hash 
				WHERE true
				$extra_query",
				$user
		);

		if (! $uinfo) {
			return false;
		}

		$following = false;
		
		if(intval($uinfo[0]['abook_self'])) {
			$usr = q("select * from channel where channel_id = %d limit 1",
				intval(api_user())
			);
			$profile = q("select * from profile where uid = %d and is_default = 1 limit 1",
				intval(api_user())
			);

			$item_normal = item_normal();

			// count public wall messages
			$r = q("SELECT COUNT(id) as total FROM item
					WHERE uid = %d
					AND item_wall = 1 $item_normal 
					AND allow_cid = '' AND allow_gid = '' AND deny_cid = '' AND deny_gid = ''
					AND item_private = 0 ",
					intval($usr[0]['channel_id'])
			);
			if($r) {
				$countitms = $r[0]['total'];
				$following = true;
			}
		}
		else {
			$r = q("SELECT COUNT(id) as total FROM item
					WHERE author_xchan = '%s'
					AND allow_cid = '' AND allow_gid = '' AND deny_cid = '' AND deny_gid = ''
					AND item_private = 0 ",
					intval($uinfo[0]['xchan_hash'])
			);
			if($r) {
				$countitms = $r[0]['total'];
			}		
			$following = ((get_abconfig($uinfo[0]['abook_channel'],$uinfo[0]['abook_xchan'],'my_perms','view_stream')) ? true : false );
		}

		// count friends
		if($usr) {
			$r = q("SELECT COUNT(abook_id) as total FROM abook
					WHERE abook_channel = %d AND abook_self = 0 ",
					intval($usr[0]['channel_id'])
			);
			if($r) {
				$countfriends = $r[0]['total'];
				$countfollowers = $r[0]['total'];
			}
		}

		$r = q("SELECT count(id) as total FROM item where item_starred = 1 and uid = %d " . item_normal(),
			intval($uinfo[0]['channel_id'])
		);
		if($r)
			$starred = $r[0]['total'];
	
		if(! intval($uinfo[0]['abook_self'])) {
			$countfriends = 0;
			$countfollowers = 0;
			$starred = 0;
		}

		$ret = array(
			'id' => intval($uinfo[0]['abook_id']),
			'self' => (intval($uinfo[0]['abook_self']) ? 1 : 0),
			'uid' => intval($uinfo[0]['abook_channel']),
			'guid' => $uinfo[0]['xchan_hash'],
			'name' => (($uinfo[0]['xchan_name']) ? $uinfo[0]['xchan_name'] : substr($uinfo[0]['xchan_addr'],0,strpos($uinfo[0]['xchan_addr'],'@'))),
			'screen_name' => substr($uinfo[0]['xchan_addr'],0,strpos($uinfo[0]['xchan_addr'],'@')),
			'location' => ($usr) ? $usr[0]['channel_location'] : '',
			'profile_image_url' => $uinfo[0]['xchan_photo_l'],
			'url' => $uinfo[0]['xchan_url'],
			'contact_url' => z_root() . '/connections/'.$uinfo[0]['abook_id'],
			'protected' => false,	
			'friends_count' => intval($countfriends),
			'created_at' => api_date($uinfo[0]['abook_created']),
			'utc_offset' => '+00:00',
			'time_zone' => 'UTC', //$uinfo[0]['timezone'],
			'geo_enabled' => false,
			'statuses_count' => intval($countitms), //#XXX: fix me 
			'lang' => App::$language,
			'description' => (($profile) ? $profile[0]['pdesc'] : ''),
			'followers_count' => intval($countfollowers),
			'favourites_count' => intval($starred),
			'contributors_enabled' => false,
			'follow_request_sent' => true,
			'profile_background_color' => 'cfe8f6',
			'profile_text_color' => '000000',
			'profile_link_color' => 'FF8500',
			'profile_sidebar_fill_color' =>'AD0066',
			'profile_sidebar_border_color' => 'AD0066',
			'profile_background_image_url' => '',
			'profile_background_tile' => false,
			'profile_use_background_image' => false,
			'notifications' => false,
			'following' => $following,
			'verified' => true // #XXX: fix me
		);

		$x = api_get_status($uinfo[0]['xchan_hash']);
		if($x)
			$ret['status'] = $x;

//		logger('api_get_user: ' . print_r($ret,true));

		return $ret;
		
	}

	function api_client_register($type) {

		$ret = array();
		$key = random_string(16);
		$secret = random_string(16);
		$name = trim(escape_tags($_REQUEST['application_name']));
		if(! $name)
			json_return_and_die($ret);
		if(is_array($_REQUEST['redirect_uris']))
			$redirect = trim($_REQUEST['redirect_uris'][0]);
		else
			$redirect = trim($_REQUEST['redirect_uris']);
		$icon = trim($_REQUEST['logo_uri']);
		$r = q("INSERT INTO clients (client_id, pw, clname, redirect_uri, icon, uid)
			VALUES ('%s','%s','%s','%s','%s',%d)",
			dbesc($key),
			dbesc($secret),
			dbesc($name),
			dbesc($redirect),
			dbesc($icon),
			intval(0)
		);

		$ret['client_id'] = $key;
		$ret['client_secret'] = $secret;
		$ret['expires_at'] = 0;
		json_return_and_die($ret);
	}



	function api_oauth_request_token( $type){
		try{
			$oauth = new ZotOAuth1();
			$req = OAuth1Request::from_request();
			logger('Req: ' . var_export($req,true),LOGGER_DATA);
			$r = $oauth->fetch_request_token($req);
		}catch(Exception $e){
			logger('oauth_exception: ' . print_r($e->getMessage(),true));
			echo 'error=' . OAuth1Util::urlencode_rfc3986($e->getMessage()); 
			killme();
		}
		echo $r;
		killme();	
	}

	function api_oauth_access_token( $type){
		try{
			$oauth = new ZotOAuth1();
			$req   = OAuth1Request::from_request();
			$r     = $oauth->fetch_access_token($req);
		}
		catch(Exception $e) {
			echo 'error=' . OAuth1Util::urlencode_rfc3986($e->getMessage()); 
			killme();
		}
		echo $r;
		killme();			
	}


