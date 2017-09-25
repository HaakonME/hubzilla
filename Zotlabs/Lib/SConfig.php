<?php

namespace Zotlabs\Lib;

// account configuration storage is built on top of the under-utilised xconfig

class SConfig {

	static public function Load($server_id) {
		return XConfig::Load('s_' . $server_id);
	}

	static public function Get($server_id,$family,$key,$default = false) {
		return XConfig::Get('s_' . $server_id,$family,$key, $default);
	}

	static public function Set($server_id,$family,$key,$value) {
		return XConfig::Set('s_' . $server_id,$family,$key,$value);
	}

	static public function Delete($server_id,$family,$key) {
		return XConfig::Delete('s_' . $server_id,$family,$key);
	}

}
