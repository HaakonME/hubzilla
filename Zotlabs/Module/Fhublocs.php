<?php
namespace Zotlabs\Module;

require_once('include/zot.php');
require_once('include/crypto.php');

/* fix missing or damaged hublocs */


class Fhublocs extends \Zotlabs\Web\Controller {

	function get() {
		
		if(! is_site_admin())
			return;
	
		$o = '';
	
		$r = q("select * from channel where channel_removed = 0");
		$sitekey = get_config('system','pubkey');
		
		if($r) {
			foreach($r as $rr) {
				$found = false;
				$primary_address = '';
				$x = zot_get_hublocs($rr['channel_hash']);
				if($x) {
					foreach($x as $xx) {
						if($xx['hubloc_url'] === z_root() && $xx['hubloc_sitekey'] === $sitekey) {
							$found = true;
							break;
						}
					}
					if($found) {
						$o .= 'Hubloc exists for ' . $rr['channel_name'] . EOL;
						continue;
					}	
				}
				$y = q("select xchan_addr from xchan where xchan_hash = '%s' limit 1",
					dbesc($rr['channel_hash'])
				);
				if($y)
					$primary_address = $y[0]['xchan_addr'];
	
				$hub_address = channel_reddress($rr['channel']);
	
			
				$primary = (($hub_address === $primary_address) ? 1 : 0);
				if(! $y)
					$primary = 1;
	
				$m = q("delete from hubloc where hubloc_hash = '%s' and hubloc_url = '%s' ",
					dbesc($rr['channel_hash']),
					dbesc(z_root())
				);
	
				// Create a verified hub location pointing to this site.
	

				$h = hubloc_store_lowlevel(
					[
						'hubloc_guid'     => $rr['channel_guid'],
						'hubloc_guid_sig' => $rr['channel_guid_sig'],
						'hubloc_hash'     => $rr['channel_hash'],
						'hubloc_addr'     => channel_reddress($rr),
						'hubloc_network'  => 'zot',
						'hubloc_primary'  => $primary,
						'hubloc_url'      => z_root(),
						'hubloc_url_sig'  => base64url_encode(rsa_sign(z_root(),$rr['channel_prvkey'])),
						'hubloc_host'     => \App::get_hostname(),
						'hubloc_callback' => z_root() . '/post',
						'hubloc_sitekey'  => $sitekey
					]
				);
	
				if($h)
					$o . 'local hubloc created for ' . $rr['channel_name'] . EOL;
				else
					$o .= 'DB update failed for ' . $rr['channel_name'] . EOL;
	
			}
	
			return $o;
	
		}
	}
}
