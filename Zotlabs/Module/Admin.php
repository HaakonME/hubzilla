<?php
/**
 * @file Zotlabs/Module/Admin.php
 * @brief Hubzilla's admin controller.
 *
 * Controller for the /admin/ area.
 */

namespace Zotlabs\Module;

require_once('include/queue_fn.php');
require_once('include/account.php');

/**
 * @brief Admin area.
 *
 */
class Admin extends \Zotlabs\Web\Controller {

	private $sm = null;

	function __construct() {
		$this->sm = new \Zotlabs\Web\SubModule();
	}

	function post(){
		logger('admin_post', LOGGER_DEBUG);

		if(! is_site_admin()) {
			return;
		}
		if (argc() > 1) {
			$this->sm->call('post');
		}

		goaway(z_root() . '/admin' );
	}

	/**
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

		nav_set_selected('Admin');

		$o = '';

		if(argc() > 1) {
			$o = $this->sm->call('get');
			if($o === false) {
				notice( t('Item not found.') );
			}
		}
		else {
			$o = $this->admin_page_summary();
		}

		if(is_ajax()) {
			echo $o;
			killme();
			return '';
		}
		else {
			return $o;
		}
	}


	/**
	 * @brief Returns content for Admin Summary Page.
	 *
	 * @return string HTML from parsed admin_summary.tpl
	 */
	function admin_page_summary() {

		// list total user accounts, expirations etc.
		$accounts = array();
		$r = q("SELECT COUNT(*) AS total, COUNT(CASE WHEN account_expires > %s THEN 1 ELSE NULL END) AS expiring, COUNT(CASE WHEN account_expires < %s AND account_expires > '%s' THEN 1 ELSE NULL END) AS expired, COUNT(CASE WHEN (account_flags & %d)>0 THEN 1 ELSE NULL END) AS blocked FROM account",
			db_utcnow(),
			db_utcnow(),
			dbesc(NULL_DATE),
			intval(ACCOUNT_BLOCKED)
		);
		if ($r) {
			$accounts['total']    = array('label' => t('Accounts'), 'val' => $r[0]['total']);
			$accounts['blocked']  = array('label' => t('Blocked accounts'), 'val' => $r[0]['blocked']);
			$accounts['expired']  = array('label' => t('Expired accounts'), 'val' => $r[0]['expired']);
			$accounts['expiring'] = array('label' => t('Expiring accounts'), 'val' => $r[0]['expiring']);
		}

		// pending registrations
		$r = q("SELECT COUNT(id) AS rtotal FROM register WHERE uid != '0'");
		$pending = $r[0]['rtotal'];

		// available channels, primary and clones
		$channels = array();
		$r = q("SELECT COUNT(*) AS total, COUNT(CASE WHEN channel_primary = 1 THEN 1 ELSE NULL END) AS main, COUNT(CASE WHEN channel_primary = 0 THEN 1 ELSE NULL END) AS clones FROM channel WHERE channel_removed = 0");
		if ($r) {
			$channels['total']  = array('label' => t('Channels'), 'val' => $r[0]['total']);
			$channels['main']   = array('label' => t('Primary'), 'val' => $r[0]['main']);
			$channels['clones'] = array('label' => t('Clones'), 'val' => $r[0]['clones']);
		}

		// We can do better, but this is a quick queue status
		$r = q("SELECT COUNT(outq_delivered) AS total FROM outq WHERE outq_delivered = 0");
		$queue = (($r) ? $r[0]['total'] : 0);
		$queues = array( 'label' => t('Message queues'), 'queue' => $queue );

		// If no plugins active return 0, otherwise list of plugin names
		$plugins = (count(\App::$plugins) == 0) ? count(\App::$plugins) : \App::$plugins;

		if(is_array($plugins))
			sort($plugins);

		// Could be extended to provide also other alerts to the admin
		$alertmsg = '';

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
			'$build'    => get_config('system', 'db_version')
		));
	}

}
