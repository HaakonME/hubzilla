<?php

namespace Zotlabs\Widget;

// @FIXME The problem with this widget is that we don't have a search function for webpages 
// that we can send the links to. Then we should also provide an option to search webpages
// and conversations.

class Tagcloud {

	function widget($args) {

		$o = '';
		$uid = \App::$profile_uid;
		$count = ((x($args,'count')) ? intval($args['count']) : 24);
		$flags = 0;
		$type = TERM_CATEGORY;

		// @FIXME there exists no $authors variable
		$r = tagadelic($uid, $count, $authors, $owner, $flags, ITEM_TYPE_WEBPAGE, $type);

		// @FIXME this should use a template

		if($r) {
			$o = '<div class="tagblock widget"><h3>' . t('Categories') . '</h3><div class="tags" align="center">';
			foreach($r as $rv) {
				$o .= '<span class="tag' . $rv[2] . '">' . $rv[0] .' </span> ' . "\r\n";
			}
			$o .= '</div></div>';
		}
		return $o;
	}
}
