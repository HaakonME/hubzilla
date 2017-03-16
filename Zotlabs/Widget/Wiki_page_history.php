<?php

namespace Zotlabs\Widget;

class Wiki_page_history {

	function widget($arr) {

		$pageUrlName = ((array_key_exists('pageUrlName', $arr)) ? $arr['pageUrlName'] : '');
		$resource_id = ((array_key_exists('resource_id', $arr)) ? $arr['resource_id'] : '');

		$pageHistory = \Zotlabs\Lib\NativeWikiPage::page_history([
			'channel_id'    => \App::$profile_uid, 
			'observer_hash' => get_observer_hash(),
			'resource_id'   => $resource_id,
			'pageUrlName'   => $pageUrlName
		]);

		return replace_macros(get_markup_template('nwiki_page_history.tpl'), array(
			'$pageHistory' => $pageHistory['history'],
			'$permsWrite'  => $arr['permsWrite'],
			'$name_lbl'    => t('Name'),
			'$msg_label'   => t('Message','wiki_history')
		));

	}
}
