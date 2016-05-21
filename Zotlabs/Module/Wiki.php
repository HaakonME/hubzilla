<?php

namespace Zotlabs\Module;/** @file */

use \library\parsedown\Parsedown as Parsedown;

class Wiki extends \Zotlabs\Web\Controller {

	function init() {
		// Determine which channel's wikis to display to the observer
		$which = null;
		if(argc() > 1)
			$which = argv(1); // if the channel name is in the URL, use that
		if(! $which) { // if no channel name was provided, assume the current logged in channel
			if(local_channel()) {
				$channel = \App::get_channel();
				if($channel && $channel['channel_address'])
				$which = $channel['channel_address'];
				goaway(z_root().'/wiki/'.$which);
			}
		}
		if(! $which) {
			notice( t('You must be logged in to see this page.') . EOL );
			return;
		}
	}

	function get() {
		require_once('include/acl_selectors.php');
		if(local_channel()) {
			$channel = \App::get_channel();
		}
		
		// TODO: check observer permissions
		//$ob = \App::get_observer();
		//$observer = get_observer_hash();
		
		// Obtain the default permission settings of the channel
    $channel_acl = array(
            'allow_cid' => $channel['channel_allow_cid'],
            'allow_gid' => $channel['channel_allow_gid'],
            'deny_cid'  => $channel['channel_deny_cid'],
            'deny_gid'  => $channel['channel_deny_gid']
    );
		// Initialize the ACL to the channel default permissions
    $x = array(
        'lockstate' => (($channel['channel_allow_cid'] || $channel['channel_allow_gid'] || $channel['channel_deny_cid'] || $channel['channel_deny_gid']) ? 'lock' : 'unlock'),
        'acl' => populate_acl($channel_acl),
        'bang' => ''
    );
		$o .= replace_macros(get_markup_template('wiki.tpl'),array(
			'$channel' => $channel['channel_address'],
			'$lockstate' => $x['lockstate'],
			'$acl' => $x['acl'],
			'$bang' => $x['bang'],
			'$content' => '# Start your wiki',
			'$wikiName' => array('wikiName', t('Enter the name of your new wiki:'), '', ''),
			'$pageName' => array('pageName', t('Enter the name of the new page:'), '', '')
		));
		head_add_js('library/ace/ace.js');
		return $o;
	}

	function post() {
		
		// TODO: Implement wiki API
		
		// Render mardown-formatted text in HTML
		if((argc() > 2) && (argv(2) === 'preview')) {
			$content = $_POST['content'];
			logger('preview content: ' . $content);
			//require_once('library/parsedown/Parsedown.php');
			$parsedown = new Parsedown();
			$html = $parsedown->text($content);
			json_return_and_die(array('html' => $html, 'success' => true));
		}
		
		// Create a new wiki
		if ((argc() > 3) && (argv(2) === 'create') && (argv(3) === 'wiki')) {
			// Determine if observer has permission to create wiki
			if (local_channel()) {
				$channel = \App::get_channel();
			} else {
				$which = argv(1);
				$channel = get_channel_by_nick($which);
				// Figure out who the page owner is.
				$perms = get_all_perms(intval($channel['channel_id']), get_observer_hash());

				if (!$perms['write_wiki']) {
					notice(t('Permission denied.') . EOL);
					json_return_and_die(array('success' => false));
				}
			}
			$name = escape_tags(urlencode($_REQUEST['name'])); //Get new wiki name
			// Get ACL for permissions
			$acl = new Zotlabs\Access\AccessList($channel);
			$acl->set_from_array($_REQUEST);
			
			$r = wiki_create_wiki($channel, $name, $acl);
			if ($r['success']) {
				json_return_and_die(array('success' => true));
			} else {
				json_return_and_die(array('success' => false));
			}
		}


		json_return_and_die(array('success' => false));
		
		
		
	}
}
