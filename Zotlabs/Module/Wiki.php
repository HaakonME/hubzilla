<?php

namespace Zotlabs\Module;/** @file */

class Wiki extends \Zotlabs\Web\Controller {

	function init() {
		// Determine which channel's wikis to display to the observer
		$nick = null;
		if (argc() > 1)
			$nick = argv(1); // if the channel name is in the URL, use that
		if (!$nick && local_channel()) { // if no channel name was provided, assume the current logged in channel
			$channel = \App::get_channel();
			if ($channel && $channel['channel_address']) {
				$nick = $channel['channel_address'];
				goaway(z_root() . '/wiki/' . $nick);
			}
		}
		if (!$nick) {
			notice(t('You must be logged in to see this page.') . EOL);
			goaway('/login');
		}
	}

	function get() {
		require_once('include/wiki.php');
		require_once('include/acl_selectors.php');
		// TODO: Combine the interface configuration into a unified object
		// Something like $interface = array('new_page_button' => false, 'new_wiki_button' => false, ...)
		$wiki_owner = false;
		$showNewWikiButton = false;
		$pageHistory = array();
		$local_observer = null;
		$resource_id = '';
		$pagename = '';
		
		// init() should have forced the URL to redirect to /wiki/channel so assume argc() > 1
		$nick = argv(1);
		$channel = get_channel_by_nick($nick);  // The channel who owns the wikis being viewed
		if(! $channel) {
			notice('Invalid channel' . EOL);
			goaway('/' . argv(0));
		}
		// Determine if the observer is the channel owner so the ACL dialog can be populated
		if (local_channel() === intval($channel['channel_id'])) {
			$local_observer = \App::get_channel();
			$wiki_owner = true;

			// Obtain the default permission settings of the channel
			$channel_acl = array(
					'allow_cid' => $local_observer['channel_allow_cid'],
					'allow_gid' => $local_observer['channel_allow_gid'],
					'deny_cid' => $local_observer['channel_deny_cid'],
					'deny_gid' => $local_observer['channel_deny_gid']
			);
			// Initialize the ACL to the channel default permissions
			$x = array(
					'lockstate' => (( $local_observer['channel_allow_cid'] || 
														$local_observer['channel_allow_gid'] || 
														$local_observer['channel_deny_cid'] || 
														$local_observer['channel_deny_gid']) 
														? 'lock' : 'unlock'),
					'acl' => populate_acl($channel_acl),
					'bang' => ''
			);
		} else {
			// Not the channel owner 
			$channel_acl = $x = array();
		}

		switch (argc()) {
			case 2:
				// Configure page template
				$wikiheader = t('Wiki Sandbox');
				$content = '"# Wiki Sandbox\n\nContent you **edit** and **preview** here *will not be saved*."';
				$hide_editor = false;
				$showPageControls = false;
				$showNewWikiButton = $wiki_owner;
				$showNewPageButton = false;
				break;
			case 3:
				// /wiki/channel/wiki -> No page was specified, so redirect to Home.md
				goaway('/'.argv(0).'/'.argv(1).'/'.argv(2).'/Home.md');
			case 4:
				// GET /wiki/channel/wiki/page
				// Fetch the wiki info and determine observer permissions
				$wikiname = argv(2);
				$pagename = argv(3);
				$w = wiki_exists_by_name($channel['channel_id'], $wikiname);
				if(!$w['resource_id']) {
					notice('Wiki not found' . EOL);
					goaway('/'.argv(0).'/'.argv(1));
				}				
				$resource_id = $w['resource_id'];
				
				if (!$wiki_owner) {
					// Check for observer permissions
					$observer_hash = get_observer_hash();
					$perms = wiki_get_permissions($resource_id, intval($channel['channel_id']), $observer_hash);
					if(!$perms['read']) {
						notice('Permission denied.' . EOL);
						goaway('/'.argv(0).'/'.argv(1));
					}
				}
				$wikiheader = rawurldecode($wikiname) . ': ' . rawurldecode($pagename);	// show wiki name and page			
				$p = wiki_get_page_content(array('resource_id' => $resource_id, 'page' => $pagename));
				if(!$p['success']) {
					notice('Error retrieving page content' . EOL);
					goaway('/'.argv(0).'/'.argv(1).'/'.argv(2));
				}
				$content = ($p['content'] !== '' ? $p['content'] : '"# New page\n"');
				$hide_editor = false;
				$showPageControls = $wiki_owner;
				$showNewWikiButton = $wiki_owner;
				$showNewPageButton = $wiki_owner;
				$pageHistory = wiki_page_history(array('resource_id' => $resource_id, 'page' => $pagename));
				break;
			default:	// Strip the extraneous URL components
				goaway('/'.argv(0).'/'.argv(1).'/'.argv(2).'/'.argv(3));
		}
		// Render the Markdown-formatted page content in HTML
		require_once('library/markdown.php');	
		
		$o .= replace_macros(get_markup_template('wiki.tpl'),array(
			'$wikiheader' => $wikiheader,
			'$hideEditor' => $hide_editor,
			'$showPageControls' => $showPageControls,
			'$showNewWikiButton'=> $showNewWikiButton,
			'$showNewPageButton'=> $showNewPageButton,
			'$channel' => $channel['channel_address'],
			'$resource_id' => $resource_id,
			'$page' => $pagename,
			'$lockstate' => $x['lockstate'],
			'$acl' => $x['acl'],
			'$bang' => $x['bang'],
			'$content' => $content,
			'$renderedContent' => Markdown(json_decode($content)),
			'$wikiName' => array('wikiName', t('Enter the name of your new wiki:'), '', ''),
			'$pageName' => array('pageName', t('Enter the name of the new page:'), '', ''),
			'$pageHistory' => $pageHistory['history']
		));
		head_add_js('library/ace/ace.js');	// Ace Code Editor
		return $o;
	}

