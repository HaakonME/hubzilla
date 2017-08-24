<?php

namespace Zotlabs\Module;

/**
 * Module: update_profile
 * Purpose: AJAX synchronisation of profile page
 *
 */


class Update_cards extends \Zotlabs\Web\Controller {

function get() {

	$profile_uid = intval($_GET['p']);
	$load = (((argc() > 1) && (argv(1) == 'load')) ? 1 : 0);

	header("Content-type: text/html");
	echo "<!DOCTYPE html><html><body><section></section></body></html>\r\n";

	killme();


	$mod = new Cards();

	$text = $mod->get($profile_uid,$load);

	/**
	 * reportedly some versions of MSIE don't handle tabs in XMLHttpRequest documents very well
	 */

	echo str_replace("\t",'       ',$text);
	echo (($_GET['msie'] == 1) ? '</div>' : '</section>');
	echo "</body></html>\r\n";
	killme();

}
}
