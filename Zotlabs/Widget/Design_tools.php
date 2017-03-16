<?php

namespace Zotlabs\Widget;

class Design_tools {

	function widget($arr) {

		// mod menu doesn't load a profile. For any modules which load a profile, check it.
		// otherwise local_channel() is sufficient for permissions.

		if(\App::$profile['profile_uid'])
			if((\App::$profile['profile_uid'] != local_channel()) && (! \App::$is_sys))
				return '';

		if(! local_channel())
			return '';

		return design_tools();
	}
}