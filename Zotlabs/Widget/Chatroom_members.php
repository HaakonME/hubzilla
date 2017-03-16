<?php

namespace Zotlabs\Widget;

class Chatroom_members {

	// The actual contents are filled in via AJAX

	function widget() {
		return replace_macros(get_markup_template('chatroom_members.tpl'), array(
			'$header' => t('Chat Members')
		));
	}

}
