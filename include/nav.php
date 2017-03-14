<?php /** @file */

use \Zotlabs\Lib as Zlib;

function nav() {

	/**
	 *
	 * Build page header and site navigation bars
	 *
	 */

	if(!(x(App::$page,'nav')))
		App::$page['nav'] = '';

	$base = z_root();

	App::$page['htmlhead'] .= <<< EOT
<script>$(document).ready(function() {
	$("#nav-search-text").search_autocomplete('$base/acl');
});

</script>
EOT;

	if(local_channel()) {
		$channel = App::get_channel();
		$observer = App::get_observer();
		$prof = q("select id from profile where uid = %d and is_default = 1",
			intval($channel['channel_id'])
		);

		$chans = q("select channel_name, channel_id from channel where channel_account_id = %d and channel_removed = 0 order by channel_name ",
			intval(get_account_id())
		);
	}
	elseif(remote_channel())
		$observer = App::get_observer();
	

	$myident = (($channel) ? $channel['xchan_addr'] : '');
		
	$sitelocation = (($myident) ? $myident : App::get_hostname());

	/**
	 *
	 * Provide a banner/logo/whatever
	 *
	 */

	$banner = get_config('system','banner');

	if($banner === false) 
		$banner = get_config('system','sitename');

	//the notifications template is in hdr.tpl
	App::$page['header'] .= replace_macros(get_markup_template('hdr.tpl'), array(
		//we could additionally use this to display important system notifications e.g. for updates
	));

	$server_role = get_config('system','server_role');
	$basic = (($server_role === 'basic') ? true : false);
	$techlevel = get_account_techlevel();

	// nav links: array of array('href', 'text', 'extra css classes', 'title')
	$nav = [];

	/**
	 * Display login or logout
	 */	

	$nav['usermenu'] = [];
	$userinfo = null;
	$nav['loginmenu'] = [];

	if($observer) {
		$userinfo = [
			'icon' => $observer['xchan_photo_m'],
			'name' => $observer['xchan_addr'],
		];
	}

	elseif(! $_SESSION['authenticated']) {
		$nav['remote_login'] = remote_login();
		$nav['loginmenu'][] = Array('rmagic',t('Remote authentication'),'',t('Click to authenticate to your home hub'),'rmagic_nav_btn');
	}



	if(local_channel()) {


		if($chans && count($chans) > 1 && feature_enabled(local_channel(),'nav_channel_select') && (! $basic))
			$nav['channels'] = $chans;

		$nav['logout'] = ['logout',t('Logout'), "", t('End this session'),'logout_nav_btn'];
		
		// user menu
		$nav['usermenu'][] = ['profile/' . $channel['channel_address'], t('View Profile'), "", t('Your profile page'),'profile_nav_btn'];

		if(feature_enabled(local_channel(),'multi_profiles') && (! $basic))
			$nav['usermenu'][]   = ['profiles', t('Edit Profiles'),"", t('Manage/Edit profiles'),'profiles_nav_btn'];
		else
			$nav['usermenu'][]   = ['profiles/' . $prof[0]['id'], t('Edit Profile'),"", t('Edit your profile'),'profiles_nav_btn'];

	}
	else {
		if(! get_account_id())  {
			$nav['login'] = login(true,'main-login',false,false);
			$nav['loginmenu'][] = ['login',t('Login'),'',t('Sign in'),'login_nav_btn'];
			App::$page['content'] .= replace_macros(get_markup_template('nav_login.tpl'),
				[ 
					'$nav' => $nav,
					'userinfo' => $userinfo
				]
			);

		}
		else
			$nav['alogout'] = ['logout',t('Logout'), "", t('End this session'),'logout_nav_btn'];


	}


	$homelink = get_my_url();
	if(! $homelink) {
		$observer = App::get_observer();
		$homelink = (($observer) ? $observer['xchan_url'] : '');
	}

	if(! local_channel()) {
		$nav['rusermenu'] = array(
			$homelink,
			t('Take me home'),
			'logout',
			t('Log me out of this site')
		);
	}

	if(((get_config('system','register_policy') == REGISTER_OPEN) || (get_config('system','register_policy') == REGISTER_APPROVE)) && (! $_SESSION['authenticated']))
		$nav['register'] = ['register',t('Register'), "", t('Create an account'),'register_nav_btn'];

	if(! get_config('system','hide_help')) {
		$help_url = z_root() . '/help?f=&cmd=' . App::$cmd;
		$context_help = '';
		$enable_context_help = ((intval(get_config('system','enable_context_help')) === 1 || get_config('system','enable_context_help') === false) ? true : false);
		if($enable_context_help === true) {
			require_once('include/help.php');
			$context_help = load_context_help();
			//point directly to /help if $context_help is empty - this can be removed once we have context help for all modules
			$enable_context_help = (($context_help) ? true : false);
		}
		$nav['help'] = [$help_url, t('Help'), "", t('Help and documentation'), 'help_nav_btn', $context_help, $enable_context_help];
	}

	$nav['search'] = ['search', t('Search'), "", t('Search site @name, #tag, ?docs, content')];


	/**
	 *
	 * The following nav links are only show to logged in users
	 *
	 */

	if(local_channel()) {

	
		$nav['network'] = array('network', t('Grid'), "", t('Your grid'),'network_nav_btn');
		$nav['network']['all'] = [ 'network', t('View your network/grid'), '','' ];
		$nav['network']['mark'] = array('', t('Mark all grid notifications seen'), '','');

		$nav['home'] = array('channel/' . $channel['channel_address'], t('Channel Home'), "", t('Channel home'),'home_nav_btn');
		$nav['home']['all'] = [ 'channel/' . $channel['channel_address'], t('View your channel home'), '' , '' ];
		$nav['home']['mark'] = array('', t('Mark all channel notifications seen'), '','');


		$nav['intros'] = array('connections/ifpending',	t('Connections'), "", t('Connections'),'connections_nav_btn');


		$nav['notifications'] = array('notifications/system',	t('Notices'), "", t('Notifications'),'notifications_nav_btn');
		$nav['notifications']['all']=array('notifications/system', t('View all notifications'), "", "");
		$nav['notifications']['mark'] = array('', t('Mark all system notifications seen'), '','');

		$nav['messages'] = array('mail/combined', t('Mail'), "", t('Private mail'),'mail_nav_btn');
		$nav['messages']['all']=array('mail/combined', t('View your private messages'), "", "");
		$nav['messages']['mark'] = array('', t('Mark all private messages seen'), '','');
		$nav['messages']['inbox'] = array('mail/inbox', t('Inbox'), "", t('Inbox'));
		$nav['messages']['outbox']= array('mail/outbox', t('Outbox'), "", t('Outbox'));
		$nav['messages']['new'] = array('mail/new', t('New Message'), "", t('New Message'));


		$nav['all_events'] = array('events', t('Events'), "", t('Event Calendar'),'events_nav_btn');
		$nav['all_events']['all']=array('events', t('View events'), "", "");
		$nav['all_events']['mark'] = array('', t('Mark all events seen'), '','');

		if(! $basic)		
			$nav['manage'] = array('manage', t('Channel Manager'), "", t('Manage Your Channels'),'manage_nav_btn');

		$nav['settings'] = array('settings', t('Settings'),"", t('Account/Channel Settings'),'settings_nav_btn');

	}

	/**
	 * Admin page
	 */
	 if (is_site_admin()){
		 $nav['admin'] = array('admin/', t('Admin'), "", t('Site Setup and Configuration'),'admin_nav_btn');
	 }


	/**
	 *
	 * Provide a banner/logo/whatever
	 *
	 */

	$banner = get_config('system','banner');

	if($banner === false) 
		$banner = get_config('system','sitename');

	$x = array('nav' => $nav, 'usermenu' => $userinfo );

	call_hooks('nav', $x);

	// Not sure the best place to put this on the page. So I'm implementing it but leaving it 
	// turned off until somebody discovers this and figures out a good location for it. 
	$powered_by = '';

	//app bin
	if(local_channel()) {
		if(get_pconfig(local_channel(), 'system','initial_import_system_apps') === false) {
			Zlib\Apps::import_system_apps();
			set_pconfig(local_channel(), 'system','initial_import_system_apps', 1);
		}

		$syslist = array();
		$list = Zlib\Apps::app_list(local_channel(), false, 'nav_featured_app');
		if($list) {
			foreach($list as $li) {
				$syslist[] = Zlib\Apps::app_encode($li);
			}
		}
		Zlib\Apps::translate_system_apps($syslist);
	}
	else {
		$syslist = Zlib\Apps::get_system_apps(true);
	}

	usort($syslist,'Zotlabs\\Lib\\Apps::app_name_compare');

	foreach($syslist as $app) {
		$navapps[] = Zlib\Apps::app_render($app,'nav');
	}

	$tpl = get_markup_template('nav.tpl');

	App::$page['nav'] .= replace_macros($tpl, array(
		'$baseurl' => z_root(),
		'$fulldocs' => t('Help'),
		'$sitelocation' => $sitelocation,
		'$nav' => $x['nav'],
		'$banner' =>  $banner,
		'$emptynotifications' => t('Loading...'),
		'$userinfo' => $x['usermenu'],
		'$localuser' => local_channel(),
		'$sel' => 	App::$nav_sel,
		'$powered_by' => $powered_by,
		'$help' => t('@name, #tag, ?doc, content'),
		'$pleasewait' => t('Please wait...'),
		'$navapps' => $navapps,
		'$addapps' => t('Add Apps')
	));

	if(x($_SESSION, 'reload_avatar') && $observer) {
		// The avatar has been changed on the server but the browser doesn't know that, 
		// force the browser to reload the image from the server instead of its cache.
		$tpl = get_markup_template('force_image_reload.tpl');

		App::$page['nav'] .= replace_macros($tpl, array(
			'$imgUrl' => $observer['xchan_photo_m']
		));
		unset($_SESSION['reload_avatar']);
	}


	call_hooks('page_header', App::$page['nav']);
}

/*
 * Set a menu item in navbar as selected
 * 
 */
function nav_set_selected($item){
    App::$nav_sel = array(
		'community' 	=> null,
		'network' 		=> null,
		'home'			=> null,
		'profiles'		=> null,
		'intros'        => null,
		'notifications'	=> null,
		'messages'		=> null,
		'directory'	    => null,
		'settings'		=> null,
		'contacts'		=> null,
		'manage'        => null,
		'register'      => null,
	);
	App::$nav_sel[$item] = 'active';
}
