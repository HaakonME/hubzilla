<?php

namespace Zotlabs\Widget;

class Wiki_list {

	function widget($arr) {

		$channel = channelx_by_n(\App::$profile_uid);

		$wikis = \Zotlabs\Lib\NativeWiki::listwikis($channel,get_observer_hash());

		if($wikis) {
			return replace_macros(get_markup_template('wikilist_widget.tpl'), array(
				'$header' => t('Wiki List'),
				'$channel' => $channel['channel_address'],
				'$wikis' => $wikis['wikis']
			));
		}
		return '';
	}

}
