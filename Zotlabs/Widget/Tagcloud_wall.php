<?php

namespace Zotlabs\Widget;

class Tagcloud_wall {

	function widget($arr) {

		if((! \App::$profile['profile_uid']) || (! \App::$profile['channel_hash']))
			return '';
		if(! perm_is_allowed(\App::$profile['profile_uid'], get_observer_hash(), 'view_stream'))
			return '';

		$limit = ((array_key_exists('limit', $arr)) ? intval($arr['limit']) : 50);
		if(feature_enabled(\App::$profile['profile_uid'], 'tagadelic'))
			return wtagblock(\App::$profile['profile_uid'], $limit, '', \App::$profile['channel_hash'], 'wall');

		return '';
	}
}
