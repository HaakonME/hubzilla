<?php

namespace Zotlabs\Web;

/**
 * Implements HTTP Signatures per draft-cavage-http-signatures-07
 */


class HTTPSig {

	// See RFC5843

	static function generate_digest($body,$set = true) {
		$digest = base64_encode(hash('sha256',$body,true));

		if($set) {
			header('Digest: SHA-256=' . $digest);
		}
		return $digest;
	}

	// See draft-cavage-http-signatures-07

	static function verify($data,$key = '') {

		$body      = $data;
		$headers   = null;
		$spoofable = false;

		$result = [
			'signer'         => '',
			'header_signed'  => false,
			'header_valid'   => false,
			'content_signed' => false,
			'content_valid'  => false
		];

		// decide if $data arrived via controller submission or curl
		if(is_array($data) && $data['header']) {
			if(! $data['success'])
				return $result;
			$h = new \Zotlabs\Web\HTTPHeaders($data['header']);
			$headers = $h->fetcharr();
			$body = $data['body'];
		}

		else {
			$headers = [];
			$headers['(request-target)'] = 
				$_SERVER['REQUEST_METHOD'] . ' ' .
				$_SERVER['REQUEST_URI'];
			foreach($_SERVER as $k => $v) {
				if(strpos($k,'HTTP_') === 0) {
					$field = str_replace('_','-',strtolower(substr($k,5)));
					$headers[$field] = $v;
				}
			}
		}

		$sig_block = null;

		if(array_key_exists('signature',$headers)) {
			$sig_block = self::parse_sigheader($headers['signature']);
		}
		elseif(array_key_exists('authorization',$headers)) {
			$sig_block = self::parse_sigheader($headers['authorization']);
		}

		if(! $sig_block)
			return $result;

		$result['header_signed'] = true;

		$signed_headers = $sig_block['headers'];
		if(! $signed_headers) 
			$signed_headers = [ 'date' ];

		$signed_data = '';
		foreach($signed_headers as $h) {
			if(array_key_exists($h,$headers)) {
				$signed_data .= $h . ': ' . $headers[$h] . "\n";
			}
			if(strpos($h,'.')) {
				$spoofable = true;
			}
		}
		$signed_data = rtrim($signed_data,"\n");

		$algorithm = null;
		if($sig_block['algorithm'] === 'rsa-sha256') {
			$algorithm = 'sha256';
		}
		if($sig_block['algorithm'] === 'rsa-sha512') {
			$algorithm = 'sha512';
		}

		if($key && function_exists($key)) {
			$result['signer'] = $sig_block['keyId'];
			$key = $key($sig_block['keyId']);
		}

		if(! $key) {
			$result['signer'] = $sig_block['keyId'];
			$key = self::get_activitypub_key($sig_block['keyId']);
		}

		if(! $key)
			return $result;

		$x = rsa_verify($signed_data,$sig_block['signature'],$key,$algorithm);

		if($x === false)
			return $result;

		if(! $spoofable)
			$result['header_valid'] = true;

		if(in_array('digest',$signed_headers)) {
			$result['content_signed'] = true;
			$digest = explode('=', $headers['digest']);
			if($digest[0] === 'SHA-256')
				$hashalg = 'sha256';
			if($digest[0] === 'SHA-512')
				$hashalg = 'sha512';

			// The explode operation will have stripped the '=' padding, so compare against unpadded base64 
			if(rtrim(base64_encode(hash($hashalg,$body,true)),'=') === $digest[1]) {
				$result['content_valid'] = true;
			}
		}

		return $result;

	}

	function get_activitypub_key($id) {

		if(strpos($id,'acct:') === 0) {
			$x = q("select xchan_pubkey from xchan left join hubloc on xchan_hash = hubloc_hash where hubloc_addr = '%s' limit 1",
				dbesc(str_replace('acct:','',$id))
			);
		}
		else {
			$x = q("select xchan_pubkey from xchan where xchan_hash = '%s' and xchan_network = 'activitypub' ",
				dbesc($id)
			);
		}

		if($x && $x[0]['xchan_pubkey']) {
			return ($x[0]['xchan_pubkey']);
		}
		$r = as_fetch($id);

		if($r) {
			$j = json_decode($r,true);

			if($j['id'] !== $id)
				return false; 
			if(array_key_exists('publicKey',$j) && array_key_exists('publicKeyPem',$j['publicKey'])) {
				return($j['publicKey']['publicKeyPem']);
			}
		}
		return false;
	}




	static function create_sig($request,$head,$prvkey,$keyid = 'Key',$send_headers = false,$auth = false,$alg = 'sha256') {

		$return_headers = [];

		if($alg === 'sha256') {
			$algorithm = 'rsa-sha256';
		}
		if($alg === 'sha512') {
			$algorithm = 'rsa-sha512';
		}

		$x = self::sign($request,$head,$prvkey,$alg);			

		if($auth) {
			$sighead = 'Authorization: Signature keyId="' . $keyid . '",algorithm="' . $algorithm
			. '",headers="' . $x['headers'] . '",signature="' . $x['signature'] . '"';
		}
		else {
			$sighead = 'Signature: keyId="' . $keyid . '",algorithm="' . $algorithm
			. '",headers="' . $x['headers'] . '",signature="' . $x['signature'] . '"';
		}

		if($head) {
			foreach($head as $k => $v) {
				if($send_headers) {
					header($k . ': ' . $v);
				}
				else {
					$return_headers[] = $k . ': ' . $v;
				}
			}
		}
		if($send_headers) {
			header($sighead);
		}
		else {
			$return_headers[] = $sighead;
		}
		return $return_headers;
	}



	static function sign($request,$head,$prvkey,$alg = 'sha256') {

		$ret = [];

		$headers = '';
		$fields  = '';
		if($request) {
			$headers = '(request-target)' . ': ' . trim($request) . "\n";
			$fields = '(request-target)';
		}			

		if(head) {
			foreach($head as $k => $v) {
				$headers .= strtolower($k) . ': ' . trim($v) . "\n";
				if($fields)
					$fields .= ' ';
				$fields .= strtolower($k);
			}
			// strip the trailing linefeed
			$headers = rtrim($headers,"\n");
		}

		$sig = base64_encode(rsa_sign($headers,$prvkey,$alg)); 		

		$ret['headers']   = $fields;
		$ret['signature'] = $sig;
	
		return $ret;
	}

	static function parse_sigheader($header) {
		$ret = [];
		$matches = [];
		if(preg_match('/keyId="(.*?)"/ism',$header,$matches))
			$ret['keyId'] = $matches[1];
		if(preg_match('/algorithm="(.*?)"/ism',$header,$matches))
			$ret['algorithm'] = $matches[1];
		if(preg_match('/headers="(.*?)"/ism',$header,$matches))
			$ret['headers'] = explode(' ', $matches[1]);
		if(preg_match('/signature="(.*?)"/ism',$header,$matches))
			$ret['signature'] = base64_decode(preg_replace('/\s+/','',$matches[1]));

		if(($ret['signature']) && ($ret['algorithm']) && (! $ret['headers']))
			$ret['headers'] = [ 'date' ];

 		return $ret;
	}


}


