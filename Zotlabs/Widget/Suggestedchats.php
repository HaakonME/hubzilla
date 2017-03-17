<?php

namespace Zotlabs\Widget;

class Suggestedchats {

	function widget($arr) {

		if(! feature_enabled(\App::$profile['profile_uid'],'ajaxchat'))
			return '';

		// There are reports that this tool does not ever remove chatrooms on dead sites,
		// and also will happily link to private chats which you cannot enter.
		// For those reasons, it will be disabled until somebody decides it's worth
		// fixing and comes up with a plan for doing so.

		return '';

		// probably should restrict this to your friends, but then the widget will only work
		// if you are logged in locally.

		$h = get_observer_hash();
		if(! $h)
			return;
		$r = q("select xchat_url, xchat_desc, count(xchat_xchan) as total from xchat group by xchat_url, xchat_desc order by total desc, xchat_desc limit 24");
		if($r) {
			for($x = 0; $x < count($r); $x ++) {
				$r[$x]['xchat_url'] = zid($r[$x]['xchat_url']);
			}
		}
		return replace_macros(get_markup_template('bookmarkedchats.tpl'),array(
			'$header' => t('Suggested Chatrooms'),
			'$rooms' => $r
		));
	}
}

