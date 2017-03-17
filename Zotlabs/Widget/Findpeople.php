<?php

namespace Zotlabs\Widget;

require_once('include/contact_widgets.php');

class Findpeople {
	function widget($arr) {
		return findpeople_widget();
	}
}

