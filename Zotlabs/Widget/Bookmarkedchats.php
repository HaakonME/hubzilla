<?php

namespace Zotlabs\Widget;

class Bookmarkedchats {

	function widget($arr) {

		if(! feature_enabled(\App::$profile['profile_uid'],'ajaxchat'))
			return '';

		$h = get_observer_hash();
		if(! $h)
			return;
		$r = q("select xchat_url, xchat_desc from xchat where xchat_xchan = '%s' order by xchat_desc",
			dbesc($h)
		);
		if($r) {
			for($x = 0; $x < count($r); $x ++) {
				$r[$x]['xchat_url'] = zid($r[$x]['xchat_url']);
			}
		}
		return replace_macros(get_markup_template('bookmarkedchats.tpl'),array(
			'$header' => t('Bookmarked Chatrooms'),
			'$rooms' => $r
		));
	}
}
