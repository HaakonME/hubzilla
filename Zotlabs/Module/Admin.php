<?php
namespace Zotlabs\Module;

use \Zotlabs\Storage\GitRepo as GitRepo;

/**
 * @file mod/admin.php
 * @brief Hubzilla's admin controller.
 *
 * Controller for the /admin/ area.
 */

require_once('include/queue_fn.php');
require_once('include/account.php');

/**
 * @param App &$a
 */

class Admin extends \Zotlabs\Web\Controller {

	function post(){
		logger('admin_post', LOGGER_DEBUG);
	
		if(! is_site_admin()) {
			return;
		}
	
		// urls
		if (argc() > 1) {
			switch (argv(1)) {
				case 'site':
					$this->admin_page_site_post($a);
					break;
				case 'accounts':
					$this->admin_page_accounts_post($a);
					break;
				case 'channels':
					$this->admin_page_channels_post($a);
					break;
				case 'plugins':
					if (argc() > 2 && argv(2) === 'addrepo') {
						$this->admin_page_plugins_post('addrepo');
						break;
					}
					if (argc() > 2 && argv(2) === 'installrepo') {
						$this->admin_page_plugins_post('installrepo');
						break;
					}
					if (argc() > 2 && argv(2) === 'removerepo') {
						$this->admin_page_plugins_post('removerepo');
						break;
					}
					if (argc() > 2 && argv(2) === 'updaterepo') {
						$this->admin_page_plugins_post('updaterepo');
						break;
					}
					if (argc() > 2 && 
						is_file("addon/" . argv(2) . "/" . argv(2) . ".php")){
							@include_once("addon/" . argv(2) . "/" . argv(2) . ".php");
							if(function_exists(argv(2).'_plugin_admin_post')) {
								$func = argv(2) . '_plugin_admin_post';
								$func($a);
							}
					}
					goaway(z_root() . '/admin/plugins/' . argv(2) );
					break;
				case 'themes':
					$theme = argv(2);
					if (is_file("view/theme/$theme/php/config.php")){
						require_once("view/theme/$theme/php/config.php");
	// fixme add parent theme if derived
						if (function_exists("theme_admin_post")){
							theme_admin_post($a);
						}
					}
					info(t('Theme settings updated.'));
					if(is_ajax()) return;
	
					goaway(z_root() . '/admin/themes/' . $theme );
					break;
				case 'logs':
					$this->admin_page_logs_post($a);
					break;
				case 'hubloc':
					$this->admin_page_hubloc_post($a);
					break;
				case 'security':
					$this->admin_page_security_post($a);
					break;
				case 'features':
					$this->admin_page_features_post($a);
					break;
				case 'dbsync':
					$this->admin_page_dbsync_post($a);
					break;
				case 'profs':
					$this->admin_page_profs_post($a);
					break;
			}
		}
	
		goaway(z_root() . '/admin' );
	}
	
	/**
	 * @param App &$a
	 * @return string
	 */
	function get() {
	
		logger('admin_content', LOGGER_DEBUG);
	
		if(! is_site_admin()) {
			return login(false);
		}
	
	
		/*
		 * Page content
		 */
		$o = '';
	
		// urls
		if (argc() > 1){
			switch (argv(1)) {
				case 'site':
					$o = $this->admin_page_site($a);
					break;
				case 'accounts':
					$o = $this->admin_page_accounts($a);
					break;
				case 'channels':
					$o = $this->admin_page_channels($a);
					break;
				case 'plugins':
					$o = $this->admin_page_plugins($a);
					break;
				case 'themes':
					$o = $this->admin_page_themes($a);
					break;
	//			case 'hubloc':
	//				$o = $this->admin_page_hubloc($a);
	//				break;
				case 'security':
					$o = $this->admin_page_security($a);
					break;
				case 'features':
					$o = $this->admin_page_features($a);
					break;
				case 'logs':
					$o = $this->admin_page_logs($a);
					break;
				case 'dbsync':
					$o = $this->admin_page_dbsync($a);
					break;
				case 'profs':
					$o = $this->admin_page_profs($a);
					break;
				case 'queue':
					$o = $this->admin_page_queue($a);
					break;
				default:
					notice( t('Item not found.') );
			}
		} else {
			$o = $this->admin_page_summary($a);
		}
	
		if(is_ajax()) {
			echo $o; 
			killme();
			return '';
		} else {
			return $o;
		}
	}
	
	
	/**
	 * @brief Returns content for Admin Summary Page.
	 *
	 * @param App &$a
	 * @return string HTML from parsed admin_summary.tpl
	 */
	function admin_page_summary(&$a) {
	
		// list total user accounts, expirations etc.
		$accounts = array();
		$r = q("SELECT COUNT(*) AS total, COUNT(CASE WHEN account_expires > %s THEN 1 ELSE NULL END) AS expiring, COUNT(CASE WHEN account_expires < %s AND account_expires != '%s' THEN 1 ELSE NULL END) AS expired, COUNT(CASE WHEN (account_flags & %d)>0 THEN 1 ELSE NULL END) AS blocked FROM account",
			db_utcnow(),
			db_utcnow(),
			dbesc(NULL_DATE),
			intval(ACCOUNT_BLOCKED)
		);
		if ($r) {
			$accounts['total']    = array('label' => t('# Accounts'), 'val' => $r[0]['total']);
			$accounts['blocked']  = array('label' => t('# blocked accounts'), 'val' => $r[0]['blocked']);
			$accounts['expired']  = array('label' => t('# expired accounts'), 'val' => $r[0]['expired']);
			$accounts['expiring'] = array('label' => t('# expiring accounts'), 'val' => $r[0]['expiring']);
		}
	
		// pending registrations
		$r = q("SELECT COUNT(id) AS `count` FROM `register` WHERE `uid` != '0'");
		$pending = $r[0]['count'];
	
		// available channels, primary and clones
		$channels = array();
		$r = q("SELECT COUNT(*) AS total, COUNT(CASE WHEN channel_primary = 1 THEN 1 ELSE NULL END) AS main, COUNT(CASE WHEN channel_primary = 0 THEN 1 ELSE NULL END) AS clones FROM channel WHERE channel_removed = 0");
		if ($r) {
			$channels['total']  = array('label' => t('# Channels'), 'val' => $r[0]['total']);
			$channels['main']   = array('label' => t('# primary'), 'val' => $r[0]['main']);
			$channels['clones'] = array('label' => t('# clones'), 'val' => $r[0]['clones']);
		}
	
		// We can do better, but this is a quick queue status
		$r = q("SELECT COUNT(outq_delivered) AS total FROM outq WHERE outq_delivered = 0");
		$queue = (($r) ? $r[0]['total'] : 0);
		$queues = array( 'label' => t('Message queues'), 'queue' => $queue );
	
		// If no plugins active return 0, otherwise list of plugin names
		$plugins = (count(\App::$plugins) == 0) ? count(\App::$plugins) : \App::$plugins;
	
		// Could be extended to provide also other alerts to the admin
		$alertmsg = '';
		// annoy admin about upcoming unsupported PHP version
		if (version_compare(PHP_VERSION, '5.4', '<')) {
			$alertmsg = 'Your PHP version ' . PHP_VERSION . ' will not be supported with the next major release of $Projectname. You are strongly urged to upgrade to a current version.'
				. '<br>PHP 5.3 has reached its <a href="http://php.net/eol.php" class="alert-link">End of Life (EOL)</a> in August 2014.'
				. ' A list about current PHP versions can be found <a href="http://php.net/supported-versions.php" class="alert-link">here</a>.';
		}

		$vmaster = get_repository_version('master');
		$vdev = get_repository_version('dev');

		$upgrade = ((version_compare(STD_VERSION,$vmaster) < 0) ? t('Your software should be updated') : '');


		$t = get_markup_template('admin_summary.tpl');
		return replace_macros($t, array(
			'$title' => t('Administration'),
			'$page' => t('Summary'),
			'$adminalertmsg' => $alertmsg,
			'$queues'   => $queues,
			'$accounts' => array( t('Registered accounts'), $accounts),
			'$pending'  => array( t('Pending registrations'), $pending),
			'$channels' => array( t('Registered channels'), $channels),
			'$plugins'  => array( t('Active plugins'), $plugins ),
			'$version'  => array( t('Version'), STD_VERSION),
			'$vmaster'  => array( t('Repository version (master)'), $vmaster),
			'$vdev'     => array( t('Repository version (dev)'), $vdev),
			'$upgrade'  => $upgrade,
			'$build' => get_config('system', 'db_version')
		));
	}
	
	
	/**
	 * @brief POST handler for Admin Site Page.
	 *
	 * @param App &$a
	 */
	function admin_page_site_post(&$a){
		if (!x($_POST, 'page_site')){
			return;
		}
	
		check_form_security_token_redirectOnErr('/admin/site', 'admin_site');
	
		$sitename 			=	((x($_POST,'sitename'))			? notags(trim($_POST['sitename']))			: '');
		$banner				=	((x($_POST,'banner'))      		? trim($_POST['banner'])				: false);
		$admininfo			=	((x($_POST,'admininfo'))		? trim($_POST['admininfo'])				: false);
		$language			=	((x($_POST,'language'))			? notags(trim($_POST['language']))			: '');
		$theme				=	((x($_POST,'theme'))			? notags(trim($_POST['theme']))				: '');
		$theme_mobile			=	((x($_POST,'theme_mobile'))		? notags(trim($_POST['theme_mobile']))			: '');
	//	$site_channel			=	((x($_POST,'site_channel'))	? notags(trim($_POST['site_channel']))				: '');
		$maximagesize		=	((x($_POST,'maximagesize'))		? intval(trim($_POST['maximagesize']))				:  0);
	
		$register_policy	=	((x($_POST,'register_policy'))	? intval(trim($_POST['register_policy']))	:  0);
		
		$access_policy	=	((x($_POST,'access_policy'))	? intval(trim($_POST['access_policy']))	:  0);
		$invite_only        = ((x($_POST,'invite_only'))		? True	: False);
		$abandon_days	    =	((x($_POST,'abandon_days'))	    ? intval(trim($_POST['abandon_days']))	    :  0);
	
		$register_text		=	((x($_POST,'register_text'))	? notags(trim($_POST['register_text']))		: '');
		$frontpage		    =	((x($_POST,'frontpage'))	? notags(trim($_POST['frontpage']))		: '');
		$mirror_frontpage   =	((x($_POST,'mirror_frontpage'))	? intval(trim($_POST['mirror_frontpage']))		: 0);
		$directory_server   =   ((x($_POST,'directory_server')) ? trim($_POST['directory_server']) : '');
		$allowed_sites        = ((x($_POST,'allowed_sites'))	? notags(trim($_POST['allowed_sites']))		: '');
		$allowed_email        = ((x($_POST,'allowed_email'))	? notags(trim($_POST['allowed_email']))		: '');
		$not_allowed_email    = ((x($_POST,'not_allowed_email'))	? notags(trim($_POST['not_allowed_email']))		: '');
		$force_publish        = ((x($_POST,'publish_all'))		? True	: False);
		$disable_discover_tab = ((x($_POST,'disable_discover_tab'))		? False	:	True);
		$login_on_homepage    = ((x($_POST,'login_on_homepage'))		? True	:	False);
		$enable_context_help    = ((x($_POST,'enable_context_help'))		? True	:	False);
		$global_directory     = ((x($_POST,'directory_submit_url'))	? notags(trim($_POST['directory_submit_url']))	: '');
		$no_community_page    = !((x($_POST,'no_community_page'))	? True	:	False);
		$default_expire_days  = ((array_key_exists('default_expire_days',$_POST)) ? intval($_POST['default_expire_days']) : 0);
	
		$verifyssl         = ((x($_POST,'verifyssl'))        ? True : False);
		$proxyuser         = ((x($_POST,'proxyuser'))        ? notags(trim($_POST['proxyuser']))  : '');
		$proxy             = ((x($_POST,'proxy'))            ? notags(trim($_POST['proxy']))      : '');
		$timeout           = ((x($_POST,'timeout'))          ? intval(trim($_POST['timeout']))    : 60);
		$delivery_interval = ((x($_POST,'delivery_interval'))? intval(trim($_POST['delivery_interval'])) : 0);
		$delivery_batch_count = ((x($_POST,'delivery_batch_count') && $_POST['delivery_batch_count'] > 0)? intval(trim($_POST['delivery_batch_count'])) : 1);
		$poll_interval     = ((x($_POST,'poll_interval'))    ? intval(trim($_POST['poll_interval'])) : 0);
		$maxloadavg        = ((x($_POST,'maxloadavg'))       ? intval(trim($_POST['maxloadavg'])) : 50);
		$feed_contacts     = ((x($_POST,'feed_contacts'))    ? intval($_POST['feed_contacts'])    : 0);
		$verify_email      = ((x($_POST,'verify_email'))     ? 1 : 0);
	
		set_config('system', 'feed_contacts', $feed_contacts);
		set_config('system', 'delivery_interval', $delivery_interval);
		set_config('system', 'delivery_batch_count', $delivery_batch_count);
		set_config('system', 'poll_interval', $poll_interval);
		set_config('system', 'maxloadavg', $maxloadavg);
		set_config('system', 'frontpage', $frontpage);
		set_config('system', 'mirror_frontpage', $mirror_frontpage);
		set_config('system', 'sitename', $sitename);
		set_config('system', 'login_on_homepage', $login_on_homepage);
		set_config('system', 'enable_context_help', $enable_context_help);
		set_config('system', 'verify_email', $verify_email);
		set_config('system', 'default_expire_days', $default_expire_days);
	
		if($directory_server)
			set_config('system','directory_server',$directory_server);
	
		if ($banner == '') {
			del_config('system', 'banner');
		} else {
			set_config('system', 'banner', $banner);
		}
	
		if ($admininfo == ''){
			del_config('system', 'admininfo');
		} else {
			require_once('include/text.php');
			linkify_tags($a, $admininfo, local_channel());
			set_config('system', 'admininfo', $admininfo);
		}
		set_config('system', 'language', $language);
		set_config('system', 'theme', $theme);
		if ( $theme_mobile === '---' ) {
			del_config('system', 'mobile_theme');
		} else {
			set_config('system', 'mobile_theme', $theme_mobile);
		}
	//	set_config('system','site_channel', $site_channel);
		set_config('system','maximagesize', $maximagesize);
	
		set_config('system','register_policy', $register_policy);
		set_config('system','invitation_only', $invite_only);	
		set_config('system','access_policy', $access_policy);
		set_config('system','account_abandon_days', $abandon_days);
		set_config('system','register_text', $register_text);
		set_config('system','allowed_sites', $allowed_sites);
		set_config('system','allowed_email', $allowed_email);
		set_config('system','not_allowed_email', $not_allowed_email);	
		set_config('system','publish_all', $force_publish);
		set_config('system','disable_discover_tab', $disable_discover_tab);
		if ($global_directory == '') {
			del_config('system', 'directory_submit_url');
		} else {
			set_config('system', 'directory_submit_url', $global_directory);
		}
	
		set_config('system','no_community_page', $no_community_page);
		set_config('system','no_utf', $no_utf);
		set_config('system','verifyssl', $verifyssl);
		set_config('system','proxyuser', $proxyuser);
		set_config('system','proxy', $proxy);
		set_config('system','curl_timeout', $timeout);
	
		info( t('Site settings updated.') . EOL);
		goaway(z_root() . '/admin/site' );
	}
	
