<?php

namespace Zotlabs\Widget;

class Chatroom_list {

	function widget($arr) {

		if(! \App::$profile)
			return '';

		$r = \Zotlabs\Lib\Chatroom::roomlist(\App::$profile['profile_uid']);

		if($r) {
			return replace_macros(get_markup_template('chatroomlist.tpl'), array(
				'$header' => t('Chatrooms'),
				'$baseurl' => z_root(),
				'$nickname' => \App::$profile['channel_address'],
				'$items' => $r,
				'$overview' => t('Overview')
			));
		}
	}
}
