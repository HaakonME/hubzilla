<?php

namespace Zotlabs\Widget;


class Profile {

	function widget($args) {
		$block = observer_prohibited();
		return profile_sidebar(\App::$profile, $block, true);
	}

}