	/**
	 * @brief Admin page site.
	 *
	 * @param  App $a
	 * @return string
	 */
	function admin_page_site(&$a) {
	
		/* Installed langs */
		$lang_choices = array();
		$langs = glob('view/*/hstrings.php');
	
		if(is_array($langs) && count($langs)) {
			if(! in_array('view/en/hstrings.php',$langs))
				$langs[] = 'view/en/';
			asort($langs);
			foreach($langs as $l) {
				$t = explode("/",$l);
				$lang_choices[$t[1]] = $t[1];
			}
		}
	
		/* Installed themes */
		$theme_choices_mobile["---"] = t("Default");
		$theme_choices = array();
		$files = glob('view/theme/*');
		if($files) {
			foreach($files as $file) {
				$vars = '';
				$f = basename($file);
				if (file_exists($file . '/library'))
					continue;
				if (file_exists($file . '/mobile'))
					$vars = t('mobile');
				if (file_exists($file . '/experimental'))
					$vars .= t('experimental');
				if (file_exists($file . '/unsupported'))
					$vars .= t('unsupported');
				if ($vars) {
					$theme_choices[$f] = $f . ' (' . $vars . ')';
					$theme_choices_mobile[$f] = $f . ' (' . $vars . ')';
				}
				else {
					$theme_choices[$f] = $f;
					$theme_choices_mobile[$f] = $f;
				}
			}
		}
	
		$dir_choices = null;
		$dirmode = get_config('system','directory_mode');
		$realm = get_directory_realm();
	
		// directory server should not be set or settable unless we are a directory client
	
		if($dirmode == DIRECTORY_MODE_NORMAL) {
			$x = q("select site_url from site where site_flags in (%d,%d) and site_realm = '%s'",
				intval(DIRECTORY_MODE_SECONDARY),
				intval(DIRECTORY_MODE_PRIMARY),
				dbesc($realm)
			);
			if($x) {
				$dir_choices = array();
				foreach($x as $xx) {
					$dir_choices[$xx['site_url']] = $xx['site_url'];
				}
			}
		}
	
		/* Banner */
	
		$banner = get_config('system', 'banner');
		if($banner === false) 
			$banner = get_config('system','sitename');
	
		$banner = htmlspecialchars($banner);
	
		/* Admin Info */
		$admininfo = get_config('system', 'admininfo');
	
		/* Register policy */
		$register_choices = Array(
			REGISTER_CLOSED  => t("No"),
			REGISTER_APPROVE => t("Yes - with approval"),
			REGISTER_OPEN    => t("Yes")
		);
	
		/* Acess policy */
		$access_choices = Array(
			ACCESS_PRIVATE => t("My site is not a public server"),
			ACCESS_PAID => t("My site has paid access only"),
			ACCESS_FREE => t("My site has free access only"),
			ACCESS_TIERED => t("My site offers free accounts with optional paid upgrades")
		);
	
	//	$ssl_choices = array(
	//		SSL_POLICY_NONE     => t("No SSL policy, links will track page SSL state"),
	//		SSL_POLICY_FULL     => t("Force all links to use SSL")
	//	);
	
		$discover_tab = get_config('system','disable_discover_tab');
		// $disable public streams by default
		if($discover_tab === false)
			$discover_tab = 1;
		// now invert the logic for the setting.
		$discover_tab = (1 - $discover_tab);
	
	
		$homelogin = get_config('system','login_on_homepage');
		$enable_context_help = get_config('system','enable_context_help');
	
		$t = get_markup_template("admin_site.tpl");
		return replace_macros($t, array(
			'$title' => t('Administration'),
			'$page' => t('Site'),
			'$submit' => t('Submit'),
			'$registration' => t('Registration'),
			'$upload' => t('File upload'),
			'$corporate' => t('Policies'),
			'$advanced' => t('Advanced'),
	
			'$baseurl' => z_root(),
			// name, label, value, help string, extra data...
			'$sitename' 		=> array('sitename', t("Site name"), htmlspecialchars(get_config('system','sitename'), ENT_QUOTES, 'UTF-8'),''),
			'$banner'			=> array('banner', t("Banner/Logo"), $banner, ""),
			'$admininfo'		=> array('admininfo', t("Administrator Information"), $admininfo, t("Contact information for site administrators.  Displayed on siteinfo page.  BBCode can be used here")),
			'$language' 		=> array('language', t("System language"), get_config('system','language'), "", $lang_choices),
			'$theme' 			=> array('theme', t("System theme"), get_config('system','theme'), t("Default system theme - may be over-ridden by user profiles - <a href='#' id='cnftheme'>change theme settings</a>"), $theme_choices),
			'$theme_mobile' 	=> array('theme_mobile', t("Mobile system theme"), get_config('system','mobile_theme'), t("Theme for mobile devices"), $theme_choices_mobile),
	//		'$site_channel' 	=> array('site_channel', t("Channel to use for this website's static pages"), get_config('system','site_channel'), t("Site Channel")),
			'$feed_contacts'    => array('feed_contacts', t('Allow Feeds as Connections'),get_config('system','feed_contacts'),t('(Heavy system resource usage)')), 
			'$maximagesize'		=> array('maximagesize', t("Maximum image size"), intval(get_config('system','maximagesize')), t("Maximum size in bytes of uploaded images. Default is 0, which means no limits.")),
			'$register_policy'	=> array('register_policy', t("Does this site allow new member registration?"), get_config('system','register_policy'), "", $register_choices),
			'$invite_only'		=> array('invite_only', t("Invitation only"), get_config('system','invitation_only'), t("Only allow new member registrations with an invitation code. Above register policy must be set to Yes.")),
			'$access_policy'	=> array('access_policy', t("Which best describes the types of account offered by this hub?"), get_config('system','access_policy'), "This is displayed on the public server site list.", $access_choices),
			'$register_text'	=> array('register_text', t("Register text"), htmlspecialchars(get_config('system','register_text'), ENT_QUOTES, 'UTF-8'), t("Will be displayed prominently on the registration page.")),
			'$frontpage'	=> array('frontpage', t("Site homepage to show visitors (default: login box)"), get_config('system','frontpage'), t("example: 'public' to show public stream, 'page/sys/home' to show a system webpage called 'home' or 'include:home.html' to include a file.")),
			'$mirror_frontpage'	=> array('mirror_frontpage', t("Preserve site homepage URL"), get_config('system','mirror_frontpage'), t('Present the site homepage in a frame at the original location instead of redirecting')),
			'$abandon_days'     => array('abandon_days', t('Accounts abandoned after x days'), get_config('system','account_abandon_days'), t('Will not waste system resources polling external sites for abandonded accounts. Enter 0 for no time limit.')),
			'$allowed_sites'	=> array('allowed_sites', t("Allowed friend domains"), get_config('system','allowed_sites'), t("Comma separated list of domains which are allowed to establish friendships with this site. Wildcards are accepted. Empty to allow any domains")),
			'$allowed_email'	=> array('allowed_email', t("Allowed email domains"), get_config('system','allowed_email'), t("Comma separated list of domains which are allowed in email addresses for registrations to this site. Wildcards are accepted. Empty to allow any domains")),
			'$not_allowed_email'	=> array('not_allowed_email', t("Not allowed email domains"), get_config('system','not_allowed_email'), t("Comma separated list of domains which are not allowed in email addresses for registrations to this site. Wildcards are accepted. Empty to allow any domains, unless allowed domains have been defined.")),
			'$verify_email'		=> array('verify_email', t("Verify Email Addresses"), get_config('system','verify_email'), t("Check to verify email addresses used in account registration (recommended).")),
			'$force_publish'	=> array('publish_all', t("Force publish"), get_config('system','publish_all'), t("Check to force all profiles on this site to be listed in the site directory.")),
			'$disable_discover_tab'	=> array('disable_discover_tab', t('Import Public Streams'), $discover_tab, t('Import and allow access to public content pulled from other sites. Warning: this content is unmoderated.')),
			'$login_on_homepage'	=> array('login_on_homepage', t("Login on Homepage"),((intval($homelogin) || $homelogin === false) ? 1 : '') , t("Present a login box to visitors on the home page if no other content has been configured.")),
			'$enable_context_help'	=> array('enable_context_help', t("Enable context help"),((intval($enable_context_help) === 1 || $enable_context_help === false) ? 1 : 0) , t("Display contextual help for the current page when the help button is pressed.")),
	
			'$directory_server' => (($dir_choices) ? array('directory_server', t("Directory Server URL"), get_config('system','directory_server'), t("Default directory server"), $dir_choices) : null),
	
			'$proxyuser'		=> array('proxyuser', t("Proxy user"), get_config('system','proxyuser'), ""),
			'$proxy'			=> array('proxy', t("Proxy URL"), get_config('system','proxy'), ""),
			'$timeout'			=> array('timeout', t("Network timeout"), (x(get_config('system','curl_timeout'))?get_config('system','curl_timeout'):60), t("Value is in seconds. Set to 0 for unlimited (not recommended).")),
			'$delivery_interval'			=> array('delivery_interval', t("Delivery interval"), (x(get_config('system','delivery_interval'))?get_config('system','delivery_interval'):2), t("Delay background delivery processes by this many seconds to reduce system load. Recommend: 4-5 for shared hosts, 2-3 for virtual private servers. 0-1 for large dedicated servers.")),
			'$delivery_batch_count' => array('delivery_batch_count', t('Deliveries per process'),(x(get_config('system','delivery_batch_count'))?get_config('system','delivery_batch_count'):1), t("Number of deliveries to attempt in a single operating system process. Adjust if necessary to tune system performance. Recommend: 1-5.")),
			'$poll_interval'			=> array('poll_interval', t("Poll interval"), (x(get_config('system','poll_interval'))?get_config('system','poll_interval'):2), t("Delay background polling processes by this many seconds to reduce system load. If 0, use delivery interval.")),
			'$maxloadavg'			=> array('maxloadavg', t("Maximum Load Average"), ((intval(get_config('system','maxloadavg')) > 0)?get_config('system','maxloadavg'):50), t("Maximum system load before delivery and poll processes are deferred - default 50.")),
			'$default_expire_days' => array('default_expire_days', t('Expiration period in days for imported (grid/network) content'), intval(get_config('system','default_expire_days')), t('0 for no expiration of imported content')),
			'$form_security_token' => get_form_security_token("admin_site"),
		));
	}
	
