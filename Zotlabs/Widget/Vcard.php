<?php

namespace Zotlabs\Widget;

class Vcard {

	function widget($arr) {
		return vcard_from_xchan('', \App::get_observer());
	}

}

