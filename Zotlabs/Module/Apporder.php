<?php

namespace Zotlabs\Module;

use \Zotlabs\Lib as Zlib;

class Apporder extends \Zotlabs\Web\Controller {

	function post() {

	}

	function get() {

		if(! local_channel())
			return;

		nav_set_selected('Order Apps');

		$syslist = array();
		$list = Zlib\Apps::app_list(local_channel(), false, 'nav_featured_app');
		if($list) {
			foreach($list as $li) {
				$syslist[] = Zlib\Apps::app_encode($li);
			}
		}
		Zlib\Apps::translate_system_apps($syslist);

		usort($syslist,'Zotlabs\\Lib\\Apps::app_name_compare');

		$syslist = Zlib\Apps::app_order(local_channel(),$syslist);

		foreach($syslist as $app) {
			$nav_apps[] = Zlib\Apps::app_render($app,'nav-order');
		}

		return replace_macros(get_markup_template('apporder.tpl'),
			[
				'$header' => t('Change Order of Navigation Apps'),
				'$desc' => t('Use arrows to move the corresponding app up or down in the display list'),
				'$nav_apps' => $nav_apps
			]
		);
	}
}
