<?php
namespace Zotlabs\Module;


class Rmagic extends \Zotlabs\Web\Controller {

	function init() {
	
		if(local_channel())
			goaway(z_root());
	
		$me = get_my_address();
		if($me) {
			$r = q("select hubloc_url from hubloc where hubloc_addr = '%s' limit 1",
				dbesc($me)
			);		
			if($r) {	
				if($r[0]['hubloc_url'] === z_root())
					goaway(z_root() . '/login');
				$dest = z_root() . '/' . str_replace('zid=','zid_=',\App::$query_string);
				goaway($r[0]['hubloc_url'] . '/magic' . '?f=&dest=' . $dest);
			}
		}
	}
	
	function post() {
	
		$address = trim($_REQUEST['address']);
	
		if(strpos($address,'@') === false) {
			$arr = array('address' => $address);
			call_hooks('reverse_magic_auth', $arr);		
	
			// if they're still here...
			notice( t('Authentication failed.') . EOL);		
			return;
		}
		else {
	
			// Presumed Red identity. Perform reverse magic auth
	
			if(strpos($address,'@') === false) {
				notice('Invalid address.');
				return;
			}
	
			$r = null;
			if($address) {
				$r = q("select hubloc_url from hubloc where hubloc_addr = '%s' limit 1",
					dbesc($address)
				);		
			}
			if($r) {
				$url = $r[0]['hubloc_url'];
			}
			else {
				$url = 'https://' . substr($address,strpos($address,'@')+1);
			}	
	
			if($url) {	
				if($_SESSION['return_url']) 
					$dest = urlencode(z_root() . '/' . str_replace('zid=','zid_=',$_SESSION['return_url']));
				else
					$dest = urlencode(z_root() . '/' . str_replace('zid=','zid_=',\App::$query_string));
	
				goaway($url . '/magic' . '?f=&dest=' . $dest);
			}
		}
	}
	
	
	function get() {
	
		$o = replace_macros(get_markup_template('rmagic.tpl'),array(
			'$title' => t('Remote Authentication'),
			'$desc' => t('Enter your channel address (e.g. channel@example.com)'),
			'$submit' => t('Authenticate')
		));
		return $o;
	
	}
}
