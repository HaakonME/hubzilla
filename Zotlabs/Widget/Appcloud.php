<?php

namespace Zotlabs\Widget;

class Appcloud {

	function widget($arr) {
		if(! local_channel())
			return '';
		return app_tagblock(z_root() . '/apps');
	}
}

