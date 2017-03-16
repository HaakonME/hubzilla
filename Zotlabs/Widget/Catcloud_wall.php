<?php

namespace Zotlabs\Widget;

class Catcloud_wall {

	function widget($arr) {

		if((! \App::$profile['profile_uid']) || (! \App::$profile['channel_hash']))
			return '';
		if(! perm_is_allowed(\App::$profile['profile_uid'], get_observer_hash(), 'view_stream'))
			return '';

		$limit = ((array_key_exists('limit',$arr)) ? intval($arr['limit']) : 50);

		return catblock(\App::$profile['profile_uid'], $limit, '', \App::$profile['channel_hash'], 'wall');
	}

}
