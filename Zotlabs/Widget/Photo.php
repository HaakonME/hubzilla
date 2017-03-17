<?php

namespace Zotlabs\Widget;


class Photo {


	/**
	 * @brief Widget to display a single photo.
	 *
	 * @param array $arr associative array with
	 *    * \e string \b src URL of photo; URL must be an http or https URL
	 *    * \e boolean \b zrl use zid in URL
	 *    * \e string \b style CSS string
	 *
	 * @return string with parsed HTML
	 */

	function widget($arr) {

		$style = $zrl = false;

		if(array_key_exists('src', $arr) && isset($arr['src']))
			$url = $arr['src'];

		if(strpos($url, 'http') !== 0)
			return '';

		if(array_key_exists('style', $arr) && isset($arr['style']))
			$style = $arr['style'];

		// ensure they can't sneak in an eval(js) function

		if(strpbrk($style, '(\'"<>' ) !== false)
			$style = '';

		if(array_key_exists('zrl', $arr) && isset($arr['zrl']))
			$zrl = (($arr['zrl']) ? true : false);

		if($zrl)
			$url = zid($url);

		$o = '<div class="widget">';

		$o .= '<img ' . (($zrl) ? ' class="zrl" ' : '')
					  . (($style) ? ' style="' . $style . '"' : '')
					  . ' src="' . $url . '" alt="' . t('photo/image') . '">';

		$o .= '</div>';

		return $o;
	}
}

