<?php
namespace Zotlabs\Module;


class Zfinger extends \Zotlabs\Web\Controller {

	function init() {
	
		require_once('include/zot.php');
		require_once('include/crypto.php');
	
		$x = zotinfo($_REQUEST);

		if($x && $x['guid'] && $x['guid_sig']) {
			$chan_hash = make_xchan_hash($x['guid'],$x['guid_sig']);
			if($chan_hash) {
				$chan = channelx_by_hash($chan_hash);
			}
		}

		$headers = [];
		$headers['Content-Type'] = 'application/json' ;
		$ret = json_encode($x);

		if($chan) {
			$hash = \Zotlabs\Web\HTTPSig::generate_digest($ret,false);
			$headers['Digest'] = 'SHA-256=' . $hash;  
			\Zotlabs\Web\HTTPSig::create_sig('',$headers,$chan['channel_prvkey'],
				'acct:' . $chan['channel_address'] . '@' . \App::get_hostname(),true);
		}
		else {
			foreach($headers as $k => $v) {
				header($k . ': ' . $v);
			}
		}

		echo $ret;
		killme();



		json_return_and_die($x);
	
	}
	
}
