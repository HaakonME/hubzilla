<?php

namespace Zotlabs\Widget;

class Pubsites {

	// used by site ratings pages to provide a return link

	function widget($arr) {
		if(\App::$poi)
			return;
		return '<div class="widget"><ul class="nav nav-pills"><li><a href="pubsites">' . t('Public Hubs') . '</a></li></ul></div>';
	}
}


