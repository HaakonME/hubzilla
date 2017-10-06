<?php

namespace Zotlabs\Lib;


class JSalmon {

	static function sign($data,$key_id,$key) {

		$arr       = $data;
		$data      = json_encode($data,JSON_UNESCAPED_SLASHES);
		$data      = base64url_encode($data, false); // do not strip padding
		$data_type = 'application/x-zot+json';
		$encoding  = 'base64url';
		$algorithm = 'RSA-SHA256';

		$data = preg_replace('/\s+/','',$data);

		// precomputed base64url encoding of data_type, encoding, algorithm concatenated with periods

		$precomputed = '.' . base64url_encode($data_type,false) . '.YmFzZTY0dXJs.UlNBLVNIQTI1Ng==';

		$signature  = base64url_encode(rsa_sign($data . $precomputed, $key), false);

		return ([
			'signed'    => true,
			'data'      => $data,
			'data_type' => $data_type,
			'encoding'  => $encoding,
			'alg'       => $algorithm,
			'sigs'      => [
				'value'  => $signature,
				'key_id' => base64url_encode($key_id)
			]
		]);

	}
}