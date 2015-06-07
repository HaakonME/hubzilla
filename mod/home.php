<?php

require_once('include/items.php');
require_once('include/conversation.php');


function home_init(&$a) {

	$ret = array();

	call_hooks('home_init',$ret);

	$splash = ((argc() > 1 && argv(1) === 'splash') ? true : false);

	$channel = $a->get_channel();
	if(local_channel() && $channel && $channel['xchan_url'] && ! $splash) {
		$dest = $channel['channel_startpage'];
		if(! $dest)
			$dest = get_pconfig(local_channel(),'system','startpage');
		if(! $dest)
			$dest = get_config('system','startpage');
		if(! $dest)
			$dest = z_root() . '/network';

		goaway($dest);
	}

	if(get_account_id() && ! $splash) {
		goaway(z_root() . '/new_channel');
	}

}


function home_content(&$a, $update = 0, $load = false) {

	$o = '';


	if(x($_SESSION,'theme'))
		unset($_SESSION['theme']);
	if(x($_SESSION,'mobile_theme'))
		unset($_SESSION['mobile_theme']);

	$splash = ((argc() > 1 && argv(1) === 'splash') ? true : false);


	call_hooks('home_content',$o);
	if($o)
		return $o;

	$startpage = get_config('system','frontpage');
	if($startpage) {
		if(strpos($startpage,'include:') !== false) {
			$file = trim(str_replace('include:' , '', $startpage));
			if(file_exists($file)) {
				$o .= file_get_contents($file);
				return $o;
			}
		}
		goaway($z_root() . '/' . $startpage);
	}

	$sitename = get_config('system','sitename');
	if($sitename) 
		$o .= '<h1>' . sprintf( t("Welcome to %s") ,$sitename) . '</h1>';

	if(intval(get_config('system','block_public')) && (! local_channel()) && (! remote_channel())) {
		// If there's nothing special happening, just spit out a login box
		if (! $a->config['system']['no_login_on_homepage'])
			$o .= login(($a->config['system']['register_policy'] == REGISTER_CLOSED) ? 0 : 1);
	}

	return $o;

}