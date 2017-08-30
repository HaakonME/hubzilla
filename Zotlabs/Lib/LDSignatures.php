<?php

namespace Zotlabs\Lib;

require_once('library/jsonld/jsonld.php');

class LDSignatures {


	static function verify($data,$pubkey) {

		$ohash = self::hash(self::signable_options($data['signature']));
		$dhash = self::hash(self::signable_data($data));

		return rsa_verify($ohash . $dhash,base64_decode($data['signature']['signatureValue']), $pubkey);
	}

	static function dopplesign(&$data,$channel) {
		$data['magicEnv'] = self::salmon_sign($data,$channel);
		return self::sign($data,$channel);
	}

	static function sign($data,$channel) {
		$options = [
			'type' => 'RsaSignature2017',
			'nonce' => random_string(64),
			'creator' => z_root() . '/channel/' . $channel['channel_address'] . '/public_key_pem',
			'created' => datetime_convert('UTC','UTC', 'now', 'Y-m-d\Th:i:s\Z')
		];

		$ohash = self::hash(self::signable_options($options));
		$dhash = self::hash(self::signable_data($data));
		$options['signatureValue'] = base64_encode(rsa_sign($ohash . $dhash,$channel['channel_prvkey']));

		$signed = array_merge([
			'@context' => [ 'https://www.w3.org/ns/activitystreams', 'https://w3id.org/security/v1' ],
			],$options);

		return $signed;
	}


	static function signable_data($data) {

		$newdata = [];
		if($data) {
			foreach($data as $k => $v) {
				if(! in_array($k,[ 'signature' ])) {
					$newopts[$k] = $v;
				}
			}
		}
		return json_encode($newdata,JSON_UNESCAPED_SLASHES);
	}


	static function signable_options($options) {

		$newopts = [ '@context' => 'https://w3id.org/identity/v1' ];
		if($options) {
			foreach($options as $k => $v) {
				if(! in_array($k,[ 'type','id','signatureValue' ])) {
					$newopts[$k] = $v;
				}
			}
		}
		return json_encode($newopts,JSON_UNESCAPED_SLASHES);
	}

	static function hash($obj) {
		return hash('sha256',self::normalise($obj));
	}

	static function normalise($data) {
		if(is_string($data)) {
			$data = json_decode($data);
		}

		if(! is_object($data))
			return '';

		return jsonld_normalize($data,[ 'algorithm' => 'URDNA2015', 'format' => 'application/nquads' ]);
	}

	static function salmon_sign($data,$channel) {

		$arr       = $data;
		$data      = json_encode($data,JSON_UNESCAPED_SLASHES);
		$data      = base64url_encode($data, false); // do not strip padding
		$data_type = 'application/activity+json';
		$encoding  = 'base64url';
		$algorithm = 'RSA-SHA256';
		$keyhash   = base64url_encode(z_root() . '/channel/' . $channel['channel_address']);

		$data = str_replace(array(" ","\t","\r","\n"),array("","","",""),$data);

		// precomputed base64url encoding of data_type, encoding, algorithm concatenated with periods

		$precomputed = '.' . base64url_encode($data_type,false) . '.YmFzZTY0dXJs.UlNBLVNIQTI1Ng==';

		$signature  = base64url_encode(rsa_sign($data . $precomputed,$channel['channel_prvkey']));

		return ([
			'id'          => $arr['id'],
			'meData'      => $data,
			'meDataType'  => $data_type,
			'meEncoding'  => $encoding,
			'meAlgorithm' => $algorithm,
			'meCreator'   => z_root() . '/channel/' . $channel['channel_address'] . '/public_key_pem',
			'meSignatureValue' => $signature
		]);

	}



}