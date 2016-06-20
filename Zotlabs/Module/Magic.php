<?php
namespace Zotlabs\Module;

@require_once('include/zot.php');


class Magic extends \Zotlabs\Web\Controller {

	function init() {
	
		$ret = array('success' => false, 'url' => '', 'message' => '');
		logger('mod_magic: invoked', LOGGER_DEBUG);
	
		logger('mod_magic: args: ' . print_r($_REQUEST,true),LOGGER_DATA);
	
		$addr = ((x($_REQUEST,'addr')) ? $_REQUEST['addr'] : '');
		$dest = ((x($_REQUEST,'dest')) ? $_REQUEST['dest'] : '');
		$test = ((x($_REQUEST,'test')) ? intval($_REQUEST['test']) : 0);
		$rev  = ((x($_REQUEST,'rev'))  ? intval($_REQUEST['rev'])  : 0);
		$delegate = ((x($_REQUEST,'delegate')) ? $_REQUEST['delegate']  : '');
	
		$parsed = parse_url($dest);
		if(! $parsed) {
			if($test) {
				$ret['message'] .= 'could not parse ' . $dest . EOL;
				return($ret);
			}
			goaway($dest);
		}
	
		$basepath = $parsed['scheme'] . '://' . $parsed['host'] . (($parsed['port']) ? ':' . $parsed['port'] : ''); 
	
		$x = q("select * from hubloc where hubloc_url = '%s' order by hubloc_connected desc limit 1",
			dbesc($basepath)
		);
		
		if(! $x) {
	
			/*
			 * We have no records for, or prior communications with this hub. 
			 * If an address was supplied, let's finger them to create a hub record. 
			 * Otherwise we'll use the special address '[system]' which will return
			 * either a system channel or the first available normal channel. We don't
			 * really care about what channel is returned - we need the hub information 
			 * from that response so that we can create signed auth packets destined 
			 * for that hub.
			 *
			 */
	
			$j = \Zotlabs\Zot\Finger::run((($addr) ? $addr : '[system]@' . $parsed['host']),null);
			if($j['success']) {
				import_xchan($j);
	
				// Now try again
	
				$x = q("select * from hubloc where hubloc_url = '%s' order by hubloc_connected desc limit 1",
					dbesc($basepath)
				);
			}
		}
	
		if(! $x) {
			if($rev)
				goaway($dest);
			else {
				logger('mod_magic: no channels found for requested hub.' . print_r($_REQUEST,true));
				if($test) {
					$ret['message'] .= 'This site has no previous connections with ' . $basepath . EOL;
					return $ret;
				} 
				notice( t('Hub not found.') . EOL);
				return;
			}
		}
	
		// This is ready-made for a plugin that provides a blacklist or "ask me" before blindly authenticating. 
		// By default, we'll proceed without asking.
	
		$arr = array(
			'channel_id' => local_channel(),
			'xchan' => $x[0],
			'destination' => $dest, 
			'proceed' => true
		);
	
		call_hooks('magic_auth',$arr);
		$dest = $arr['destination'];
		if(! $arr['proceed']) {
			if($test) {
				$ret['message'] .= 'cancelled by plugin.' . EOL;
				return $ret;
			}
			goaway($dest);
		}
	
		if((get_observer_hash()) && ($x[0]['hubloc_url'] === z_root())) {
			// We are already authenticated on this site and a registered observer.
			// Just redirect.
			if($test) {
				$ret['success'] = true;
				$ret['message'] .= 'Local site - you are already authenticated.' . EOL;
				return $ret;
			}
	
			$delegation_success = false;
			if($delegate) {
				$r = q("select * from channel left join hubloc on channel_hash = hubloc_hash where hubloc_addr = '%s' limit 1",
					dbesc($delegate)
				);
	
				if($r && intval($r[0]['channel_id'])) {
					$allowed = perm_is_allowed($r[0]['channel_id'],get_observer_hash(),'delegate');
					if($allowed) {
						$_SESSION['delegate_channel'] = $r[0]['channel_id'];
						$_SESSION['delegate'] = get_observer_hash();
						$_SESSION['account_id'] = intval($r[0]['channel_account_id']);
						change_channel($r[0]['channel_id']);
	
						$delegation_success = true;
					}
				}
			}
				
	
	
			// FIXME: check and honour local delegation
	
	
			goaway($dest);
		}
	
		if(local_channel()) {
			$channel = \App::get_channel();
	
			$token = random_string();
			$token_sig = base64url_encode(rsa_sign($token,$channel['channel_prvkey']));
	 
			$channel['token'] = $token;
			$channel['token_sig'] = $token_sig;
	
			\Zotlabs\Zot\Verify::create('auth',$channel['channel_id'],$token,$x[0]['hubloc_url']);
	
			$target_url = $x[0]['hubloc_callback'] . '/?f=&auth=' . urlencode($channel['channel_address'] . '@' . \App::get_hostname())
				. '&sec=' . $token . '&dest=' . urlencode($dest) . '&version=' . ZOT_REVISION;
	
			if($delegate)
				$target_url .= '&delegate=' . urlencode($delegate);
	
			logger('mod_magic: redirecting to: ' . $target_url, LOGGER_DEBUG); 
	
			if($test) {
				$ret['success'] = true;
				$ret['url'] = $target_url;
				$ret['message'] = 'token ' . $token . ' created for channel ' . $channel['channel_id'] . ' for url ' . $x[0]['hubloc_url'] . EOL;
				return $ret;
			}
	
			goaway($target_url);
				
		}
	
		if($test) {
			$ret['message'] = 'Not authenticated or invalid arguments to mod_magic' . EOL;
			return $ret;
		}
	
		goaway($dest);
	
	}
	
}
