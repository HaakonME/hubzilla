<?php

namespace Zotlabs\Widget;

class Helpindex {

	function widget($arr) {

		$o .= '<div class="widget">';

		$level_0 = get_help_content('sitetoc');
		if(! $level_0)
			$level_0 = get_help_content('toc');

		$level_0 = preg_replace('/\<ul(.*?)\>/','<ul class="nav nav-pills flex-column">',$level_0);

		$levels = array();


		if(argc() > 2) {
			$path = '';
			for($x = 1; $x < argc(); $x ++) {
				$path .= argv($x) . '/';
				$y = get_help_content($path . 'sitetoc');
				if(! $y)
					$y = get_help_content($path . 'toc');
				if($y)
					$levels[] = preg_replace('/\<ul(.*?)\>/','<ul class="nav nav-pills flex-column">',$y);
			}
		}

		if($level_0)
			$o .= $level_0;
		if($levels) {
			foreach($levels as $l) {
				$o .= '<br /><br />';
				$o .= $l;
			}
		}

		$o .= '</div>';

		return $o;
	}
}
