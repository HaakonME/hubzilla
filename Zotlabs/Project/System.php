<?php

namespace Zotlabs\Project;

class System {

	function get_platform_name() {
		if(is_array(\App::$config) && is_array(\App::$config['system']) && \App::$config['system']['platform_name'])
			return \App::$config['system']['platform_name'];
		return PLATFORM_NAME;
	}

	function get_site_name() {
		if(is_array(\App::$config) && is_array(\App::$config['system']) && \App::$config['system']['sitename'])
			return \App::$config['system']['sitename'];
		return '';
	}

	function get_project_version() {
		if(is_array(\App::$config) && is_array(\App::$config['system']) && \App::$config['system']['hide_version'])
			return '';
		return RED_VERSION;
	}

	function get_update_version() {
		if(is_array(\App::$config) && is_array(\App::$config['system']) && \App::$config['system']['hide_version'])
			return '';
		return DB_UPDATE_VERSION;
	}


	function get_notify_icon() {
		if(is_array(\App::$config) && is_array(\App::$config['system']) && \App::$config['system']['email_notify_icon_url'])
			return \App::$config['system']['email_notify_icon_url'];
		return z_root() . '/images/hz-white-32.png';
	}

	function get_site_icon() {
		if(is_array(\App::$config) && is_array(\App::$config['system']) && \App::$config['system']['site_icon_url'])
			return \App::$config['system']['site_icon_url'];
		return z_root() . '/images/hz-32.png';
	}


	function get_server_role() {
		if(UNO)
			return 'basic';
		return 'advanced';
	}

	// return the standardised version. Since we can't easily compare
	// before the STD_VERSION definition was applied, we have to treat 
	// all prior release versions the same. You can dig through them
	// with other means (such as RED_VERSION) if necessary. 

	function get_std_version() {
		if(defined('STD_VERSION'))
			return STD_VERSION;
		return '0.0.0';
	}


}