	function post() {
		require_once('include/wiki.php');
		
		// /wiki/channel/preview
		// Render mardown-formatted text in HTML for preview
		if((argc() > 2) && (argv(2) === 'preview')) {
			$content = $_POST['content'];
			require_once('library/markdown.php');
			$html = Markdown($content);
			json_return_and_die(array('html' => $html, 'success' => true));
		}
		
		// Create a new wiki
		// /wiki/channel/create/wiki
		if ((argc() > 3) && (argv(2) === 'create') && (argv(3) === 'wiki')) {
			$nick = argv(1);
			// Determine if observer has permission to create wiki
			$observer_hash = get_observer_hash();
			if (local_channel()) {
				$channel = \App::get_channel();
			} else {
				$channel = get_channel_by_nick($nick);
				// Figure out who the page owner is.
				$perms = get_all_perms(intval($channel['channel_id']), $observer_hash);
				// TODO: Create a new permission setting for wiki analogous to webpages. Until
				// then, use webpage permissions
				if (!$perms['write_pages']) {
					notice(t('Permission denied.') . EOL);
					goaway('/'.argv(0).'/'.argv(1).'/'.argv(2));
				}
			}
			$wiki = array(); 
			// Generate new wiki info from input name
			$wiki['rawName'] = $_POST['wikiName'];
			$wiki['htmlName'] = escape_tags($_POST['wikiName']);
			$wiki['urlName'] = urlencode(escape_tags($_POST['wikiName'])); 
			if($wiki['urlName'] === '') {				
				notice('Error creating wiki. Invalid name.');
				goaway('/wiki');
			}
			// Get ACL for permissions
			$acl = new \Zotlabs\Access\AccessList($channel);
			$acl->set_from_array($_POST);
			$r = wiki_create_wiki($channel, $observer_hash, $wiki, $acl);
			if ($r['success']) {
				$homePage = wiki_create_page('Home', $r['item']['resource_id']);
				if(!$homePage['success']) {
					notice('Wiki created, but error creating Home page.');
					goaway('/wiki/'.$nick.'/'.$wiki['urlName']);
				}
				goaway('/wiki/'.$nick.'/'.$wiki['urlName'].'/'.$homePage['urlName']);
			} else {
				notice('Error creating wiki');
				goaway('/wiki');
			}
		}

		// Delete a wiki
		if ((argc() > 3) && (argv(2) === 'delete') && (argv(3) === 'wiki')) {
			$nick = argv(1);
			// Determine if observer has permission to create wiki
			if (local_channel()) {
				$channel = \App::get_channel();
			} else {
				$channel = get_channel_by_nick($nick);
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
			$nick = argv(1);
			$resource_id = $_POST['resource_id']; 
			// Determine if observer has permission to create wiki
			if (local_channel()) {
				$channel = \App::get_channel();
			} else {
				$channel = get_channel_by_nick($nick);
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
			$name = escape_tags(urlencode($_POST['name'])); //Get new page name
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
			$nick = argv(1);
			$resource_id = $_POST['resource_id']; 
			$pagename = escape_tags(urlencode($_POST['name'])); 
			$content = escape_tags($_POST['content']); //Get new content
			// Determine if observer has permission to save content
			if (local_channel()) {
				$channel = \App::get_channel();
			} else {
				$channel = get_channel_by_nick($nick);
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
		
		// Update page history
		// /wiki/channel/history/page
		if ((argc() === 4) && (argv(2) === 'history') && (argv(3) === 'page')) {
			$nick = argv(1);
			$resource_id = $_POST['resource_id'];
			$pagename = escape_tags(urlencode($_POST['name']));
			// Determine if observer has permission to view content
			if (local_channel()) {
				$channel = \App::get_channel();
			} else {
				$channel = get_channel_by_nick($nick);
				$observer_hash = get_observer_hash();
				$perms = wiki_get_permissions($resource_id, intval($channel['channel_id']), $observer_hash);
				if (!$perms['read']) {
					logger('Wiki read permission denied.' . EOL);
					json_return_and_die(array('historyHTML' => '', 'message' => 'Permission denied.', 'success' => false));
				}
			}
			$historyHTML = widget_wiki_page_history(array(
					'resource_id' => $resource_id,
					'page' => $pagename
			));
			json_return_and_die(array('historyHTML' => $historyHTML, 'message' => '', 'success' => true));
		}

		//notice('You must be authenticated.');
		json_return_and_die(array('message' => 'You must be authenticated.', 'success' => false));
		
	}
}
