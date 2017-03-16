<?php

namespace Zotlabs\Widget;


class Archive {

	function widget($arr) {

		$o = '';

		if(! \App::$profile_uid) {
			return '';
		}

		$uid = \App::$profile_uid;

		if(! feature_enabled($uid,'archives'))
			return '';

		if(! perm_is_allowed($uid,get_observer_hash(),'view_stream'))
			return '';

		$wall = ((array_key_exists('wall', $arr)) ? intval($arr['wall']) : 0);
		$style = ((array_key_exists('style', $arr)) ? $arr['style'] : 'select');
		$showend = ((get_pconfig($uid,'system','archive_show_end_date')) ? true : false);
		$mindate = get_pconfig($uid,'system','archive_mindate');
		$visible_years = get_pconfig($uid,'system','archive_visible_years');
		if(! $visible_years)
			$visible_years = 5;

		$url = z_root() . '/' . \App::$cmd;

		$ret = list_post_dates($uid,$wall,$mindate);

		if(! count($ret))
			return '';

		$cutoff_year = intval(datetime_convert('',date_default_timezone_get(),'now','Y')) - $visible_years;
		$cutoff = ((array_key_exists($cutoff_year,$ret))? true : false);

		$o = replace_macros(get_markup_template('posted_date_widget.tpl'),array(
			'$title' => t('Archives'),
			'$size' => $visible_years,
			'$cutoff_year' => $cutoff_year,
			'$cutoff' => $cutoff,
			'$url' => $url,
			'$style' => $style,
			'$showend' => $showend,
			'$dates' => $ret
		));
		return $o;
	}
}

