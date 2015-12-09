<?php

namespace Zotlabs\Zot;

class Auth {

	protected $ret;
	protected $test;
	protected $address;
	protected $desturl;
	protected $sec;
	protected $version;
	protected $delegate;
	protected $success;
	protected $delegate_success;

	protected $remote;
	protected $remote_service_class;
	protected $remote_level;
	protected $remote_hub;
	protected $dnt;

	function __construct($req) {

		logger('construct');

		$this->ret      = array('success' => false);
		$this->success  = false;
		$this->test     = ((array_key_exists('test',$req)) ? intval($req['test']) : 0);
		$this->address  = $req['auth'];
		$this->desturl  = $req['dest'];
		$this->sec      = $req['sec'];
		$this->version  = $req['version'];
		$this->delegate = $req['delegate'];

		$c = get_sys_channel();
		if(! $c) {
			logger('unable to obtain response (sys) channel');
			reply_die('no local channels found.');
		}

		$x = $this->GetHublocs($this->address);

		logger('hublocs');

		foreach($x as $xx) {

		logger('verify');

			if($this->Verify($c,$xx))
				break;
		}

		/**
		 * @FIXME we really want to save the return_url in the session before we
		 * visit rmagic. This does however prevent a recursion if you visit
		 * rmagic directly, as it would otherwise send you back here again.
		 * But z_root() probably isn't where you really want to go.
		 */

		if(strstr($this->desturl,z_root() . '/rmagic'))
			goaway(z_root());

		$this->reply_die();	

	}

	function GetHublocs($address) {

	   // Try and find a hubloc for the person attempting to auth
		$x = q("select * from hubloc left join xchan on xchan_hash = hubloc_hash 
			where hubloc_addr = '%s' order by hubloc_id desc",
			dbesc($address)
		);

		if(! $x) {
			// finger them if they can't be found.
			$ret = zot_finger($address, null);
			if ($ret['success']) {
				$j = json_decode($ret['body'], true);
				if ($j)
					import_xchan($j);
				$x = q("select * from hubloc left join xchan on xchan_hash = hubloc_hash 
					where hubloc_addr = '%s' order by hubloc_id desc",
					dbesc($address)
				);
			}
		}
		if(! $x) {
			logger('mod_zot: auth: unable to finger ' . $address);
			$this->reply_die('no hubloc found for ' . $address . ' and probing failed.');
		}

		return $x;
	}


