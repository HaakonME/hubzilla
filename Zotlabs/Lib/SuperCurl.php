<?php

namespace Zotlabs\Lib;

/**
 * @brief wrapper for z_fetch_url() which can be instantiated with several built-in parameters and 
 * these can be modified and re-used. Useful for CalDAV and other processes which need to authenticate
 * and set lots of CURL options (many of which stay the same from one call to the next). 
 */




class SuperCurl {

	
    private $auth;
    private $url;

	private $curlopt = array();

	private $headers = null;
    public $filepos = 0;
	public $filehandle = 0;
    public $request_data = '';

	private $request_method = 'GET';
	private $upload = false;


    private function set_data($s) {
        $this->request_data = $s;
        $this->filepos = 0;
    }

    public function curl_read($ch,$fh,$size) {

        if($this->filepos < 0) {
            unset($fh);
            return '';
        }

        $s = substr($this->request_data,$this->filepos,$size);

        if(strlen($s) < $size)
            $this->filepos = (-1);
        else
            $this->filepos = $this->filepos + $size;

        return $s;
    }


	public function __construct($opts = array()) {
		$this->set($opts);
	}

	private function set($opts = array()) {
		if($opts) {
			foreach($opts as $k => $v) {
				switch($k) {
					case 'http_auth':
						$this->auth = $v;
						break;
					case 'custom':
						$this->request_method = $v;
						break;
					case 'url':
						$this->url = $v;
						break;
					case 'data':
						$this->set_data($v);
						if($v) {
							$this->upload = true;
						}
						else {
							$this->upload = false;
						}							
						break;
					case 'headers':
						$this->headers = $v;
						break;
					default:
						$this->curlopts[$k] = $v;
						break;
				}
			}
		}
	}

	function exec() {
		$opts = $this->curlopts;
		if($this->auth)
			$opts['http_auth'] = $this->auth;
		if($this->custom)
			$opts['custom'] = $this->custom;
		if($this->headers)
			$opts['headers'] = $this->headers;
		if($this->upload) {
			$opts['upload'] = true;
			$opts['infile'] = $this->filehandle;
			$opts['infilesize'] = strlen($this->request_data);
			$opts['readfunc'] = [ $this, 'curl_read' ] ;
		}

		$recurse = 0;
		return z_fetch_url($this->url,true,$recurse,(($opts) ? $opts : null)); 
		
	}
		
	
}
