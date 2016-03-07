<?php

namespace Zotlabs\Web;


class OpenGraph {

	private $vars = null;

	function __construct() {

		$this->vars = array();

	}

	function set($property,$value) {
		$this->vars[$property] = $value;
	}

	function check_required() {
		if(
			($this->vars) 
			&& array_key_exists('og:title',$this->vars) 
			&& array_key_exists('og:type', $this->vars) 
			&& array_key_exists('og:image',$this->vars) 
			&& array_key_exists('og:url',  $this->vars)
		)
			return true;
		return false;
	}

	function get() {
		if($this->check_required()) {
			$o = "\r\n";
			foreach($this->vars as $k => $v) {
				$o .= '<meta property="' . $k . '" content="' . urlencode($v) . '" />' . "\r\n" ;
			}
			return $o;
		}
		return '';
	}

}