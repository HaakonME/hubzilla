<?php
namespace Zotlabs\Module; /** @file */

require_once('include/acl_selectors.php');
require_once('include/crypto.php');
require_once('include/items.php');
require_once('include/taxonomy.php');
require_once('include/conversation.php');
require_once('include/zot.php');

/**
 * remote post
 * 
 * https://yoursite/rpost?f=&title=&body=&remote_return=
 *
 * This can be called via either GET or POST, use POST for long body content as suhosin often limits GET parameter length
 *
 * f= placeholder, often required
 * title= Title of post
 * body= Body of post
 * url= URL which will be parsed and the results appended to the body
 * source= Source application
 * remote_return= absolute URL to return after posting is finished
 * type= choices are 'html' or 'bbcode', default is 'bbcode'
 *
 */




class Rpost extends \Zotlabs\Web\Controller {

	function get() {
	
		$o = '';
	
		if(! local_channel()) {
			if(remote_channel()) {
				// redirect to your own site.
				// We can only do this with a GET request so you'll need to keep the text short or risk getting truncated
				// by the wretched beast called 'suhosin'. All the browsers now allow long GET requests, but suhosin
				// blocks them.
	
				$url = get_rpost_path(\App::get_observer());
				// make sure we're not looping to our own hub
				if(($url) && (! stristr($url, \App::get_hostname()))) {
					foreach($_REQUEST as $key => $arg) {
						$url .= '&' . $key . '=' . $arg;
					}
					goaway($url);
				}
			}
	
			// The login procedure is going to bugger our $_REQUEST variables
			// so save them in the session.
	
			if(array_key_exists('body',$_REQUEST)) {
				$_SESSION['rpost'] = $_REQUEST;
			}
			return login();
		}
	
		// If we have saved rpost session variables, but nothing in the current $_REQUEST, recover the saved variables
	
		if((! array_key_exists('body',$_REQUEST)) && (array_key_exists('rpost',$_SESSION))) {
			$_REQUEST = $_SESSION['rpost'];
			unset($_SESSION['rpost']);
		}
	
		if(array_key_exists('channel',$_REQUEST)) {
			$r = q("select channel_id from channel where channel_account_id = %d and channel_address = '%s' limit 1",
				intval(get_account_id()),
				dbesc($_REQUEST['channel'])
			);
			if($r) {
				require_once('include/security.php');
				$change = change_channel($r[0]['channel_id']);
			}
		}
	
		if($_REQUEST['remote_return']) {
			$_SESSION['remote_return'] = $_REQUEST['remote_return'];
		}
		if(argc() > 1 && argv(1) === 'return') {
			if($_SESSION['remote_return'])
				goaway($_SESSION['remote_return']);
			goaway(z_root() . '/network');
		}
	
		$plaintext = true;
	//	if(feature_enabled(local_channel(),'richtext'))
	//		$plaintext = false;
	
		if(array_key_exists('type', $_REQUEST) && $_REQUEST['type'] === 'html') {
			require_once('include/html2bbcode.php');
			$_REQUEST['body'] = html2bbcode($_REQUEST['body']); 
		}
	
		$channel = \App::get_channel();
	
	
		$acl = new \Zotlabs\Access\AccessList($channel);
	
		$channel_acl = $acl->get();
	
		if($_REQUEST['url']) {
			$x = z_fetch_url(z_root() . '/linkinfo?f=&url=' . urlencode($_REQUEST['url']));
			if($x['success'])
				$_REQUEST['body'] = $_REQUEST['body'] . $x['body'];
		}
	
		$x = array(
			'is_owner' => true,
			'allow_location' => ((intval(get_pconfig($channel['channel_id'],'system','use_browser_location'))) ? '1' : ''),
			'default_location' => $channel['channel_location'],
			'nickname' => $channel['channel_address'],
			'lockstate' => (($acl->is_private()) ? 'lock' : 'unlock'),
			'acl' => populate_acl($channel_acl, true, \Zotlabs\Lib\PermissionDescription::fromGlobalPermission('view_stream'), get_post_aclDialogDescription(), 'acl_dialog_post'),
			'permissions' => $channel_acl,
			'bang' => '',
			'visitor' => true,
			'profile_uid' => local_channel(),
			'title' => $_REQUEST['title'],
			'body' => $_REQUEST['body'],
			'attachment' => $_REQUEST['attachment'],
			'source' => ((x($_REQUEST,'source')) ? strip_tags($_REQUEST['source']) : ''),
			'return_path' => 'rpost/return',
			'bbco_autocomplete' => 'bbcode',
			'editor_autocomplete'=> true,
			'bbcode' => true
		);
	
		$editor = status_editor($a,$x);
	
		$o .= replace_macros(get_markup_template('edpost_head.tpl'), array(
			'$title' => t('Edit post'),
			'$editor' => $editor
		));
	
		return $o;
	
	}
	
	
	
}
