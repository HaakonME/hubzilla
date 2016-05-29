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
			goaway('/login');
		}
	}

	function get() {
		require_once('include/wiki.php');
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

		$resource_id = '';
		$pagename = '';
		
		// GET https://hubzilla.hub/argv(0)/argv(1)/argv(2)/argv(3)/argv(4)/...
		if(argc() > 2) {
			// GET /wiki/channel/wiki
			// Check if wiki exists andr redirect if it does not
			$channel = get_channel_by_nick(argv(1));
			$w = wiki_exists_by_name($channel['channel_id'], argv(2));
			if(!$w['id']) {
				goaway('/'.argv(0).'/'.argv(1));
			} else {
				$resource_id = $w['resource_id'];
			}
		}
		
		if(argc()<3) {
			// GET /wiki/channel
			$wikiheader = t('Wiki Sandbox');
			$content = '"# Wiki Sandbox\n\nContent you **edit** and **preview** here *will not be saved*."';
			$hide_editor = false;
			$showPageControls = false;
		} elseif (argc()<4) {
			// GET /wiki/channel/wiki
			// No page was specified, so redirect to Home.md
			goaway('/'.argv(0).'/'.argv(1).'/'.argv(2).'/Home.md');
			$wikiheader = rawurldecode(argv(2)); // show wiki name
			$content = '""';
			$hide_editor = true;	
			$showPageControls = true;
		} elseif (argc()<5) {
			// GET /wiki/channel/wiki/page
			$pagename = argv(3);
			$wikiheader = rawurldecode(argv(2)) . ': ' . rawurldecode($pagename);	// show wiki name and page			
			$p = wiki_get_page_content(array('wiki_resource_id' => $resource_id, 'page' => $pagename));
			if(!$p['success']) {
				logger('Error getting page content');
				$content = 'Error retrieving page content. Try again.';
			}
			$content = $p['content'];
			$hide_editor = false;
			$showPageControls = true;
		}
		//$parsedown = new Parsedown();
		//$renderedContent = $parsedown->text(json_decode($content));
		require_once('library/markdown.php');
		$renderedContent = Markdown(json_decode($content));
		
		$o .= replace_macros(get_markup_template('wiki.tpl'),array(
			'$wikiheader' => $wikiheader,
			'$hideEditor' => $hide_editor,
			'$showPageControls' => $showPageControls,
			'$channel' => $channel['channel_address'],
			'$resource_id' => $resource_id,
			'$page' => $pagename,
			'$lockstate' => $x['lockstate'],
			'$acl' => $x['acl'],
			'$bang' => $x['bang'],
			'$content' => $content,
			'$renderedContent' => $renderedContent,
			'$wikiName' => array('wikiName', t('Enter the name of your new wiki:'), '', ''),
			'$pageName' => array('pageName', t('Enter the name of the new page:'), '', '')
		));
		head_add_js('library/ace/ace.js');
		return $o;
	}

	function post() {
		require_once('include/wiki.php');
		
		// Render mardown-formatted text in HTML
		if((argc() > 2) && (argv(2) === 'preview')) {
			$content = $_POST['content'];
			//$parsedown = new Parsedown();
			//$html = $parsedown->text($content);
			require_once('library/markdown.php');
			$html = Markdown($content);
			json_return_and_die(array('html' => $html, 'success' => true));
		}
		
		// Check if specified wiki exists and redirect if not
		if((argc() > 2)) {
			$wikiname = argv(2);
			// TODO: Check if specified wiki exists and redirect if not
		}
		
		// Create a new wiki
		if ((argc() > 3) && (argv(2) === 'create') && (argv(3) === 'wiki')) {
			$which = argv(1);
			// Determine if observer has permission to create wiki
			$observer_hash = get_observer_hash();
			if (local_channel()) {
				$channel = \App::get_channel();
			} else {
				$channel = get_channel_by_nick($which);
				// Figure out who the page owner is.
				$perms = get_all_perms(intval($channel['channel_id']), $observer_hash);
				// TODO: Create a new permission setting for wiki analogous to webpages. Until
				// then, use webpage permissions
				if (!$perms['write_pages']) {
					notice(t('Permission denied.') . EOL);
					goaway('/'.argv(0).'/'.argv(1).'/'.argv(2));
				}
			}
			$name = escape_tags(urlencode($_POST['wikiName'])); //Get new wiki name
			if($name === '') {				
				notice('Error creating wiki. Invalid name.');
				goaway('/wiki');
			}
			// Get ACL for permissions
			$acl = new \Zotlabs\Access\AccessList($channel);
			$acl->set_from_array($_POST);
			$r = wiki_create_wiki($channel, $observer_hash, $name, $acl);
			if ($r['success']) {
				$homePage = wiki_create_page('Home.md', $r['item']['resource_id']);
				if(!$homePage['success']) {
					notice('Wiki created, but error creating Home page.');
					goaway('/wiki/'.$which.'/'.$name);
				}
				goaway('/wiki/'.$which.'/'.$name.'/Home.md');
			} else {
				notice('Error creating wiki');
				goaway('/wiki');
			}
		}

		// Delete a wiki
		if ((argc() > 3) && (argv(2) === 'delete') && (argv(3) === 'wiki')) {
			$which = argv(1);
			// Determine if observer has permission to create wiki
			if (local_channel()) {
				$channel = \App::get_channel();
			} else {
				$channel = get_channel_by_nick($which);
				$observer_hash = get_observer_hash();
				// Figure out who the page owner is.
				$perms = get_all_perms(intval($channel['channel_id']), $observer_hash);
				// TODO: Create a new permission setting for wiki analogous to webpages. Until
				// then, use webpage permissions
				if (!$perms['write_pages']) {
					logger('Wiki delete permission denied.' . EOL);
					json_return_and_die(array('success' => false));
				}
			}
			$resource_id = $_POST['resource_id']; 
			$deleted = wiki_delete_wiki($resource_id);
			if ($deleted['success']) {
				json_return_and_die(array('success' => true));
			} else {
				logger('Error deleting wiki: ' . $resource_id);
				json_return_and_die(array('success' => false));
			}
		}

		// Create a page
		if ((argc() === 4) && (argv(2) === 'create') && (argv(3) === 'page')) {
			$which = argv(1);
			$resource_id = $_POST['resource_id']; 
			// Determine if observer has permission to create wiki
			if (local_channel()) {
				$channel = \App::get_channel();
			} else {
				$channel = get_channel_by_nick($which);
				$observer_hash = get_observer_hash();
				// Figure out who the page owner is.
				$perms = get_all_perms(intval($channel['channel_id']), $observer_hash);
				// TODO: Create a new permission setting for wiki analogous to webpages. Until
				// then, use webpage permissions
				if (!$perms['write_pages']) {
					logger('Wiki editing permission denied.' . EOL);
					json_return_and_die(array('success' => false));
				}
				$perms = wiki_get_permissions($resource_id, intval($channel['channel_id']), $observer_hash);
				if(!$perms['write']) {
					logger('Wiki write permission denied. Read only.' . EOL);
					json_return_and_die(array('success' => false));					
				}
			}
			$name = escape_tags(urlencode($_POST['name'])); //Get new wiki name
			if($name === '') {				
				json_return_and_die(array('message' => 'Error creating page. Invalid name.', 'success' => false));
			}
			$page = wiki_create_page($name . '.md', $resource_id);
			if ($page['success']) {
				json_return_and_die(array('url' => '/'.argv(0).'/'.argv(1).'/'.$page['wiki'].'/'.$name.'.md', 'success' => true));
			} else {
				logger('Error creating page');
				json_return_and_die(array('message' => 'Error creating page.', 'success' => false));
			}
		}		
		
		// Fetch page list for a wiki
		if ((argc() === 5) && (argv(2) === 'get') && (argv(3) === 'page') && (argv(4) === 'list')) {
			$resource_id = $_POST['resource_id']; // resource_id for wiki in db
			$channel = get_channel_by_nick(argv(1));
			$observer_hash = get_observer_hash();
			$perms = wiki_get_permissions($resource_id, intval($channel['channel_id']), $observer_hash);
			if(!$perms['read']) {
				logger('Wiki read permission denied.' . EOL);
				json_return_and_die(array('pages' => null, 'message' => 'Permission denied.', 'success' => false));					
			}
			$page_list_html = widget_wiki_pages(array(
					'resource_id' => $resource_id, 
					'refresh' => true, 
					'channel' => argv(1)));
			json_return_and_die(array('pages' => $page_list_html, 'message' => '', 'success' => true));					
		}
		
		// Save a page
		if ((argc() === 4) && (argv(2) === 'save') && (argv(3) === 'page')) {
			$which = argv(1);
			$resource_id = $_POST['resource_id']; 
			$pagename = escape_tags(urlencode($_POST['name'])); 
			$content = escape_tags($_POST['content']); //Get new content
			// Determine if observer has permission to create wiki
			if (local_channel()) {
				$channel = \App::get_channel();
			} else {
				$channel = get_channel_by_nick($which);
				$observer_hash = get_observer_hash();
				// Figure out who the page owner is.
				$perms = get_all_perms(intval($channel['channel_id']), $observer_hash);
				// TODO: Create a new permission setting for wiki analogous to webpages. Until
				// then, use webpage permissions
				if (!$perms['write_pages']) {
					logger('Wiki editing permission denied.' . EOL);
					json_return_and_die(array('success' => false));
				}
				$perms = wiki_get_permissions($resource_id, intval($channel['channel_id']), $observer_hash);
				if(!$perms['write']) {
					logger('Wiki write permission denied. Read only.' . EOL);
					json_return_and_die(array('success' => false));					
				}
			}
			$saved = wiki_save_page(array('resource_id' => $resource_id, 'name' => $pagename, 'content' => $content));
			if($saved['success']) {
				$ob = \App::get_observer();
				$commit = wiki_git_commit(array(
						'commit_msg' => 'Updated ' . $pagename, 
						'resource_id' => $resource_id, 
						'observer' => $ob,
						'files' => array($pagename)
						));
				if($commit['success']) {
					json_return_and_die(array('message' => 'Wiki git repo commit made', 'success' => true));
				} else {
					json_return_and_die(array('message' => 'Error making git commit','success' => false));					
				}
			} else {
				json_return_and_die(array('message' => 'Error saving page', 'success' => false));					
			}
		}
		
		//notice('You must be authenticated.');
		json_return_and_die(array('message' => 'You must be authenticated.', 'success' => false));
		
	}
}