	function admin_page_hubloc_post(&$a){
		check_form_security_token_redirectOnErr('/admin/hubloc', 'admin_hubloc');
		require_once('include/zot.php');
	
		//prepare for ping
	
		if ( $_POST['hublocid']) {
			$hublocid = $_POST['hublocid'];
			$arrhublocurl = q("SELECT hubloc_url FROM hubloc WHERE hubloc_id = %d ",
				intval($hublocid)
			);
			$hublocurl = $arrhublocurl[0]['hubloc_url'] . '/post';
	
			//perform ping
			$m = zot_build_packet(\App::get_channel(),'ping');
			$r = zot_zot($hublocurl,$m);
			//handle results and set the hubloc flags in db to make results visible
			$r2 = $r['body'];
			$r3 = $r2['success'];
			if ( $r3['success'] == True ){
				//set HUBLOC_OFFLINE to 0
				logger(' success = true ',LOGGER_DEBUG);
			} else {
				//set HUBLOC_OFFLINE to 1 
				logger(' success = false ', LOGGER_DEBUG);
			}
	
			//unfotunatly zping wont work, I guess return format is not correct
			//require_once('mod/zping.php');
			//$r = zping_content($hublocurl);
			//logger('zping answer: ' . $r, LOGGER_DEBUG);
	
			//in case of repair store new pub key for tested hubloc (all channel with this hubloc) in db
			//after repair set hubloc flags to 0
		}
	
		goaway(z_root() . '/admin/hubloc' );
	}
	
	function trim_array_elems($arr) {
		$narr = array();
	
		if($arr && is_array($arr)) {
			for($x = 0; $x < count($arr); $x ++) {
				$y = trim($arr[$x]);
				if($y)
					$narr[] = $y;
			}
		}
		return $narr;
	}
	
	function admin_page_security_post(&$a){
		check_form_security_token_redirectOnErr('/admin/security', 'admin_security');
	
	logger('post: ' . print_r($_POST,true));
	
		$block_public         = ((x($_POST,'block_public'))		? True	: False);
		set_config('system','block_public',$block_public);
	
		$ws = $this->trim_array_elems(explode("\n",$_POST['whitelisted_sites']));
		set_config('system','whitelisted_sites',$ws);
	
		$bs = $this->trim_array_elems(explode("\n",$_POST['blacklisted_sites']));
		set_config('system','blacklisted_sites',$bs);
	
		$wc = $this->trim_array_elems(explode("\n",$_POST['whitelisted_channels']));
		set_config('system','whitelisted_channels',$wc);
	
		$bc = $this->trim_array_elems(explode("\n",$_POST['blacklisted_channels']));
		set_config('system','blacklisted_channels',$bc);
	
		$embed_sslonly         = ((x($_POST,'embed_sslonly'))		? True	: False);
		set_config('system','embed_sslonly',$embed_sslonly);
	
		$we = $this->trim_array_elems(explode("\n",$_POST['embed_allow']));
		set_config('system','embed_allow',$we);
	
		$be = $this->trim_array_elems(explode("\n",$_POST['embed_deny']));
		set_config('system','embed_deny',$be);
	
		$ts = ((x($_POST,'transport_security')) ? True : False);
		set_config('system','transport_security_header',$ts);

		$cs = ((x($_POST,'content_security')) ? True : False);
		set_config('system','content_security_policy',$cs);

		goaway(z_root() . '/admin/security');
	}
	
	
	
	
	function admin_page_features_post(&$a) {
	
		check_form_security_token_redirectOnErr('/admin/features', 'admin_manage_features');
	
		logger('postvars: ' . print_r($_POST,true));
	
		$arr = array();
		$features = get_features(false);
	
		foreach($features as $fname => $fdata) {
			foreach(array_slice($fdata,1) as $f) {
				$feature = $f[0];
	
				if(array_key_exists('feature_' . $feature,$_POST))
					$val = intval($_POST['feature_' . $feature]);
				else
					$val = 0;
				set_config('feature',$feature,$val);
	
				if(array_key_exists('featurelock_' . $feature,$_POST))
					set_config('feature_lock',$feature,$val);
				else
					del_config('feature_lock',$feature);
			}
		}
	
		goaway(z_root() . '/admin/features' );
	
	}
	
	function admin_page_features(&$a) {
		
		if((argc() > 1) && (argv(1) === 'features')) {
			$arr = array();
			$features = get_features(false);
	
			foreach($features as $fname => $fdata) {
				$arr[$fname] = array();
				$arr[$fname][0] = $fdata[0];
				foreach(array_slice($fdata,1) as $f) {
	
					$set = get_config('feature',$f[0]);
					if($set === false)
						$set = $f[3];
					$arr[$fname][1][] = array(
						array('feature_' .$f[0],$f[1],$set,$f[2],array(t('Off'),t('On'))),
						array('featurelock_' .$f[0],sprintf( t('Lock feature %s'),$f[1]),(($f[4] !== false) ? 1 : 0),'',array(t('Off'),t('On')))
					);
				}
			}
			
			$tpl = get_markup_template("admin_settings_features.tpl");
			$o .= replace_macros($tpl, array(
				'$form_security_token' => get_form_security_token("admin_manage_features"),
				'$title'	=> t('Manage Additional Features'),
				'$features' => $arr,
				'$submit'   => t('Submit'),
			));
	
			return $o;
		}
	}
	
	
	
	
	
	function admin_page_hubloc(&$a) {
		$hubloc = q("SELECT hubloc_id, hubloc_addr, hubloc_host, hubloc_status  FROM hubloc");
	
		if(! $hubloc){
			notice( t('No server found') . EOL);
			goaway(z_root() . '/admin/hubloc');
		}
	
		$t = get_markup_template('admin_hubloc.tpl');
		return replace_macros($t, array(
			'$hubloc' => $hubloc,
			'$th_hubloc' => array(t('ID'), t('for channel'), t('on server'), t('Status')),
			'$title' => t('Administration'),
			'$page' => t('Server'),
			'$queues' => $queues,
			//'$accounts' => $accounts, /*$accounts is empty here*/
			'$pending' => array( t('Pending registrations'), $pending),
			'$plugins' => array( t('Active plugins'), \App::$plugins ),
			'$form_security_token' => get_form_security_token('admin_hubloc')
		));
	}
	
