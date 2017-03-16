<?php

namespace Zotlabs\Widget;

class Admin {

	function widget($arr) {

		/*
		 * Side bar links
		 */

		if(! is_site_admin()) {
			return '';
		}

		$o = '';

		// array( url, name, extra css classes )

		$aside = [
			'site'      => array(z_root() . '/admin/site/',     t('Site'),           'site'),
			'accounts'  => array(z_root() . '/admin/accounts/', t('Accounts'),       'accounts', 'pending-update', t('Member registrations waiting for confirmation')),
			'channels'  => array(z_root() . '/admin/channels/', t('Channels'),       'channels'),
			'security'  => array(z_root() . '/admin/security/', t('Security'),       'security'),
			'features'  => array(z_root() . '/admin/features/', t('Features'),       'features'),
			'plugins'   => array(z_root() . '/admin/plugins/',  t('Plugins'),        'plugins'),
			'themes'    => array(z_root() . '/admin/themes/',   t('Themes'),         'themes'),
			'queue'     => array(z_root() . '/admin/queue',     t('Inspect queue'),  'queue'),
			'profs'     => array(z_root() . '/admin/profs',     t('Profile Fields'), 'profs'),
			'dbsync'    => array(z_root() . '/admin/dbsync/',   t('DB updates'),     'dbsync')
		];

		/* get plugins admin page */

		$r = q("SELECT * FROM addon WHERE plugin_admin = 1");

		$plugins = array();
		if($r) {
			foreach ($r as $h){
				$plugin = $h['aname'];
				$plugins[] = array(z_root() . '/admin/plugins/' . $plugin, $plugin, 'plugin');
				// temp plugins with admin
				\App::$plugins_admin[] = $plugin;
			}
		}

		$logs = array(z_root() . '/admin/logs/', t('Logs'), 'logs');

		$arr = array('links' => $aside,'plugins' => $plugins,'logs' => $logs);
		call_hooks('admin_aside',$arr);

		$o .= replace_macros(get_markup_template('admin_aside.tpl'), array(
			'$admin' => $aside,
			'$admtxt' => t('Admin'),
			'$plugadmtxt' => t('Plugin Features'),
			'$plugins' => $plugins,
			'$logtxt' => t('Logs'),
			'$logs' => $logs,
			'$h_pending' => t('Member registrations waiting for confirmation'),
			'$admurl'=> z_root() . '/admin/'
		));

		return $o;

	}
}

