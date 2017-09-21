<?php

namespace Zotlabs\Lib;

class ActivityStreams {

	public $data;
	public $valid  = false;
	public $id     = '';
	public $type   = '';
	public $actor  = null;
	public $obj    = null;
	public $tgt    = null;
	public $origin = null;
	public $owner  = null;
	public $signer = null;
	public $ldsig  = null;
	public $sigok  = false;
	public $recips = null;
	public $raw_recips = null;

	function __construct($string) {

		$this->data = json_decode($string,true);
		if($this->data) {
			$this->valid = true;
		}

		if($this->is_valid()) {
			$this->id     = $this->get_property_obj('id');
			$this->type   = $this->get_primary_type();
			$this->actor  = $this->get_compound_property('actor');
			$this->obj    = $this->get_compound_property('object');
			$this->tgt    = $this->get_compound_property('target');
			$this->origin = $this->get_compound_property('origin');
			$this->recips = $this->collect_recips();

			$this->ldsig = $this->get_compound_property('signature');
			if($this->ldsig) {
				$this->signer = $this->get_compound_property('creator',$this->ldsig);
				if($this->signer && $this->signer['publicKey'] && $this->signer['publicKey']['publicKeyPem']) {
					$this->sigok = \Zotlabs\Lib\LDSignatures::verify($this->data,$this->signer['publicKey']['publicKeyPem']);
				}
			}

			if(($this->type === 'Note') && (! $this->obj)) {
				$this->obj = $this->data;
				$this->type = 'Create';
			}
		}
	}

	function is_valid() {
		return $this->valid;
	}

	function set_recips($arr) {
		$this->saved_recips = $arr;
	}

	function collect_recips($base = '',$namespace = '') {
		$x = [];
		$fields = [ 'to','cc','bto','bcc','audience'];
		foreach($fields as $f) {
			$y = $this->get_compound_property($f,$base,$namespace);
			if($y) {
				$x = array_merge($x,$y);
				if(! is_array($this->raw_recips))
					$this->raw_recips = [];
				$this->raw_recips[$f] = $x;
			}
		}						
// not yet ready for prime time
//		$x = $this->expand($x,$base,$namespace);
		return $x;
	}

	function expand($arr,$base = '',$namespace = '') {
		$ret = [];

		// right now use a hardwired recursion depth of 5

		for($z = 0; $z < 5; $z ++) {
			if(is_array($arr) && $arr) {
				foreach($arr as $a) {
					if(is_array($a)) {
						$ret[] = $a;
					}
					else {
						$x = $this->get_compound_property($a,$base,$namespace);
						if($x) {
							$ret = array_merge($ret,$x);
						}
					}
				}
			}
		}

		// @fixme de-duplicate

		return $ret;
	}

	function get_namespace($base,$namespace) {

		if(! $namespace)
			return '';

		$key = null;


		foreach( [ $this->data, $base ] as $b ) {
			if(! $b)
				continue;
			if(array_key_exists('@context',$b)) {
				if(is_array($b['@context'])) {
					foreach($b['@context'] as $ns) {
						if(is_array($ns)) {
							foreach($ns as $k => $v) {
								if($namespace === $v)
									$key = $k;
							}
						}
						else {
							if($namespace === $ns) {
								$key = '';
							}
						}
					}
				}
				else {
					if($namespace === $b['@context']) {
						$key = '';
					}
				}
			}
		}
		return $key;
	}


	function get_property_obj($property,$base = '',$namespace = '' ) {
		$prefix = $this->get_namespace($base,$namespace);
		if($prefix === null)
			return null;	
		$base = (($base) ? $base : $this->data);
		$propname = (($prefix) ? $prefix . ':' : '') . $property;
		return ((array_key_exists($propname,$base)) ? $base[$propname] : null);
	}

	function fetch_property($url) {
		$redirects = 0;
		if(! check_siteallowed($url)) {
			logger('blacklisted: ' . $url);
			return null;
		}

		$x = z_fetch_url($url,true,$redirects,
			['headers' => [ 'Accept: application/ld+json; profile="https://www.w3.org/ns/activitystreams", application/activity+json' ]]);
		if($x['success'])
			return json_decode($x['body'],true);
		return null;
	}

	function get_compound_property($property,$base = '',$namespace = '') {
		$x = $this->get_property_obj($property,$base,$namespace);
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

	function get_primary_type($base = '',$namespace = '') {
		if(! $base)
			$base = $this->data;
		$x = $this->get_property_obj('type',$base,$namespace);
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