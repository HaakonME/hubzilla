<?php

namespace Zotlabs\Widget;

class Shortprofile {

	function widget($arr) {

		if(! \App::$profile['profile_uid'])
			return;

		$block = observer_prohibited();

		return profile_sidebar(\App::$profile, $block, true, true);
	}

}