	function admin_page_security(&$a) {
	
		$whitesites = get_config('system','whitelisted_sites');
		$whitesites_str = ((is_array($whitesites)) ? implode($whitesites,"\n") : '');
	
		$blacksites = get_config('system','blacklisted_sites');
		$blacksites_str = ((is_array($blacksites)) ? implode($blacksites,"\n") : '');
	
	
		$whitechannels = get_config('system','whitelisted_channels');
		$whitechannels_str = ((is_array($whitechannels)) ? implode($whitechannels,"\n") : '');
	
		$blackchannels = get_config('system','blacklisted_channels');
		$blackchannels_str = ((is_array($blackchannels)) ? implode($blackchannels,"\n") : '');
	
	
		$whiteembeds = get_config('system','embed_allow');
		$whiteembeds_str = ((is_array($whiteembeds)) ? implode($whiteembeds,"\n") : '');
	
		$blackembeds = get_config('system','embed_deny');
		$blackembeds_str = ((is_array($blackembeds)) ? implode($blackembeds,"\n") : '');
	
		$embed_coop = intval(get_config('system','embed_coop'));
	
		if((! $whiteembeds) && (! $blackembeds)) {
			$embedhelp1 = t("By default, unfiltered HTML is allowed in embedded media. This is inherently insecure.");
		}

		$embedhelp2 = t("The recommended setting is to only allow unfiltered HTML from the following sites:"); 
		$embedhelp3 = t("https://youtube.com/<br />https://www.youtube.com/<br />https://youtu.be/<br />https://vimeo.com/<br />https://soundcloud.com/<br />");
		$embedhelp4 = t("All other embedded content will be filtered, <strong>unless</strong> embedded content from that site is explicitly blocked.");
	
		$t = get_markup_template('admin_security.tpl');
		return replace_macros($t, array(
			'$title' => t('Administration'),
			'$page' => t('Security'),
			'$form_security_token' => get_form_security_token('admin_security'),
	        '$block_public'     => array('block_public', t("Block public"), get_config('system','block_public'), t("Check to block public access to all otherwise public personal pages on this site unless you are currently authenticated.")),
			'$transport_security' => array('transport_security', t('Set "Transport Security" HTTP header'),intval(get_config('system','transport_security_header')),''),
			'$content_security' => array('content_security', t('Set "Content Security Policy" HTTP header'),intval(get_config('system','content_security_policy')),''),
			'$whitelisted_sites' => array('whitelisted_sites', t('Allow communications only from these sites'), $whitesites_str, t('One site per line. Leave empty to allow communication from anywhere by default')),
			'$blacklisted_sites' => array('blacklisted_sites', t('Block communications from these sites'), $blacksites_str, ''),
			'$whitelisted_channels' => array('whitelisted_channels', t('Allow communications only from these channels'), $whitechannels_str, t('One channel (hash) per line. Leave empty to allow from any channel by default')),
			'$blacklisted_channels' => array('blacklisted_channels', t('Block communications from these channels'), $blackchannels_str, ''),
			'$embed_sslonly' => array('embed_sslonly',t('Only allow embeds from secure (SSL) websites and links.'), intval(get_config('system','embed_sslonly')),''),
			'$embed_allow' => array('embed_allow', t('Allow unfiltered embedded HTML content only from these domains'), $whiteembeds_str, t('One site per line. By default embedded content is filtered.')),
			'$embed_deny' => array('embed_deny', t('Block embedded HTML from these domains'), $blackembeds_str, ''),
	
//	        '$embed_coop'     => array('embed_coop', t('Cooperative embed security'), $embed_coop, t('Enable to share embed security with other compatible sites/hubs')),

			'$submit' => t('Submit')
		));
	}
	
	
	
	
	function admin_page_dbsync(&$a) {
		$o = '';
	
		if(argc() > 3 && intval(argv(3)) && argv(2) === 'mark') {
			set_config('database', 'update_r' . intval(argv(3)), 'success');
			if(intval(get_config('system','db_version')) <= intval(argv(3)))
				set_config('system','db_version',intval(argv(3)) + 1);
			info( t('Update has been marked successful') . EOL);
			goaway(z_root() . '/admin/dbsync');
		}
	
		if(argc() > 2 && intval(argv(2))) {
			require_once('install/update.php');
			$func = 'update_r' . intval(argv(2));
			if(function_exists($func)) {
				$retval = $func();
				if($retval === UPDATE_FAILED) {
					$o .= sprintf( t('Executing %s failed. Check system logs.'), $func); 
				}
				elseif($retval === UPDATE_SUCCESS) {
					$o .= sprintf( t('Update %s was successfully applied.'), $func);
					set_config('database',$func, 'success');
				}
				else
					$o .= sprintf( t('Update %s did not return a status. Unknown if it succeeded.'), $func);
			}
			else
				$o .= sprintf( t('Update function %s could not be found.'), $func);
	
			return $o;
		}
	
		$failed = array();
		$r = q("select * from config where `cat` = 'database' ");
		if(count($r)) {
			foreach($r as $rr) {
				$upd = intval(substr($rr['k'],8));
				if($rr['v'] === 'success')
					continue;
				$failed[] = $upd;
			}
		}
		if(! count($failed))
			return '<div class="generic-content-wrapper-styled"><h3>' . t('No failed updates.') . '</h3></div>';
	
		$o = replace_macros(get_markup_template('failed_updates.tpl'),array(
			'$base' => z_root(),
			'$banner' => t('Failed Updates'),
			'$desc' => '',
			'$mark' => t('Mark success (if update was manually applied)'),
			'$apply' => t('Attempt to execute this update step automatically'),
			'$failed' => $failed
		));
	
		return $o;
	}
	