	function Verify($channel,$hubloc) {
		logger('auth request received from ' . $hubloc['hubloc_addr'] );

		// check credentials and access

		// If they are already authenticated and haven't changed credentials,
		// we can save an expensive network round trip and improve performance.

		$this->remote               = remote_channel();
		$this->remote_service_class = '';
		$this->remote_level         = 0;
		$this->remote_hub           = $hubloc['hubloc_url'];
		$this->dnt                  = 0;

		// Also check that they are coming from the same site as they authenticated with originally.

		$already_authed = ((($this->remote) && ($hubloc['hubloc_hash'] == $this->remote) 
			&& ($hubloc['hubloc_url'] === $_SESSION['remote_hub'])) ? true : false);
			if($this->delegate && $this->delegate !== $_SESSION['delegate_channel'])
				$already_authed = false;

			$j = array();
			
		if(! $already_authed) {

			// Auth packets MUST use ultra top-secret hush-hush mode - e.g. the entire packet is encrypted using the 
			// site private key
			// The actual channel sending the packet ($c[0]) is not important, but this provides a 
			// generic zot packet with a sender which can be verified

			$p = zot_build_packet($channel,$type = 'auth_check', 
				array(array('guid' => $hubloc['hubloc_guid'],'guid_sig' => $hubloc['hubloc_guid_sig'])), 
				$hubloc['hubloc_sitekey'], $this->sec);

			$this->dbg_msg('auth check packet created using sitekey ' . $hubloc['hubloc_sitekey']);
			$this->dbg_msg('packet contents: ' . $p);


			$result = zot_zot($hubloc['hubloc_callback'],$p);

			if(! $result['success']) {
				logger('auth_check callback failed.');
				if($this->test) {
					$this->dbg_msg('auth check request to your site returned .' . print_r($result, true));
					return false;
				}
				return false;
			}
			$j = json_decode($result['body'], true);
			if(! $j) {
				logger('auth_check json data malformed.');
				if($this->test) {
					$this->dbg_msg('json malformed: ' . $result['body']);
					return false;
				}
			}

			$this->dbg_msg('auth check request returned .' . print_r($j, true));

			if ($already_authed || $j['success']) {
				if($j['success']) {
					// legit response, but we do need to check that this wasn't answered by a man-in-middle
					if (! rsa_verify($this->sec . $hubloc['xchan_hash'],base64url_decode($j['confirm']),$hubloc['xchan_pubkey'])) {
						logger('final confirmation failed.');
						if($this->test) {
							$this->dbg_msg('final confirmation failed. ' . $sec . print_r($j,true) . print_r($hubloc,true));
							return false;
						}
						return false;
					}
					if (array_key_exists('service_class',$j))
						$this->remote_service_class = $j['service_class'];
					if (array_key_exists('level',$j))
						$this->remote_level = $j['level'];
					if (array_key_exists('DNT',$j))
						$this->dnt = $j['DNT'];
				}

				// everything is good... maybe

				if(local_channel()) {

					// tell them to logout if they're logged in locally as anything but the target remote account
					// in which case just shut up because they don't need to be doing this at all.

					if (get_app()->channel['channel_hash'] != $hubloc['xchan_hash']) {
						logger('already authenticated locally as somebody else.');
						notice( t('Remote authentication blocked. You are logged into this site locally. Please logout and retry.') . EOL);
						if($this->test) {
							$$this->dbg_msg('already logged in locally with a conflicting identity.');
							return false;;
						}
					}
					return false;;
				}

				// log them in

				if ($this->test) {
					$ret['success'] = true;
					$this->reply_die('Authentication Success!');
				}

				$this->delegate_success = false;
				if($this->delegate) {
					$r = q("select * from channel left join xchan on channel_hash = xchan_hash where xchan_addr = '%s' limit 1",
						dbesc($this->delegate)
					);
					if ($r && intval($r[0]['channel_id'])) {
						$allowed = perm_is_allowed($r[0]['channel_id'],$hubloc['xchan_hash'],'delegate');
						if($allowed) {
							$_SESSION['delegate_channel'] = $r[0]['channel_id'];
							$_SESSION['delegate'] = $hubloc['xchan_hash'];
							$_SESSION['account_id'] = intval($r[0]['channel_account_id']);
							require_once('include/security.php');
							change_channel($r[0]['channel_id']);
							$this->delegate_success = true;
						}
					}
				}

				$_SESSION['authenticated'] = 1;
				if (! $this->delegate_success) {
					$_SESSION['visitor_id'] = $hubloc['xchan_hash'];
					$_SESSION['my_url'] = $hubloc['xchan_url'];
					$_SESSION['my_address'] = $this->address;
					$_SESSION['remote_service_class'] = $this->remote_service_class;
					$_SESSION['remote_level'] = $this->remote_level;
					$_SESSION['remote_hub'] = $this->remote_hub;
					$_SESSION['DNT'] = $this->dnt;
				}

				$arr = array('xchan' => $hubloc, 'url' => $this->desturl, 'session' => $_SESSION);
				call_hooks('magic_auth_success',$arr);
				get_app()->set_observer($hubloc);
				require_once('include/security.php');
				get_app()->set_groups(init_groups_visitor($_SESSION['visitor_id']));
				info(sprintf( t('Welcome %s. Remote authentication successful.'),$hubloc['xchan_name']));
				logger('mod_zot: auth success from ' . $hubloc['xchan_addr']);
				$this->success = true;
				return true;
			}
			else {
				if($this->test) {
					$this->dbg_msg('auth failure. ' . print_r($_REQUEST,true) . print_r($j,true));
					return false;
				}
				logger('magic-auth failure - not authenticated: ' . $hubloc['xchan_addr']);
			}

			if($this->test) {
				$this->dbg_msg('auth failure fallthrough ' . print_r($_REQUEST,true) . print_r($j,true));
				return false;
		   }
		}
	}


