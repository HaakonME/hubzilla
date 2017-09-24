<?php

namespace Zotlabs\Widget;

require_once('include/contact_widgets.php');

class Common_friends {

	function widget($arr) {

		if((! \App::$profile['profile_uid']) 
			|| (! perm_is_allowed(\App::$profile['profile_uid'],get_observer_hash(),'view_contacts'))) {
			return '';
		}

		return common_friends_visitor_widget(\App::$profile['profile_uid']);

	}
}