	function admin_page_queue($a) {
		$o = '';
	
		$expert = ((array_key_exists('expert',$_REQUEST)) ? intval($_REQUEST['expert']) : 0);
	
		if($_REQUEST['drophub']) {
			require_once('hubloc.php');
			hubloc_mark_as_down($_REQUEST['drophub']);
			remove_queue_by_posturl($_REQUEST['drophub']);
		}
	
		if($_REQUEST['emptyhub']) {
			remove_queue_by_posturl($_REQUEST['emptyhub']);
		}
	
		$r = q("select count(outq_posturl) as total, max(outq_priority) as priority, outq_posturl from outq 
			where outq_delivered = 0 group by outq_posturl order by total desc");
	
		for($x = 0; $x < count($r); $x ++) {
			$r[$x]['eurl'] = urlencode($r[$x]['outq_posturl']);
			$r[$x]['connected'] = datetime_convert('UTC',date_default_timezone_get(),$r[$x]['connected'],'Y-m-d');
		}
	
		$o = replace_macros(get_markup_template('admin_queue.tpl'), array(
			'$banner' => t('Queue Statistics'),
			'$numentries' => t('Total Entries'),
			'$priority' => t('Priority'),
			'$desturl' => t('Destination URL'),
			'$nukehub' => t('Mark hub permanently offline'),
			'$empty' => t('Empty queue for this hub'),
			'$lastconn' => t('Last known contact'),
			'$hasentries' => ((count($r)) ? true : false),
			'$entries' => $r,
			'$expert' => $expert
		));
	
		return $o;
	}
	
	/**
	 * @brief Handle POST actions on accounts admin page.
	 *
	 * This function is called when on the admin user/account page the form was
	 * submitted to handle multiple operations at once. If one of the icons next
	 * to an entry are pressed the function admin_page_accounts() will handle this.
	 *
	 * @param App $a
	 */
	function admin_page_accounts_post($a) {
		$pending = ( x($_POST, 'pending') ? $_POST['pending'] : array() );
		$users   = ( x($_POST, 'user')    ? $_POST['user']    : array() );
		$blocked = ( x($_POST, 'blocked') ? $_POST['blocked'] : array() );
	
		check_form_security_token_redirectOnErr('/admin/accounts', 'admin_accounts');
	
		// change to switch structure?
		// account block/unblock button was submitted
		if (x($_POST, 'page_users_block')) {
			for ($i = 0; $i < count($users); $i++) {
				// if account is blocked remove blocked bit-flag, otherwise add blocked bit-flag
				$op = ($blocked[$i]) ? '& ~' : '| ';
				q("UPDATE account SET account_flags = (account_flags $op%d) WHERE account_id = %d",
					intval(ACCOUNT_BLOCKED),
					intval($users[$i])
				);
			}
			notice( sprintf( tt("%s account blocked/unblocked", "%s account blocked/unblocked", count($users)), count($users)) );
		}
		// account delete button was submitted
		if (x($_POST, 'page_accounts_delete')) {
			foreach ($users as $uid){
				account_remove($uid, true, false);
			}
			notice( sprintf( tt("%s account deleted", "%s accounts deleted", count($users)), count($users)) );
		}
		// registration approved button was submitted
		if (x($_POST, 'page_users_approve')) {
			foreach ($pending as $hash) {
				account_allow($hash);
			}
		}
		// registration deny button was submitted
		if (x($_POST, 'page_users_deny')) {
			foreach ($pending as $hash) {
				account_deny($hash);
			}
		}
	
		goaway(z_root() . '/admin/accounts' );
	}
	
	/**
	 * @brief Generate accounts admin page and handle single item operations.
	 *
	 * This function generates the accounts/account admin page and handles the actions
	 * if an icon next to an entry was clicked. If several items were selected and
	 * the form was submitted it is handled by the function admin_page_accounts_post().
	 *
	 * @param App &$a
	 * @return string
	 */
	function admin_page_accounts(&$a){
		if (argc() > 2) {
			$uid = argv(3);
			$account = q("SELECT * FROM account WHERE account_id = %d",
				intval($uid)
			);
	
			if (! $account) {
				notice( t('Account not found') . EOL);
				goaway(z_root() . '/admin/accounts' );
			}
	
			check_form_security_token_redirectOnErr('/admin/accounts', 'admin_accounts', 't');
	
			switch (argv(2)){
				case 'delete':
					// delete user
					account_remove($uid,true,false);
	
					notice( sprintf(t("Account '%s' deleted"), $account[0]['account_email']) . EOL);
					break;
				case 'block':
					q("UPDATE account SET account_flags = ( account_flags | %d ) WHERE account_id = %d",
						intval(ACCOUNT_BLOCKED),
						intval($uid)
					);
	
					notice( sprintf( t("Account '%s' blocked") , $account[0]['account_email']) . EOL);
					break;
				case 'unblock':
					q("UPDATE account SET account_flags = ( account_flags & ~%d ) WHERE account_id = %d",
							intval(ACCOUNT_BLOCKED),
							intval($uid)
					);
	
					notice( sprintf( t("Account '%s' unblocked"), $account[0]['account_email']) . EOL);
					break;
			}
	
			goaway(z_root() . '/admin/accounts' );
		}
	
		/* get pending */
		$pending = q("SELECT account.*, register.hash from account left join register on account_id = register.uid where (account_flags & %d )>0 ",
			intval(ACCOUNT_PENDING)
		);
	
		/* get accounts */
	
		$total = q("SELECT count(*) as total FROM account");
		if (count($total)) {
			\App::set_pager_total($total[0]['total']);
			\App::set_pager_itemspage(100);
		}
	
		$serviceclass = (($_REQUEST['class']) ? " and account_service_class = '" . dbesc($_REQUEST['class']) . "' " : '');

		$key = (($_REQUEST['key']) ? dbesc($_REQUEST['key']) : 'account_id');
		$dir = 'asc';
		if(array_key_exists('dir',$_REQUEST))
			$dir = ((intval($_REQUEST['dir'])) ? 'asc' : 'desc');

		$base = z_root() . '/admin/accounts?f=';
		$odir = (($dir === 'asc') ? '0' : '1');
	
		$users = q("SELECT `account_id` , `account_email`, `account_lastlog`, `account_created`, `account_expires`, " . 			"`account_service_class`, ( account_flags & %d ) > 0 as `blocked`, " .
				"(SELECT %s FROM channel as ch " .
				"WHERE ch.channel_account_id = ac.account_id and ch.channel_removed = 0 ) as `channels` " .
			"FROM account as ac where true $serviceclass order by $key $dir limit %d offset %d ",
			intval(ACCOUNT_BLOCKED),
			db_concat('ch.channel_address', ' '),
			intval(\App::$pager['itemspage']),
			intval(\App::$pager['start'])
		);
	
	//	function _setup_users($e){
	//		$accounts = Array(
	//			t('Normal Account'), 
	//			t('Soapbox Account'),
	//			t('Community/Celebrity Account'),
	//			t('Automatic Friend Account')
	//		);
	
	//		$e['page_flags'] = $accounts[$e['page-flags']];
	//		$e['register_date'] = relative_date($e['register_date']);
	//		$e['login_date'] = relative_date($e['login_date']);
	//		$e['lastitem_date'] = relative_date($e['lastitem_date']);
	//		return $e;
	//	}
	//	$users = array_map("_setup_users", $users);
	
		$t = get_markup_template('admin_accounts.tpl');
		$o = replace_macros($t, array(
			// strings //
			'$title' => t('Administration'),
			'$page' => t('Accounts'),
			'$submit' => t('Submit'),
			'$select_all' => t('select all'),
			'$h_pending' => t('Registrations waiting for confirm'),
			'$th_pending' => array( t('Request date'), t('Email') ),
			'$no_pending' =>  t('No registrations.'),
			'$approve' => t('Approve'),
			'$deny' => t('Deny'),
			'$delete' => t('Delete'),
			'$block' => t('Block'),
			'$unblock' => t('Unblock'),
			'$odir' => $odir,
			'$base' => $base,
			'$h_users' => t('Accounts'),
			'$th_users' => array( 
				[ t('ID'), 'account_id' ],
				[ t('Email'), 'account_email' ],
				[ t('All Channels'), 'channels' ],
				[ t('Register date'), 'account_created' ],
				[ t('Last login'), 'account_lastlog' ],
				[ t('Expires'), 'account_expires' ],
				[ t('Service Class'), 'account_service_class'] ),
	
			'$confirm_delete_multi' => t('Selected accounts will be deleted!\n\nEverything these accounts had posted on this site will be permanently deleted!\n\nAre you sure?'),
			'$confirm_delete' => t('The account {0} will be deleted!\n\nEverything this account has posted on this site will be permanently deleted!\n\nAre you sure?'),
	
			'$form_security_token' => get_form_security_token("admin_accounts"),
	
			// values //
			'$baseurl' => z_root(),
	
			'$pending' => $pending,
			'$users' => $users,
		));
		$o .= paginate($a);
	
		return $o;
	}
	
	
	/**
	 * @brief Channels admin page.
	 *
	 * @param App &$a
	 */
	function admin_page_channels_post(&$a) {
		$channels = ( x($_POST, 'channel') ? $_POST['channel'] : Array() );
	
		check_form_security_token_redirectOnErr('/admin/channels', 'admin_channels');
		
		$xor = db_getfunc('^');
	
		if (x($_POST,'page_channels_block')){
			foreach($channels as $uid){
				q("UPDATE channel SET channel_pageflags = ( channel_pageflags $xor %d ) where channel_id = %d",
					intval(PAGE_CENSORED),
					intval( $uid )
				);
				\Zotlabs\Daemon\Master::Summon(array('Directory',$uid,'nopush'));
			}
			notice( sprintf( tt("%s channel censored/uncensored", "%s channels censored/uncensored", count($channels)), count($channels)) );
		}
		if (x($_POST,'page_channels_code')){
			foreach($channels as $uid){
				q("UPDATE channel SET channel_pageflags = ( channel_pageflags $xor %d ) where channel_id = %d",
					intval(PAGE_ALLOWCODE),
					intval( $uid )
				);
			}
			notice( sprintf( tt("%s channel code allowed/disallowed", "%s channels code allowed/disallowed", count($channels)), count($channels)) );
		}
		if (x($_POST,'page_channels_delete')){
			foreach($channels as $uid){
				channel_remove($uid,true);
			}
			notice( sprintf( tt("%s channel deleted", "%s channels deleted", count($channels)), count($channels)) );
		}
	
		goaway(z_root() . '/admin/channels' );
	}
	
	/**
	 * @brief
	 *
	 * @param App &$a
	 * @return string
	 */
	function admin_page_channels(&$a){
		if (argc() > 2) {
			$uid = argv(3);
			$channel = q("SELECT * FROM channel WHERE channel_id = %d",
				intval($uid)
			);
	
			if (! $channel) {
				notice( t('Channel not found') . EOL);
				goaway(z_root() . '/admin/channels' );
			}
	
			switch(argv(2)) {
				case "delete":{
					check_form_security_token_redirectOnErr('/admin/channels', 'admin_channels', 't');
					// delete channel
					channel_remove($uid,true);
					
					notice( sprintf(t("Channel '%s' deleted"), $channel[0]['channel_name']) . EOL);
				}; break;
	
				case "block":{
					check_form_security_token_redirectOnErr('/admin/channels', 'admin_channels', 't');
					$pflags = $channel[0]['channel_pageflags'] ^ PAGE_CENSORED; 
					q("UPDATE channel SET channel_pageflags = %d where channel_id = %d",
						intval($pflags),
						intval( $uid )
					);
					\Zotlabs\Daemon\Master::Summon(array('Directory',$uid,'nopush'));
	
					notice( sprintf( (($pflags & PAGE_CENSORED) ? t("Channel '%s' censored"): t("Channel '%s' uncensored")) , $channel[0]['channel_name'] . ' (' . $channel[0]['channel_address'] . ')' ) . EOL);
				}; break;
	
				case "code":{
					check_form_security_token_redirectOnErr('/admin/channels', 'admin_channels', 't');
					$pflags = $channel[0]['channel_pageflags'] ^ PAGE_ALLOWCODE; 
					q("UPDATE channel SET channel_pageflags = %d where channel_id = %d",
						intval($pflags),
						intval( $uid )
					);
	
					notice( sprintf( (($pflags & PAGE_ALLOWCODE) ? t("Channel '%s' code allowed"): t("Channel '%s' code disallowed")) , $channel[0]['channel_name'] . ' (' . $channel[0]['channel_address'] . ')' ) . EOL);
				}; break;
	
				default: 
					break;
			}
			goaway(z_root() . '/admin/channels' );
		}


		$key = (($_REQUEST['key']) ? dbesc($_REQUEST['key']) : 'channel_id');
		$dir = 'asc';
		if(array_key_exists('dir',$_REQUEST))
			$dir = ((intval($_REQUEST['dir'])) ? 'asc' : 'desc');

		$base = z_root() . '/admin/channels?f=';
		$odir = (($dir === 'asc') ? '0' : '1');


	
		/* get channels */
	
		$total = q("SELECT count(*) as total FROM channel where channel_removed = 0 and channel_system = 0");
		if($total) {
			\App::set_pager_total($total[0]['total']);
			\App::set_pager_itemspage(100);
		}

		$channels = q("SELECT * from channel where channel_removed = 0 and channel_system = 0 order by $key $dir limit %d offset %d ",
			intval(\App::$pager['itemspage']),
			intval(\App::$pager['start'])
		);

		if($channels) {
			for($x = 0; $x < count($channels); $x ++) {
				if($channels[$x]['channel_pageflags'] & PAGE_CENSORED)
					$channels[$x]['blocked'] = true;
				else
					$channels[$x]['blocked'] = false;
	
				if($channels[$x]['channel_pageflags'] & PAGE_ALLOWCODE)
					$channels[$x]['allowcode'] = true;
				else
					$channels[$x]['allowcode'] = false;
			}
		}
	
		$t = get_markup_template("admin_channels.tpl");
		$o = replace_macros($t, array(
			// strings //
			'$title' => t('Administration'),
			'$page' => t('Channels'),
			'$submit' => t('Submit'),
			'$select_all' => t('select all'),
			'$delete' => t('Delete'),
			'$block' => t('Censor'),
			'$unblock' => t('Uncensor'),
			'$code' => t('Allow Code'),
			'$uncode' => t('Disallow Code'),
			'$h_channels' => t('Channel'),
			'$base' => $base,
			'$odir' => $odir,
			'$th_channels' => array( 
					[ t('UID'), 'channel_id' ],
					[ t('Name'), 'channel_name' ],
					[ t('Address'), 'channel_address' ]),
	
			'$confirm_delete_multi' => t('Selected channels will be deleted!\n\nEverything that was posted in these channels on this site will be permanently deleted!\n\nAre you sure?'),
			'$confirm_delete' => t('The channel {0} will be deleted!\n\nEverything that was posted in this channel on this site will be permanently deleted!\n\nAre you sure?'),
	
			'$form_security_token' => get_form_security_token("admin_channels"),
	
			// values //
			'$baseurl' => z_root(),
			'$channels' => $channels,
		));
		$o .= paginate($a);
	
		return $o;
	}
	
	
	/**
	 * Plugins admin page
	 *
	 * @param App $a
	 * @return string
	 */
	function admin_page_plugins(&$a){
	
		/*
		 * Single plugin
		 */
		if (\App::$argc == 3){
			$plugin = \App::$argv[2];
			if (!is_file("addon/$plugin/$plugin.php")){
				notice( t("Item not found.") );
				return '';
			}
	
			$enabled = in_array($plugin,\App::$plugins);
			$info = get_plugin_info($plugin);
			$x = check_plugin_versions($info);
	
			// disable plugins which are installed but incompatible versions
	
			if($enabled && ! $x) {
				$enabled = false;
				$idz = array_search($plugin, \App::$plugins);
				if ($idz !== false) {
					unset(\App::$plugins[$idz]);
					uninstall_plugin($plugin);
					set_config("system","addon", implode(", ",\App::$plugins));
				}
			}
			$info['disabled'] = 1-intval($x);
	
			if (x($_GET,"a") && $_GET['a']=="t"){
				check_form_security_token_redirectOnErr('/admin/plugins', 'admin_plugins', 't');
	
				// Toggle plugin status
				$idx = array_search($plugin, \App::$plugins);
				if ($idx !== false){
					unset(\App::$plugins[$idx]);
					uninstall_plugin($plugin);
					info( sprintf( t("Plugin %s disabled."), $plugin ) );
				} else {
					\App::$plugins[] = $plugin;
					install_plugin($plugin);
					info( sprintf( t("Plugin %s enabled."), $plugin ) );
				}
				set_config("system","addon", implode(", ",\App::$plugins));
				goaway(z_root() . '/admin/plugins' );
			}
			// display plugin details
			require_once('library/markdown.php');
	
			if (in_array($plugin, \App::$plugins)){
				$status = 'on';
				$action = t('Disable');
			} else {
				$status = 'off';
				$action =  t('Enable');
			}
	
			$readme = null;
			if (is_file("addon/$plugin/README.md")){
				$readme = file_get_contents("addon/$plugin/README.md");
				$readme = Markdown($readme);
			} else if (is_file("addon/$plugin/README")){
				$readme = "<pre>". file_get_contents("addon/$plugin/README") ."</pre>";
			}
	
			$admin_form = '';
	
			$r = q("select * from addon where plugin_admin = 1 and aname = '%s' limit 1",
				dbesc($plugin)
			);
	
			if($r) {
				@require_once("addon/$plugin/$plugin.php");
				if(function_exists($plugin.'_plugin_admin')) {
					$func = $plugin.'_plugin_admin';
					$func($a, $admin_form);
				}
			}
	
	
			$t = get_markup_template('admin_plugins_details.tpl');
			return replace_macros($t, array(
				'$title' => t('Administration'),
				'$page' => t('Plugins'),
				'$toggle' => t('Toggle'),
				'$settings' => t('Settings'),
				'$baseurl' => z_root(),
	
				'$plugin' => $plugin,
				'$status' => $status,
				'$action' => $action,
				'$info' => $info,
				'$str_author' => t('Author: '),
				'$str_maintainer' => t('Maintainer: '),
				'$str_minversion' => t('Minimum project version: '),
				'$str_maxversion' => t('Maximum project version: '),
				'$str_minphpversion' => t('Minimum PHP version: '),
				'$str_requires' => t('Requires: '),
				'$disabled' => t('Disabled - version incompatibility'),
	
				'$admin_form' => $admin_form,
				'$function' => 'plugins',
				'$screenshot' => '',
				'$readme' => $readme,
	
				'$form_security_token' => get_form_security_token('admin_plugins'),
			));
		}
	
	
		/*
		 * List plugins
		 */
		$plugins = array();
		$files = glob('addon/*/');
		if($files) {
			foreach($files as $file) {
				if (is_dir($file)){
					list($tmp, $id) = array_map('trim', explode('/', $file));
					$info = get_plugin_info($id);
					$enabled = in_array($id,\App::$plugins);
					$x = check_plugin_versions($info);
	
					// disable plugins which are installed but incompatible versions
	
					if($enabled && ! $x) {
						$enabled = false;
						$idz = array_search($id, \App::$plugins);
						if ($idz !== false) {
							unset(\App::$plugins[$idz]);
							uninstall_plugin($id);
							set_config("system","addon", implode(", ",\App::$plugins));
						}
					}
					$info['disabled'] = 1-intval($x);
	
					$plugins[] = array( $id, (($enabled)?"on":"off") , $info);
				}
			}
		}
	
		usort($plugins,'self::plugin_sort');

		
		$admin_plugins_add_repo_form= replace_macros(
			get_markup_template('admin_plugins_addrepo.tpl'), array(
				'$post' => 'admin/plugins/addrepo',
				'$desc' => t('Enter the public git repository URL of the plugin repo.'),
				'$repoURL' => array('repoURL', t('Plugin repo git URL'), '', ''),
				'$repoName' => array('repoName', t('Custom repo name'), '', '', t('(optional)')),
				'$submit' => t('Download Plugin Repo')
			)
		);
		$newRepoModalID = random_string(3);
		$newRepoModal = replace_macros(
			get_markup_template('generic_modal.tpl'), array(
				'$id' => $newRepoModalID,
				'$title' => t('Install new repo'),
				'$ok' => t('Install'),
				'$cancel' => t('Cancel')
			)
		);
			
		$reponames = $this->listAddonRepos();
		$addonrepos = [];
		foreach($reponames as $repo) {
			$addonrepos[] = array('name' => $repo, 'description' => '');
			// TODO: Parse repo info to provide more information about repos
		}
		
		$t = get_markup_template('admin_plugins.tpl');
		return replace_macros($t, array(
			'$title' => t('Administration'),
			'$page' => t('Plugins'),
			'$submit' => t('Submit'),
			'$baseurl' => z_root(),
			'$function' => 'plugins',
			'$plugins' => $plugins,
			'$disabled' => t('Disabled - version incompatibility'),
			'$form_security_token' => get_form_security_token('admin_plugins'),
			'$managerepos' => t('Manage Repos'),
			'$installedtitle' => t('Installed Plugin Repositories'),
			'$addnewrepotitle' =>	t('Install a New Plugin Repository'),
			'$expandform' => false,
			'$form' => $admin_plugins_add_repo_form,
			'$newRepoModal' => $newRepoModal,
			'$newRepoModalID' => $newRepoModalID,
			'$addonrepos' => $addonrepos,
			'$repoUpdateButton' => t('Update'),
			'$repoBranchButton' => t('Switch branch'),
			'$repoRemoveButton' => t('Remove')
		));
	}

	function listAddonRepos() {
		$addonrepos = [];
		$addonDir = __DIR__ . '/../../extend/addon/';
		if(is_dir($addonDir)) {
			if ($handle = opendir($addonDir)) {
				while (false !== ($entry = readdir($handle))) {
					if ($entry != "." && $entry != "..") {
						$addonrepos[] = $entry;
					}
				}
				closedir($handle);
			}
		}
		return $addonrepos;
	}

	static public function plugin_sort($a,$b) {
		return(strcmp(strtolower($a[2]['name']),strtolower($b[2]['name'])));
	}


	/**
	 * @param array $themes
	 * @param string $th
	 * @param int $result
	 */
	function toggle_theme(&$themes, $th, &$result) {
		for($x = 0; $x < count($themes); $x ++) {
			if($themes[$x]['name'] === $th) {
				if($themes[$x]['allowed']) {
					$themes[$x]['allowed'] = 0;
					$result = 0;
				}
				else {
					$themes[$x]['allowed'] = 1;
					$result = 1;
				}
			}
		}
	}
	
	/**
	 * @param array $themes
	 * @param string $th
	 * @return int
	 */
	function theme_status($themes, $th) {
		for($x = 0; $x < count($themes); $x ++) {
			if($themes[$x]['name'] === $th) {
				if($themes[$x]['allowed']) {
					return 1;
				}
				else {
					return 0;
				}
			}
		}
		return 0;
	}
	
	
	/**
	 * @param array $themes
	 * @return string
	 */
	function rebuild_theme_table($themes) {
		$o = '';
		if(count($themes)) {
			foreach($themes as $th) {
				if($th['allowed']) {
					if(strlen($o))
						$o .= ',';
					$o .= $th['name'];
				}
			}
		}
		return $o;
	}
	
	
	/**
	 * @brief Themes admin page.
	 *
	 * @param App &$a
	 * @return string
	 */
	function admin_page_themes(&$a){
	
		$allowed_themes_str = get_config('system', 'allowed_themes');
		$allowed_themes_raw = explode(',', $allowed_themes_str);
		$allowed_themes = array();
		if(count($allowed_themes_raw))
			foreach($allowed_themes_raw as $x)
				if(strlen(trim($x)))
					$allowed_themes[] = trim($x);
	
		$themes = array();
		$files = glob('view/theme/*');
		if($files) {
			foreach($files as $file) {
				$f = basename($file);
				$is_experimental = intval(file_exists($file . '/.experimental'));
				$is_supported = 1-(intval(file_exists($file . '/.unsupported'))); // Is not used yet
				$is_allowed = intval(in_array($f,$allowed_themes));
				$themes[] = array('name' => $f, 'experimental' => $is_experimental, 'supported' => $is_supported, 'allowed' => $is_allowed);
			}
		}
	
		if(! count($themes)) {
			notice( t('No themes found.'));
			return '';
		}
	
		/*
		 * Single theme
		 */
	
		if (\App::$argc == 3){
			$theme = \App::$argv[2];
			if(! is_dir("view/theme/$theme")){
				notice( t("Item not found.") );
				return '';
			}
	
			if (x($_GET,"a") && $_GET['a']=="t"){
				check_form_security_token_redirectOnErr('/admin/themes', 'admin_themes', 't');
	
				// Toggle theme status
	
				$this->toggle_theme($themes, $theme, $result);
				$s = $this->rebuild_theme_table($themes);
				if($result)
					info( sprintf('Theme %s enabled.', $theme));
				else
					info( sprintf('Theme %s disabled.', $theme));
	
				set_config('system', 'allowed_themes', $s);
				goaway(z_root() . '/admin/themes' );
			}
	
			// display theme details
			require_once('library/markdown.php');
	
			if ($this->theme_status($themes,$theme)) {
				$status="on"; $action= t("Disable");
			} else {
				$status="off"; $action= t("Enable");
			}
	
			$readme=Null;
			if (is_file("view/theme/$theme/README.md")){
				$readme = file_get_contents("view/theme/$theme/README.md");
				$readme = Markdown($readme);
			} else if (is_file("view/theme/$theme/README")){
				$readme = "<pre>". file_get_contents("view/theme/$theme/README") ."</pre>";
			}
	
			$admin_form = '';
			if (is_file("view/theme/$theme/php/config.php")){
				require_once("view/theme/$theme/php/config.php");
				if(function_exists("theme_admin")){
					$admin_form = theme_admin($a);
				}
			}
	
			$screenshot = array( get_theme_screenshot($theme), t('Screenshot'));
			if(! stristr($screenshot[0],$theme))
				$screenshot = null;
	
			$t = get_markup_template('admin_plugins_details.tpl');
			return replace_macros($t, array(
				'$title' => t('Administration'),
				'$page' => t('Themes'),
				'$toggle' => t('Toggle'),
				'$settings' => t('Settings'),
				'$baseurl' => z_root(),
			
				'$plugin' => $theme,
				'$status' => $status,
				'$action' => $action,
				'$info' => get_theme_info($theme),
				'$function' => 'themes',
				'$admin_form' => $admin_form,
				'$str_author' => t('Author: '),
				'$str_maintainer' => t('Maintainer: '),
				'$screenshot' => $screenshot,
				'$readme' => $readme,
	
				'$form_security_token' => get_form_security_token('admin_themes'),
			));
		}
	
		/*
		 * List themes
		 */
	
		$xthemes = array();
		if($themes) {
			foreach($themes as $th) {
				$xthemes[] = array($th['name'],(($th['allowed']) ? "on" : "off"), get_theme_info($th['name']));
			}
		}
	
		$t = get_markup_template('admin_plugins.tpl');
		return replace_macros($t, array(
			'$title' => t('Administration'),
			'$page' => t('Themes'),
			'$submit' => t('Submit'),
			'$baseurl' => z_root(),
			'$function' => 'themes',
			'$plugins' => $xthemes,
			'$experimental' => t('[Experimental]'),
			'$unsupported' => t('[Unsupported]'),
			'$form_security_token' => get_form_security_token('admin_themes'),
		));
	}
	
	
	/**
	 * @brief POST handler for logs admin page.
	 *
	 * @param App &$a
	 */
	function admin_page_logs_post(&$a) {
		if (x($_POST, 'page_logs')) {
			check_form_security_token_redirectOnErr('/admin/logs', 'admin_logs');
	
			$logfile   = ((x($_POST,'logfile'))   ? notags(trim($_POST['logfile'])) : '');
			$debugging = ((x($_POST,'debugging')) ? true : false);
			$loglevel  = ((x($_POST,'loglevel'))  ? intval(trim($_POST['loglevel'])) : 0);
	
			set_config('system','logfile', $logfile);
			set_config('system','debugging',  $debugging);
			set_config('system','loglevel', $loglevel);
		}
	
		info( t('Log settings updated.') );
		goaway(z_root() . '/admin/logs' );
	}
	
	/**
	 * @brief Logs admin page.
	 *
	 * @param App $a
	 * @return string
	 */
	function admin_page_logs(&$a){
	
		$log_choices = Array(
			LOGGER_NORMAL => 'Normal',
			LOGGER_TRACE => 'Trace',
			LOGGER_DEBUG => 'Debug',
			LOGGER_DATA => 'Data',
			LOGGER_ALL => 'All'
		);
	
		$t = get_markup_template('admin_logs.tpl');
	
		$f = get_config('system', 'logfile');
	
		$data = '';
	
		if(!file_exists($f)) {
			$data = t("Error trying to open <strong>$f</strong> log file.\r\n<br/>Check to see if file $f exist and is 
	readable.");
		}
		else {
			$fp = fopen($f, 'r');
			if(!$fp) {
				$data = t("Couldn't open <strong>$f</strong> log file.\r\n<br/>Check to see if file $f is readable.");
			}
			else {
				$fstat = fstat($fp);
				$size = $fstat['size'];
				if($size != 0)
				{
					if($size > 5000000 || $size < 0)
						$size = 5000000;
					$seek = fseek($fp,0-$size,SEEK_END);
					if($seek === 0) {
						$data = escape_tags(fread($fp,$size));
						while(! feof($fp))
							$data .= escape_tags(fread($fp,4096));
					}
				}
				fclose($fp);
			}
		}
	
		return replace_macros($t, array(
			'$title' => t('Administration'),
			'$page' => t('Logs'),
			'$submit' => t('Submit'),
			'$clear' => t('Clear'),
			'$data' => $data,
			'$baseurl' => z_root(),
			'$logname' =>  get_config('system','logfile'),
	
			// name, label, value, help string, extra data...
			'$debugging' => array('debugging', t("Debugging"),get_config('system','debugging'), ""),
			'$logfile'   => array('logfile', t("Log file"), get_config('system','logfile'), t("Must be writable by web server. Relative to your top-level webserver directory.")),
			'$loglevel'  => array('loglevel', t("Log level"), get_config('system','loglevel'), "", $log_choices),
	
			'$form_security_token' => get_form_security_token('admin_logs'),
		));
	}
	
	function admin_page_plugins_post($action) {
		switch ($action) {
			case 'updaterepo':
				if (array_key_exists('repoName', $_REQUEST)) {
					$repoName = $_REQUEST['repoName'];
				} else {
					json_return_and_die(array('message' => 'No repo name provided.', 'success' => false));
				}
				$extendDir = __DIR__ . '/../../store/[data]/git/sys/extend';
				$addonDir = $extendDir . '/addon';
				if (!file_exists($extendDir)) {
					if (!mkdir($extendDir, 0770, true)) {
						logger('Error creating extend folder: ' . $extendDir);
						json_return_and_die(array('message' => 'Error creating extend folder: ' . $extendDir, 'success' => false));
					} else {
						if (!symlink(__DIR__ . '/../../extend/addon', $addonDir)) {
							logger('Error creating symlink to addon folder: ' . $addonDir);
							json_return_and_die(array('message' => 'Error creating symlink to addon folder: ' . $addonDir, 'success' => false));
						}
					}
				}
				$repoDir = __DIR__ . '/../../store/[data]/git/sys/extend/addon/' . $repoName;
				if (!is_dir($repoDir)) {
					logger('Repo directory does not exist: ' . $repoDir);
					json_return_and_die(array('message' => 'Invalid addon repo.', 'success' => false));
				}
				if (!is_writable($repoDir)) {
					logger('Repo directory not writable to web server: ' . $repoDir);
					json_return_and_die(array('message' => 'Repo directory not writable to web server.', 'success' => false));
				}
				$git = new GitRepo('sys', null, false, $repoName, $repoDir);
				try {
					if ($git->pull()) {
						$files = array_diff(scandir($repoDir), array('.', '..'));
						foreach ($files as $file) {
							if (is_dir($repoDir . '/' . $file) && $file !== '.git') {
								$source = '../extend/addon/' . $repoName . '/' . $file;
								$target = realpath(__DIR__ . '/../../addon/') . '/' . $file;
								unlink($target);
								if (!symlink($source, $target)) {
									logger('Error linking addons to /addon');
									json_return_and_die(array('message' => 'Error linking addons to /addon', 'success' => false));
								}
							}
						}
						json_return_and_die(array('message' => 'Repo updated.', 'success' => true));
					} else {
						json_return_and_die(array('message' => 'Error updating addon repo.', 'success' => false));
					}
				} catch (\PHPGit\Exception\GitException $e) {
					json_return_and_die(array('message' => 'Error updating addon repo.', 'success' => false));
				}
			case 'removerepo':
				if (array_key_exists('repoName', $_REQUEST)) {
					$repoName = $_REQUEST['repoName'];
				} else {
					json_return_and_die(array('message' => 'No repo name provided.', 'success' => false));
				}
				$extendDir = __DIR__ . '/../../store/[data]/git/sys/extend';
				$addonDir = $extendDir . '/addon';
				if (!file_exists($extendDir)) {
					if (!mkdir($extendDir, 0770, true)) {
						logger('Error creating extend folder: ' . $extendDir);
						json_return_and_die(array('message' => 'Error creating extend folder: ' . $extendDir, 'success' => false));
					} else {
						if (!symlink(__DIR__ . '/../../extend/addon', $addonDir)) {
							logger('Error creating symlink to addon folder: ' . $addonDir);
							json_return_and_die(array('message' => 'Error creating symlink to addon folder: ' . $addonDir, 'success' => false));
						}
					}
				}
				$repoDir = __DIR__ . '/../../store/[data]/git/sys/extend/addon/' . $repoName;
				if (!is_dir($repoDir)) {
					logger('Repo directory does not exist: ' . $repoDir);
					json_return_and_die(array('message' => 'Invalid addon repo.', 'success' => false));
				}
				if (!is_writable($repoDir)) {
					logger('Repo directory not writable to web server: ' . $repoDir);
					json_return_and_die(array('message' => 'Repo directory not writable to web server.', 'success' => false));
				}
				// TODO: remove directory and unlink /addon/files
				if (rrmdir($repoDir)) {
					json_return_and_die(array('message' => 'Repo deleted.', 'success' => true));
				} else {
					json_return_and_die(array('message' => 'Error deleting addon repo.', 'success' => false));
				}
			case 'installrepo':
				require_once('library/markdown.php');
				if (array_key_exists('repoURL', $_REQUEST)) {
					require __DIR__ . '/../../library/PHPGit.autoload.php';			 // Load PHPGit dependencies					
					$repoURL = $_REQUEST['repoURL'];
					$extendDir = __DIR__ . '/../../store/[data]/git/sys/extend';
					$addonDir = $extendDir . '/addon';
					if (!file_exists($extendDir)) {
						if (!mkdir($extendDir, 0770, true)) {
							logger('Error creating extend folder: ' . $extendDir);
							json_return_and_die(array('message' => 'Error creating extend folder: ' . $extendDir, 'success' => false));
						} else {
							if (!symlink(__DIR__ . '/../../extend/addon', $addonDir)) {
								logger('Error creating symlink to addon folder: ' . $addonDir);
								json_return_and_die(array('message' => 'Error creating symlink to addon folder: ' . $addonDir, 'success' => false));
							}
						}
					}
					if (!is_writable($extendDir)) {
						logger('Directory not writable to web server: ' . $extendDir);
						json_return_and_die(array('message' => 'Directory not writable to web server.', 'success' => false));
					}
					$repoName = null;
					if (array_key_exists('repoName', $_REQUEST) && $_REQUEST['repoName'] !== '') {
						$repoName = $_REQUEST['repoName'];
					} else {
						$repoName = GitRepo::getRepoNameFromURL($repoURL);
					}
					if (!$repoName) {
						logger('Invalid git repo');
						json_return_and_die(array('message' => 'Invalid git repo', 'success' => false));
					}
					$repoDir = $addonDir . '/' . $repoName;
					$tempRepoBaseDir = __DIR__ . '/../../store/[data]/git/sys/temp/';
					$tempAddonDir = $tempRepoBaseDir . $repoName;

					if (!is_writable($addonDir) || !is_writable($tempAddonDir)) {
						logger('Temp repo directory or /extend/addon not writable to web server: ' . $tempAddonDir);
						json_return_and_die(array('message' => 'Temp repo directory not writable to web server.', 'success' => false));
					}
					rename($tempAddonDir, $repoDir);

					if (!is_writable(realpath(__DIR__ . '/../../addon/'))) {
						logger('/addon directory not writable to web server: ' . $tempAddonDir);
						json_return_and_die(array('message' => '/addon directory not writable to web server.', 'success' => false));
					}
					$files = array_diff(scandir($repoDir), array('.', '..'));
					foreach ($files as $file) {
						if (is_dir($repoDir . '/' . $file) && $file !== '.git') {
							$source = '../extend/addon/' . $repoName . '/' . $file;
							$target = realpath(__DIR__ . '/../../addon/') . '/' . $file;
							unlink($target);
							if (!symlink($source, $target)) {
								logger('Error linking addons to /addon');
								json_return_and_die(array('message' => 'Error linking addons to /addon', 'success' => false));
							}
						}
					}
					$git = new GitRepo('sys', $repoURL, false, $repoName, $repoDir);
					$repo = $git->probeRepo();
					json_return_and_die(array('repo' => $repo, 'message' => '', 'success' => true));
				}
			case 'addrepo':
				require_once('library/markdown.php');
				if (array_key_exists('repoURL', $_REQUEST)) {
					require __DIR__ . '/../../library/PHPGit.autoload.php';			 // Load PHPGit dependencies					
					$repoURL = $_REQUEST['repoURL'];
					$extendDir = __DIR__ . '/../../store/[data]/git/sys/extend';
					$addonDir = $extendDir . '/addon';
					$tempAddonDir = __DIR__ . '/../../store/[data]/git/sys/temp';
					if (!file_exists($extendDir)) {
						if (!mkdir($extendDir, 0770, true)) {
							logger('Error creating extend folder: ' . $extendDir);
							json_return_and_die(array('message' => 'Error creating extend folder: ' . $extendDir, 'success' => false));
						} else {
							if (!symlink(__DIR__ . '/../../extend/addon', $addonDir)) {
								logger('Error creating symlink to addon folder: ' . $addonDir);
								json_return_and_die(array('message' => 'Error creating symlink to addon folder: ' . $addonDir, 'success' => false));
							}
						}
					}
					if (!is_dir($tempAddonDir)) {
						if (!mkdir($tempAddonDir, 0770, true)) {
							logger('Error creating temp plugin repo folder: ' . $tempAddonDir);
							json_return_and_die(array('message' => 'Error creating temp plugin repo folder: ' . $tempAddonDir, 'success' => false));
						}
					}
					$repoName = null;
					if (array_key_exists('repoName', $_REQUEST) && $_REQUEST['repoName'] !== '') {
						$repoName = $_REQUEST['repoName'];
					} else {
						$repoName = GitRepo::getRepoNameFromURL($repoURL);
					}
					if (!$repoName) {
						logger('Invalid git repo');
						json_return_and_die(array('message' => 'Invalid git repo: ' . $repoName, 'success' => false));
					}
					$repoDir = $tempAddonDir . '/' . $repoName;
					if (!is_writable($tempAddonDir)) {
						logger('Temporary directory for new addon repo is not writable to web server: ' . $tempAddonDir);
						json_return_and_die(array('message' => 'Temporary directory for new addon repo is not writable to web server.', 'success' => false));
					}
					// clone the repo if new automatically
					$git = new GitRepo('sys', $repoURL, true, $repoName, $repoDir);

					$remotes = $git->git->remote();
					$fetchURL = $remotes['origin']['fetch'];
					if ($fetchURL !== $git->url) {
						if (rrmdir($repoDir)) {
							$git = new GitRepo('sys', $repoURL, true, $repoName, $repoDir);
						} else {
							json_return_and_die(array('message' => 'Error deleting existing addon repo.', 'success' => false));
						}
					}
					$repo = $git->probeRepo();
					$repo['readme'] = $repo['manifest'] = null;
					foreach ($git->git->tree('master') as $object) {
						if ($object['type'] == 'blob' && (strtolower($object['file']) === 'readme.md' || strtolower($object['file']) === 'readme')) {
							$repo['readme'] = Markdown($git->git->cat->blob($object['hash']));
						} else if ($object['type'] == 'blob' && strtolower($object['file']) === 'manifest.json') {
							$repo['manifest'] = $git->git->cat->blob($object['hash']);
						}
					}
					json_return_and_die(array('repo' => $repo, 'message' => '', 'success' => true));
				} else {
					json_return_and_die(array('message' => 'No repo URL provided', 'success' => false));
				}
				break;
			default:
				break;
		}
	}

	function admin_page_profs_post(&$a) {
	
		if(array_key_exists('basic',$_REQUEST)) {
			$arr = explode(',',$_REQUEST['basic']);
			for($x = 0; $x < count($arr); $x ++) 
				if(trim($arr[$x]))
					$arr[$x] = trim($arr[$x]);
			set_config('system','profile_fields_basic',$arr);
	
			if(array_key_exists('advanced',$_REQUEST)) {
				$arr = explode(',',$_REQUEST['advanced']);
				for($x = 0; $x < count($arr); $x ++)
					if(trim($arr[$x]))
						$arr[$x] = trim($arr[$x]);
				set_config('system','profile_fields_advanced',$arr);
			}
			goaway(z_root() . '/admin/profs');
		}
	
	
		if(array_key_exists('field_name',$_REQUEST)) {
			if($_REQUEST['id']) {
				$r = q("update profdef set field_name = '%s', field_type = '%s', field_desc = '%s' field_help = '%s', field_inputs = '%s' where id = %d",
					dbesc($_REQUEST['field_name']),
					dbesc($_REQUEST['field_type']),
					dbesc($_REQUEST['field_desc']),
					dbesc($_REQUEST['field_help']),
					dbesc($_REQUEST['field_inputs']),
					intval($_REQUEST['id'])
				);
			}
			else {
				$r = q("insert into profdef ( field_name, field_type, field_desc, field_help, field_inputs ) values ( '%s' , '%s', '%s', '%s', '%s' )",
					dbesc($_REQUEST['field_name']),
					dbesc($_REQUEST['field_type']),
					dbesc($_REQUEST['field_desc']),
					dbesc($_REQUEST['field_help']),
					dbesc($_REQUEST['field_inputs'])
				);
			}
		}
	
	
		// add to chosen array basic or advanced
	
		goaway(z_root() . '/admin/profs');
	}
	
	function admin_page_profs(&$a) {
	
		if((argc() > 3) && argv(2) == 'drop' && intval(argv(3))) {
			$r = q("delete from profdef where id = %d",
				intval(argv(3))
			);
			// remove from allowed fields
	
			goaway(z_root() . '/admin/profs');	
		}
	
		if((argc() > 2) && argv(2) === 'new') {
			return replace_macros(get_markup_template('profdef_edit.tpl'),array(
				'$header' => t('New Profile Field'),
				'$field_name' => array('field_name',t('Field nickname'),$_REQUEST['field_name'],t('System name of field')),
				'$field_type' => array('field_type',t('Input type'),(($_REQUEST['field_type']) ? $_REQUEST['field_type'] : 'text'),''),
				'$field_desc' => array('field_desc',t('Field Name'),$_REQUEST['field_desc'],t('Label on profile pages')),
				'$field_help' => array('field_help',t('Help text'),$_REQUEST['field_help'],t('Additional info (optional)')),
				'$submit' => t('Save')
			));
		}
	
		if((argc() > 2) && intval(argv(2))) {
			$r = q("select * from profdef where id = %d limit 1",
				intval(argv(2))
			);
			if(! $r) {
				notice( t('Field definition not found') . EOL);
				goaway(z_root() . '/admin/profs');
			}
	
			return replace_macros(get_markup_template('profdef_edit.tpl'),array(
				'$id' => intval($r[0]['id']),
				'$header' => t('Edit Profile Field'),
				'$field_name' => array('field_name',t('Field nickname'),$r[0]['field_name'],t('System name of field')),
				'$field_type' => array('field_type',t('Input type'),$r[0]['field_type'],''),
				'$field_desc' => array('field_desc',t('Field Name'),$r[0]['field_desc'],t('Label on profile pages')),
				'$field_help' => array('field_help',t('Help text'),$r[0]['field_help'],t('Additional info (optional)')),
				'$submit' => t('Save')
			));
		}
	
		$basic = '';
		$barr = array();
		$fields = get_profile_fields_basic();
		if(! $fields)
			$fields = get_profile_fields_basic(1);
		if($fields) {
			foreach($fields as $k => $v) {
				if($basic)
					$basic .= ', ';
				$basic .= trim($k);
				$barr[] = trim($k);
			}
		}
	
		$advanced = '';
		$fields = get_profile_fields_advanced();
		if(! $fields)
			$fields = get_profile_fields_advanced(1);
		if($fields) {
			foreach($fields as $k => $v) {
				if(in_array(trim($k),$barr))
					continue;
				if($advanced)
					$advanced .= ', ';
				$advanced .= trim($k);
			}
		}
	
		$all = '';
		$fields = get_profile_fields_advanced(1);
		if($fields) {
			foreach($fields as $k => $v) {
				if($all)
					$all .= ', ';
				$all .= trim($k);
			}
		}
	
		$r = q("select * from profdef where true");
		if($r) {
			foreach($r as $rr) {
				if($all)
					$all .= ', ';
				$all .= $rr['field_name'];
			}
		}
	
		
		$o = replace_macros(get_markup_template('admin_profiles.tpl'),array(
			'$title' => t('Profile Fields'),
			'$basic' => array('basic',t('Basic Profile Fields'),$basic,''),
			'$advanced' => array('advanced',t('Advanced Profile Fields'),$advanced,t('(In addition to basic fields)')),
			'$all' => $all,
			'$all_desc' => t('All available fields'),
			'$cust_field_desc' => t('Custom Fields'),
			'$cust_fields' => $r,
			'$edit' => t('Edit'),
			'$drop' => t('Delete'),
			'$new' => t('Create Custom Field'),		
			'$submit' => t('Submit')
		));
	
		return $o;
	
	
	}
	
}