	function dbg_msg($msg) {
		if($msg) {
			if(array_key_exists('message',$this->ret))
				$this->ret['message'] .= $msg;
			else
				$this->ret['message'] = $msg;
		}
	}


	function reply_die($msg,$goaway = true) {
		if($msg) {
			if(array_key_exists('message',$this->ret))
				$this->ret['message'] .= $msg;
			else
				$this->ret['message'] = $msg;
		}
		if($this->test)
			json_return_and_die($this->ret);
		if($goaway)
			goaway($this->desturl);
	}

}


/**
 * @brief HTTP POST entry point for Zot.
 *
 * Most access to this endpoint is via the post method.
 * Here we will pick out the magic auth params which arrive as a get request,
 * and the only communications to arrive this way.
 *
 * Magic Auth
 * ==========
 *
 * So-called "magic auth" takes place by a special exchange. On the site where the "channel to be authenticated" lives (e.g. $mysite), 
 * a redirection is made via $mysite/magic to the zot endpoint of the remote site ($remotesite) with special GET parameters.
 *
 * The endpoint is typically  https://$remotesite/post - or whatever was specified as the callback url in prior communications
 * (we will bootstrap an address and fetch a zot info packet if possible where no prior communications exist)
 *
 * Five GET parameters are supplied:
 * * auth => the urlencoded webbie (channel@host.domain) of the channel requesting access
 * * dest => the desired destination URL (urlencoded)
 * * sec  => a random string which is also stored on $mysite for use during the verification phase. 
 * * version => the zot revision
 * * delegate => optional urlencoded webbie of a local channel to invoke delegation rights for
 *
 * When this packet is received, an "auth-check" zot message is sent to $mysite.
 * (e.g. if $_GET['auth'] is foobar@podunk.edu, a zot packet is sent to the podunk.edu zot endpoint, which is typically /post)
 * If no information has been recorded about the requesting identity a zot information packet will be retrieved before
 * continuing.
 *
 * The sender of this packet is an arbitrary/random site channel. The recipients will be a single recipient corresponding
 * to the guid and guid_sig we have associated with the requesting auth identity
 *
 * \code{.json}
 * {
 *   "type":"auth_check",
 *   "sender":{
 *     "guid":"kgVFf_...",
 *     "guid_sig":"PT9-TApz...",
 *     "url":"http:\/\/podunk.edu",
 *     "url_sig":"T8Bp7j..."
 *   },
 *   "recipients":{
 *     {
 *       "guid":"ZHSqb...",
 *       "guid_sig":"JsAAXi..."
 *     }
 *   }
 *   "callback":"\/post",
 *   "version":1,
 *   "secret":"1eaa661",
 *   "secret_sig":"eKV968b1..."
 * }
 * \endcode
 *
 * auth_check messages MUST use encapsulated encryption. This message is sent to the origination site, which checks the 'secret' to see 
 * if it is the same as the 'sec' which it passed originally. It also checks the secret_sig which is the secret signed by the 
 * destination channel's private key and base64url encoded. If everything checks out, a json packet is returned:
 *
 * \code{.json}
 * {
 *   "success":1,
 *   "confirm":"q0Ysovd1u...",
 *   "service_class":(optional)
 *   "level":(optional)
 * }
 * \endcode
 *
 * 'confirm' in this case is the base64url encoded RSA signature of the concatenation of 'secret' with the
 * base64url encoded whirlpool hash of the requestor's guid and guid_sig; signed with the source channel private key. 
 * This prevents a man-in-the-middle from inserting a rogue success packet. Upon receipt and successful 
 * verification of this packet, the destination site will redirect to the original destination URL and indicate a successful remote login. 
 * Service_class can be used by cooperating sites to provide different access rights based on account rights and subscription plans. It is 
 * a string whose contents are not defined by protocol. Example: "basic" or "gold".
 *
 * @param[in,out] App &$a
 */
