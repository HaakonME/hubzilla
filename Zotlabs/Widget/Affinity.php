<?php

namespace Zotlabs\Widget;

class Affinity {

	function widget($arr) {

		if(! local_channel())
			return '';
	
		// Get default cmin value from pconfig, but allow GET parameter to override
		$cmin = intval(get_pconfig(local_channel(),'affinity','cmin'));
		$cmin = (($cmin) ? $cmin : 0);
		$cmin = ((x($_REQUEST,'cmin')) ? intval($_REQUEST['cmin']) : $cmin);
	
		// Get default cmax value from pconfig, but allow GET parameter to override
		$cmax = intval(get_pconfig(local_channel(),'affinity','cmax'));
		$cmax = (($cmax) ? $cmax : 99);
		$cmax = ((x($_REQUEST,'cmax')) ? intval($_REQUEST['cmax']) : $cmax);


		if(feature_enabled(local_channel(),'affinity')) {

			$labels = array(
				t('Me'),
				t('Family'),
				t('Friends'),
				t('Acquaintances'),
				t('All')
			);
			call_hooks('affinity_labels',$labels);
			$label_str = '';

			if($labels) {
				foreach($labels as $l) {
					if($label_str) {
						$label_str .= ", '|'";
						$label_str .= ", '" . $l . "'";
					}
					else
						$label_str .= "'" . $l . "'";
				}
			}

			$tpl = get_markup_template('main_slider.tpl');
			$x = replace_macros($tpl,array(
				'$val' => $cmin . ',' . $cmax,
				'$refresh' => t('Refresh'),
				'$labels' => $label_str,
			));
		
			$arr = array('html' => $x);
			call_hooks('main_slider',$arr);
			return $arr['html'];
		}
 		return '';
	}
}
 