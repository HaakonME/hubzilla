<?php

namespace Zotlabs\Widget;


class Wiki_pages {

	function widget($arr) {

		$channelname = ((array_key_exists('channel',$arr)) ? $arr['channel'] : '');
		$c = channelx_by_nick($channelname);

		if(! $c)
			$c = \App::get_channel();

		if(! $c)
			return '';

		$wikiname = '';
		if(array_key_exists('refresh', $arr)) {
			$not_refresh = (($arr['refresh']=== true) ? false : true);
		}
		else {
			$not_refresh = true;
		}

		$pages = array();
		if(! array_key_exists('resource_id', $arr)) {
			$hide = true;
		}
		else {
			$p = \Zotlabs\Lib\NativeWikiPage::page_list($c['channel_id'],get_observer_hash(),$arr['resource_id']);

			if($p['pages']) {
				$pages = $p['pages'];
				$w = $p['wiki'];
				// Wiki item record is $w['wiki']
				$wikiname = $w['urlName'];
				if (!$wikiname) {
					$wikiname = '';
				}
			}
		}


		$can_create = perm_is_allowed(\App::$profile['uid'],get_observer_hash(),'write_wiki');

		$can_delete = ((local_channel() && (local_channel() == \App::$profile['uid'])) ? true : false);

		return replace_macros(get_markup_template('wiki_page_list.tpl'), array(
				'$hide' => $hide,
				'$resource_id' => $arr['resource_id'],
				'$not_refresh' => $not_refresh,
				'$header' => t('Wiki Pages'),
				'$channel' => $channelname,
				'$wikiname' => $wikiname,
				'$pages' => $pages,
				'$canadd' => $can_create,
				'$candel' => $can_delete,
				'$addnew' => t('Add new page'),
				'$pageName' => array('pageName', t('Page name')),
		));
	}
}


