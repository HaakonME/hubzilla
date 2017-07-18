<?php

namespace Zotlabs\Lib;


class ActivityStreams2 {

	public $data;
	public $valid = false;
	public $id    = '';
	public $type  = '';
	public $actor = null;
	public $obj   = null;
	public $tgt   = null;

	function __construct($string) {

		$this->data = json_decode($string,true);
		if($this->data) {
			$this->valid = true;
		}

		if($this->is_valid()) {
			$this->id    = $this->get_property_obj('id');
			$this->type  = $this->get_primary_type();
			$this->actor = $this->get_compound_property('actor');
			$this->obj   = $this->get_compound_property('object');
			$this->tgt   = $this->get_compound_property('target');
		}
	}

	function is_valid() {
		return $this->valid;
	}

	function get_property_obj($property,$base = '') {
		if(! $base) {
			$base = $this->data;
		}
		return $base[$property];
	}

	function fetch_property($url) {
		$redirects = 0;
		$x = z_fetch_url($url,true,$redirects,
			['headers' => [ 'Accept: application/ld+json; profile="https://www.w3.org/ns/activitystreams"']]);
		if($x['success'])
			return json_decode($x['body'],true);
		return null;
	}

	function get_compound_property($property,$base = '') {
		$x = $this->get_property_obj($property,$base);
		if($this->is_url($x)) {
			$x = $this->fetch_property($x); 	
		}
		return $x;
	}

	function is_url($url) {
		if(($url) && (! is_array($url)) && (strpos($url,'http') === 0)) {
			return true;
		}
		return false;
	}

	function get_primary_type($base = '') {
		if(! $base)
			$base = $this->data;
		$x = $this->get_property_obj('type',$base);
		if(is_array($x)) {
			foreach($x as $y) {
				if(strpos($y,':') === false) {
					return $y;
				}
			}
		}
		return $x;
	}

	function debug() {
		$x = var_export($this,true);
		return $x;
	}

}