<?php

namespace Zotlabs\Widget;

class Zcard {

	function widget($args) {
		$channel = channelx_by_n(\App::$profile_uid);
		return get_zcard($channel,get_observer_hash(),array('width' => 875));
	}
}
