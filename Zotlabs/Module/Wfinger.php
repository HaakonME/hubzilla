<?php
namespace Zotlabs\Module;

require_once('include/zot.php');


class Wfinger extends \Zotlabs\Web\Controller {

	function init() {
	
		session_write_close();

		$result = array();
	
		$scheme = '';
	
		if(x($_SERVER,'HTTPS') && $_SERVER['HTTPS'])
			$scheme = 'https';
		elseif(x($_SERVER,'SERVER_PORT') && (intval($_SERVER['SERVER_PORT']) == 443))
			$scheme = 'https';
	
		$zot = intval($_REQUEST['zot']);
	
		if(($scheme !== 'https') && (! $zot)) {
			header($_SERVER["SERVER_PROTOCOL"] . ' ' . 500 . ' ' . 'Webfinger requires HTTPS');
			killme();
		}
	
	
		$resource = $_REQUEST['resource'];
		logger('webfinger: ' . $resource,LOGGER_DEBUG);
	

		$root_resource  = false;
		$pchan = false;

		if(strcasecmp(rtrim($resource,'/'),z_root()) === 0)
			$root_resource = true;

		$r = null;
	
		if(($resource) && (! $root_resource)) {
	
			if(strpos($resource,'acct:') === 0) {
				$channel = str_replace('acct:','',$resource);
				if(strpos($channel,'@') !== false) {
					$host = substr($channel,strpos($channel,'@')+1);

					// If the webfinger address points off site, redirect to the correct site

					if(strcasecmp($host,\App::get_hostname())) {
						goaway('https://' . $host . '/.well-known/webfinger?f=&resource=' . $resource . (($zot) ? '&zot=' . $zot : ''));
					}
					$channel = substr($channel,0,strpos($channel,'@'));
				}		
			}
			if(strpos($resource,'http') === 0) {
				$channel = str_replace('~','',basename($resource));
			}
	
			if(substr($channel,0,1) === '[' ) {
				$channel = substr($channel,1);
				$channel = substr($channel,0,-1);
				$pchan = true;
				$r = q("select * from pchan left join xchan on pchan_hash = xchan_hash 
					where pchan_guid = '%s' limit 1",
					dbesc($channel)
				);
				if($r) {
					$r[0] = pchan_to_chan($r[0]);
				}
			}
			else {	
				$r = q("select * from channel left join xchan on channel_hash = xchan_hash 
					where channel_address = '%s' limit 1",
					dbesc($channel)
				);
			}
		}
	
		header('Access-Control-Allow-Origin: *');
	

		if($root_resource) {
			$result['subject'] = $resource;
			$result['properties'] = [
					'https://w3id.org/security/v1#publicKeyPem' => get_config('system','pubkey')
			];
			$result['links'] = [
				[
					'rel'  => 'http://purl.org/openwebauth/v1',
					'type' => 'application/x-zot+json',
					'href' => z_root() . '/owa',
				],
			];



	
		}

		if($resource && $r) {
	
			$h = q("select hubloc_addr from hubloc where hubloc_hash = '%s' and hubloc_deleted = 0",
				dbesc($r[0]['channel_hash'])
			);
	
			$result['subject'] = $resource;
	
			$aliases = array(
				z_root() . (($pchan) ? '/pchan/' : '/channel/') . $r[0]['channel_address'],
				z_root() . '/~' . $r[0]['channel_address']
			);
	
			if($h) {
				foreach($h as $hh) {
					$aliases[] = 'acct:' . $hh['hubloc_addr'];
				}
			}
	
			$result['aliases'] = [];
	
			$result['properties'] = [
					'http://webfinger.net/ns/name'   => $r[0]['channel_name'],
					'http://xmlns.com/foaf/0.1/name' => $r[0]['channel_name'],
					'https://w3id.org/security/v1#publicKeyPem' => $r[0]['xchan_pubkey']
			];
	
			foreach($aliases as $alias) 
				if($alias != $resource)
					$result['aliases'][] = $alias;
	

			if($pchan) {
				$result['links'] = [
	
					[
						'rel'  => 'http://webfinger.net/rel/avatar',
						'type' => $r[0]['xchan_photo_mimetype'],
						'href' => $r[0]['xchan_photo_l']	
					],
	
					[
						'rel'  => 'http://webfinger.net/rel/profile-page',
						'href' => $r[0]['xchan_url'],
					],

					[
						'rel'  => 'magic-public-key',
						'href' => 'data:application/magic-public-key,' . salmon_key($r[0]['channel_pubkey']),
					]

				];


			}
			else {

				$result['links'] = [
	
					[
						'rel'  => 'http://webfinger.net/rel/avatar',
						'type' => $r[0]['xchan_photo_mimetype'],
						'href' => $r[0]['xchan_photo_l']	
					],
	
					[
						'rel'  => 'http://microformats.org/profile/hcard',
						'type' => 'text/html',
						'href' => z_root() . '/hcard/' . $r[0]['channel_address']	
					],


					[
						'rel'  => 'http://webfinger.net/rel/profile-page',
						'href' => z_root() . '/profile/' . $r[0]['channel_address'],
					],
	
					[
						'rel'  => 'http://schemas.google.com/g/2010#updates-from', 
						'type' => 'application/atom+xml', 
						'href' => z_root() . '/ofeed/'  . $r[0]['channel_address']
					],

					[
						'rel'  => 'http://webfinger.net/rel/blog',
						'href' => z_root() . '/channel/' . $r[0]['channel_address'],
					],
	
					[
						'rel'      => 'http://ostatus.org/schema/1.0/subscribe',
						'template' => z_root() . '/follow?f=&url={uri}',
					],
	
					[
						'rel'  => 'http://purl.org/zot/protocol',
						'href' => z_root() . '/.well-known/zot-info' . '?address=' . $r[0]['xchan_addr'],
					],

					[
						'rel'  => 'http://purl.org/openwebauth/v1',
						'type' => 'application/x-zot+json',
						'href' => z_root() . '/owa',
					],

	
					[
						'rel'  => 'magic-public-key',
						'href' => 'data:application/magic-public-key,' . salmon_key($r[0]['channel_pubkey']),
					]
				];
			}

			if($zot) {
				// get a zotinfo packet and return it with webfinger
				$result['zot'] = zotinfo( [ 'address' => $r[0]['xchan_addr'] ]);
			}
		}

		if(! $result) {
			header($_SERVER["SERVER_PROTOCOL"] . ' ' . 400 . ' ' . 'Bad Request');
			killme();
		}
	
		$arr = [ 'channel' => $r[0], 'pchan' => $pchan, 'request' => $_REQUEST, 'result' => $result ];
		call_hooks('webfinger',$arr);


		json_return_and_die($arr['result'],'application/jrd+json');
	
	}
	
}
