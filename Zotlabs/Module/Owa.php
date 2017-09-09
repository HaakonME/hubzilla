<?php

namespace Zotlabs\Module;

/**
 * OpenWebAuth verifier and token generator
 * See https://macgirvin.com/wiki/mike/OpenWebAuth/Home
 * Requests to this endpoint should be signed using HTTP Signatures
 * using the 'Authorization: Signature' authentication method
 * If the signature verifies a token is returned. 
 *
 * This token may be exchanged for an authenticated cookie. 
 */

class Owa extends \Zotlabs\Web\Controller {

	function init() {

		$ret = [ 'success' => false ];

		foreach([ 'REDIRECT_REMOTE_USER', 'HTTP_AUTHORIZATION' ] as $head) {
			if(array_key_exists($head,$_SERVER) && substr(trim($_SERVER[$head]),0,9) === 'Signature') {
				if($head !== 'HTTP_AUTHORIZATION') {
					$_SERVER['HTTP_AUTHORIZATION'] = $_SERVER[$head];
					continue;
				}

				$sigblock = \Zotlabs\Web\HTTPSig::parse_sigheader($_SERVER[$head]);
				if($sigblock) {
					$keyId = $sigblock['keyId'];

					if($keyId) {
						$r = q("select * from hubloc left join xchan on hubloc_hash = xchan_hash 
							where hubloc_addr = '%s' limit 1",
							dbesc(str_replace('acct:','',$keyId))
						);
						if($r) {
							$hubloc = $r[0];
							$verified = \Zotlabs\Web\HTTPSig::verify('',$hubloc['xchan_pubkey']);	
							if($verified && $verified['header_signed'] && $verified['header_valid']) {
								$ret['success'] = true;
								$token = random_string(32);
								\Zotlabs\Zot\Verify::create('owt',0,$token,$r[0]['hubloc_addr']);
								$ret['token'] = $token;
							}
						}
					}
				}
			}
		}
		json_return_and_die($ret,'application/x-zot+json');
	}
}
