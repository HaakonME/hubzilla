<?php
/**
 * @file Zotlabs/Module/Post.php
 *
 * @brief Zot endpoint.
 *
 */

namespace Zotlabs\Module;

require_once('include/zot.php');

/**
 * @brief Post module.
 *
 */
class Post extends \Zotlabs\Web\Controller {

	function init() {
		if(array_key_exists('auth', $_REQUEST)) {
			$x = new \Zotlabs\Zot\Auth($_REQUEST);
			exit;
		}
	}

	function post() {
		if(array_key_exists('data',$_REQUEST)) {
			$z = new \Zotlabs\Zot\Receiver($_REQUEST['data'], get_config('system', 'prvkey'), new \Zotlabs\Zot\ZotHandler());
			exit;
		}

	}

}
