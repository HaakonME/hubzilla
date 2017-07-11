<?php

namespace Zotlabs\Module\Settings;


class Featured {
		
	function post() {
		check_form_security_token_redirectOnErr('/settings/featured', 'settings_featured');
	
		call_hooks('feature_settings_post', $_POST);
	
		if($_POST['affinity_slider-submit']) {
			if(intval($_POST['affinity_cmax'])) {
				set_pconfig(local_channel(),'affinity','cmax',intval($_POST['affinity_cmax']));
			}
			if(intval($_POST['affinity_cmin'])) {
				set_pconfig(local_channel(),'affinity','cmin',intval($_POST['affinity_cmin']));			
			}
			if(intval($_POST['affinity_cmax']) || intval($_POST['affinity_cmin'])) {
				info( t('Affinity Slider settings updated.') . EOL);
			}
		}
		
		build_sync_packet();
		return;
	}

	function get() {
		$settings_addons = "";
	
		$o = '';
			
		$r = q("SELECT * FROM hook WHERE hook = 'feature_settings' ");
		if(! $r)
			$settings_addons = t('No feature settings configured');
	
		if(feature_enabled(local_channel(),'affinity')) {
			
			$cmax = intval(get_pconfig(local_channel(),'affinity','cmax'));
			$cmax = (($cmax) ? $cmax : 99);
			$setting_fields .= replace_macros(get_markup_template('field_input.tpl'), array(
				'$field'    => array('affinity_cmax', t('Default maximum affinity level'), $cmax, '')
			));
			$cmin = intval(get_pconfig(local_channel(),'affinity','cmin'));
			$cmin = (($cmin) ? $cmin : 0);
			$setting_fields .= replace_macros(get_markup_template('field_input.tpl'), array(
				'$field'    => array('affinity_cmin', t('Default minimum affinity level'), $cmin, '')
			));

			$settings_addons .= replace_macros(get_markup_template('generic_addon_settings.tpl'), array(
				'$addon'    => array('affinity_slider', '' . t('Affinity Slider Settings'), '', t('Submit')),
				'$content'  => $setting_fields
			));
		}

		call_hooks('feature_settings', $settings_addons);
					
		$tpl = get_markup_template("settings_addons.tpl");
		$o .= replace_macros($tpl, array(
			'$form_security_token' => get_form_security_token("settings_featured"),
			'$title'	=> t('Feature/Addon Settings'),
			'$settings_addons' => $settings_addons
		));
		return $o;
	}
	
}