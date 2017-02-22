<?php
namespace Zotlabs\Module;

// See update_profile.php for documentation

require_once('include/group.php');


class Update_display extends \Zotlabs\Web\Controller {

	function get() {
	
		$profile_uid = intval($_GET['p']);
		if(! $profile_uid)
			$profile_uid = (-1);
		$load = (((argc() > 1) && (argv(1) == 'load')) ? 1 : 0);
		header("Content-type: text/html");
		echo "<!DOCTYPE html><html><body>\r\n";
		echo (($_GET['msie'] == 1) ? '<div>' : '<section>');
	
		$mod = new Display();
		$text = $mod->get($profile_uid, $load);

		echo str_replace("\t",'       ',$text);
		echo (($_GET['msie'] == 1) ? '</div>' : '</section>');
		echo "</body></html>\r\n";

		killme();
	
	}
	
}
