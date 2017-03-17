<?php

namespace Zotlabs\Widget;

class Rating {

	function widget($arr) {


		$rating_enabled = get_config('system','rating_enabled');
		if(! $rating_enabled) {
			return;
		}

		if($arr['target'])
			$hash = $arr['target'];
		else
			$hash = \App::$poi['xchan_hash'];

		if(! $hash)
			return;

		$url = '';
		$remote = false;

		if(remote_channel() && ! local_channel()) {
			$ob = \App::get_observer();
			if($ob && $ob['xchan_url']) {
				$p = parse_url($ob['xchan_url']);
				if($p) {
					$url = $p['scheme'] . '://' . $p['host'] . (($p['port']) ? ':' . $p['port'] : '');
					$url .= '/rate?f=&target=' . urlencode($hash);
				}
				$remote = true;
			}
		}

		$self = false;

		if(local_channel()) {
			$channel = \App::get_channel();

			if($hash == $channel['channel_hash'])
				$self = true;

			head_add_js('ratings.js');
		}


		$o = '<div class="widget">';
		$o .= '<h3>' . t('Rating Tools') . '</h3>';

		if((($remote) || (local_channel())) && (! $self)) {
			if($remote)
				$o .= '<a class="btn btn-block btn-primary btn-sm" href="' . $url . '"><i class="fa fa-pencil"></i> ' . t('Rate Me') . '</a>';
			else
				$o .= '<div class="btn btn-block btn-primary btn-sm" onclick="doRatings(\'' . $hash . '\'); return false;"><i class="fa fa-pencil"></i> ' . t('Rate Me') . '</div>';
		}

		$o .= '<a class="btn btn-block btn-default btn-sm" href="ratings/' . $hash . '"><i class="fa fa-eye"></i> ' . t('View Ratings') . '</a>';
		$o .= '</div>';

		return $o;

	}
}

