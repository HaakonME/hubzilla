<?php /** @file */

namespace Zotlabs\Module;

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
		profile_load($nick);

	}

	function get() {

		if(observer_prohibited(true)) {
			return login();
		}

		if(! feature_enabled(\App::$profile_uid,'wiki')) {
			notice( t('Not found') . EOL);
     		return;
 		}
	
		$tab = 'wiki';
	
	
		require_once('include/wiki.php');
		require_once('include/acl_selectors.php');
		require_once('include/conversation.php');

		// TODO: Combine the interface configuration into a unified object
		// Something like $interface = array('new_page_button' => false, 'new_wiki_button' => false, ...)
		$wiki_owner = false;
		$showNewWikiButton = false;
		$showCommitMsg = false;
		$hidePageHistory = false;
		$pageHistory = array();
		$local_observer = null;
		$resource_id = '';
		
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
						? 'lock' : 'unlock'
					),
					'acl' => populate_acl($channel_acl),
					'allow_cid' => acl2json($channel_acl['allow_cid']),
					'allow_gid' => acl2json($channel_acl['allow_gid']),
					'deny_cid' => acl2json($channel_acl['deny_cid']),
					'deny_gid' => acl2json($channel_acl['deny_gid']),
					'bang' => ''
			);
		} else {
			// Not the channel owner 
			$channel_acl = $x = array();
		}

		switch (argc()) {
			case 2:
				// Configure page template
				$wikiheaderName = t('Wiki');
				$wikiheaderPage = t('Sandbox');
				require_once('library/markdown.php');	
				$content = t('"# Wiki Sandbox\n\nContent you **edit** and **preview** here *will not be saved*."');
				$renderedContent = Markdown(json_decode($content));
				$hide_editor = false;
				$showPageControls = false;
				$showNewWikiButton = $wiki_owner;
				$showNewPageButton = false;
				$hidePageHistory = true;
				$showCommitMsg = false;
				break;
			case 3:
				// /wiki/channel/wiki -> No page was specified, so redirect to Home.md
				$wikiUrlName = urlencode(argv(2));
				goaway('/'.argv(0).'/'.argv(1).'/'.$wikiUrlName.'/Home');
			case 4:
				// GET /wiki/channel/wiki/page
				// Fetch the wiki info and determine observer permissions
				$wikiUrlName = urlencode(argv(2));
				$pageUrlName = urlencode(argv(3));
				$w = wiki_exists_by_name($channel['channel_id'], $wikiUrlName);
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
					if($perms['write']) {
						$wiki_editor = true;
					} else {
						$wiki_editor = false;
					}
				} else {
					$wiki_editor = true;
				}
				$wikiheaderName = urldecode($wikiUrlName);
				$wikiheaderPage = urldecode($pageUrlName);
				$p = wiki_get_page_content(array('resource_id' => $resource_id, 'pageUrlName' => $pageUrlName));
				if(!$p['success']) {
					notice('Error retrieving page content' . EOL);
					goaway('/'.argv(0).'/'.argv(1).'/'.$wikiUrlName);
				}
				$content = ($p['content'] !== '' ? htmlspecialchars_decode($p['content'],ENT_COMPAT) : '"# New page\n"');
				// Render the Markdown-formatted page content in HTML
				require_once('library/markdown.php');
				$html = wiki_generate_toc(purify_html(Markdown(wiki_bbcode(json_decode($content)))));
				$renderedContent = wiki_convert_links($html,argv(0).'/'.argv(1).'/'.$wikiUrlName);
				$hide_editor = false;
				$showPageControls = $wiki_editor;
				$showNewWikiButton = $wiki_owner;
				$showNewPageButton = $wiki_editor;
				$hidePageHistory = false;
				$showCommitMsg = true;
				$pageHistory = wiki_page_history(array('resource_id' => $resource_id, 'pageUrlName' => $pageUrlName));
				break;
			default:	// Strip the extraneous URL components
				goaway('/'.argv(0).'/'.argv(1).'/'.$wikiUrlName.'/'.$pageUrlName);
		}
		
		$wikiModalID = random_string(3);
		$wikiModal = replace_macros(
			get_markup_template('generic_modal.tpl'), array(
				'$id' => $wikiModalID,
				'$title' => t('Revision Comparison'),
				'$ok' => t('Revert'),
				'$cancel' => t('Cancel')
			)
		);
				
		$is_owner = ((local_channel()) && (local_channel() == \App::$profile['profile_uid']) ? true : false);
		
		$o .= profile_tabs($a, $is_owner, \App::$profile['channel_address']);


		$o .= replace_macros(get_markup_template('wiki.tpl'),array(
			'$wikiheaderName' => $wikiheaderName,
			'$wikiheaderPage' => $wikiheaderPage,
			'$hideEditor' => $hide_editor,
			'$showPageControls' => $showPageControls,
			'$showNewWikiButton'=> $showNewWikiButton,
			'$showNewPageButton'=> $showNewPageButton,
			'$hidePageHistory' => $hidePageHistory,
			'$showCommitMsg' => $showCommitMsg,
			'$channel' => $channel['channel_address'],
			'$resource_id' => $resource_id,
			'$page' => $pageUrlName,
			'$lockstate' => $x['lockstate'],
			'$acl' => $x['acl'],
			'$allow_cid' => $x['allow_cid'],
			'$allow_gid' => $x['allow_gid'],
			'$deny_cid' => $x['deny_cid'],
			'$deny_gid' => $x['deny_gid'],
			'$bang' => $x['bang'],
			'$content' => $content,
			'$renderedContent' => $renderedContent,
			'$wikiName' => array('wikiName', t('Enter the name of your new wiki:'), '', ''),
			'$pageName' => array('pageName', t('Enter the name of the new page:'), '', ''),
			'$pageRename' => array('pageRename', t('Enter the new name:'), '', ''),
			'$commitMsg' => array('commitMsg', '', '', '', '', 'placeholder="(optional) Enter a custom message when saving the page..."'),
			'$pageHistory' => $pageHistory['history'],
			'$wikiModal' => $wikiModal,
			'$wikiModalID' => $wikiModalID,
			'$commit' => 'HEAD',
			'$embedPhotos' => t('Embed image from photo albums'),
			'$embedPhotosModalTitle' => t('Embed an image from your albums'),
			'$embedPhotosModalCancel' => t('Cancel'),
			'$embedPhotosModalOK' => t('OK'),
			'$modalchooseimages' => t('Choose images to embed'),
			'$modalchoosealbum' => t('Choose an album'),
			'$modaldiffalbum' => t('Choose a different album...'),
			'$modalerrorlist' => t('Error getting album list'),
			'$modalerrorlink' => t('Error getting photo link'),
			'$modalerroralbum' => t('Error getting album'),
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
			$resource_id = $_POST['resource_id']; 
			require_once('library/markdown.php');
			$content = wiki_bbcode($content);
			$html = wiki_generate_toc(purify_html(Markdown($content)));
			$w = wiki_get_wiki($resource_id);
			$wikiURL = argv(0).'/'.argv(1).'/'.$w['urlName'];
			$html = wiki_convert_links($html,$wikiURL);
			json_return_and_die(array('html' => $html, 'success' => true));
		}
		
		// Create a new wiki
		// /wiki/channel/create/wiki
		if ((argc() > 3) && (argv(2) === 'create') && (argv(3) === 'wiki')) {
			$nick = argv(1);
			$channel = get_channel_by_nick($nick);
			// Determine if observer has permission to create wiki
			$observer_hash = get_observer_hash();
			// Only the channel owner can create a wiki, at least until we create a 
			// more detail permissions framework
			if (local_channel() !== intval($channel['channel_id'])) {
				goaway('/'.argv(0).'/'.$nick.'/');
			} 
			$wiki = array(); 
			// Generate new wiki info from input name
			$wiki['postVisible'] = ((intval($_POST['postVisible']) === 0) ? 0 : 1);
			$wiki['rawName'] = $_POST['wikiName'];
			$wiki['htmlName'] = escape_tags($_POST['wikiName']);
			$wiki['urlName'] = urlencode($_POST['wikiName']); 
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
				goaway('/wiki/'.$nick.'/'.$wiki['urlName'].'/'.$homePage['page']['urlName']);
			} else {
				notice('Error creating wiki');
				goaway('/wiki');
			}
		}

		// Delete a wiki
		if ((argc() > 3) && (argv(2) === 'delete') && (argv(3) === 'wiki')) {
			$nick = argv(1);
			$channel = get_channel_by_nick($nick);
			// Only the channel owner can delete a wiki, at least until we create a 
			// more detail permissions framework
			if (local_channel() !== intval($channel['channel_id'])) {
				logger('Wiki delete permission denied.' . EOL);
				json_return_and_die(array('message' => 'Wiki delete permission denied.', 'success' => false));
			} 
			$resource_id = $_POST['resource_id']; 
			$deleted = wiki_delete_wiki($resource_id);
			if ($deleted['success']) {
				json_return_and_die(array('message' => '', 'success' => true));
			} else {
				logger('Error deleting wiki: ' . $resource_id);
				json_return_and_die(array('message' => 'Error deleting wiki', 'success' => false));
			}
		}

		// Create a page
		if ((argc() === 4) && (argv(2) === 'create') && (argv(3) === 'page')) {
			$nick = argv(1);
			$resource_id = $_POST['resource_id']; 
			// Determine if observer has permission to create a page
			$channel = get_channel_by_nick($nick);
			if (local_channel() !== intval($channel['channel_id'])) {
				$observer_hash = get_observer_hash();
				$perms = wiki_get_permissions($resource_id, intval($channel['channel_id']), $observer_hash);
				if(!$perms['write']) {
					logger('Wiki write permission denied. ' . EOL);
					json_return_and_die(array('success' => false));					
				}
			}
			$name = $_POST['name']; //Get new page name
			if(urlencode(escape_tags($_POST['name'])) === '') {				
				json_return_and_die(array('message' => 'Error creating page. Invalid name.', 'success' => false));
			}
			$page = wiki_create_page($name, $resource_id);
			if ($page['success']) {
				json_return_and_die(array('url' => '/'.argv(0).'/'.argv(1).'/'.$page['wiki']['urlName'].'/'.urlencode($page['page']['urlName']), 'success' => true));
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
			if (local_channel() !== intval($channel['channel_id'])) {
				$perms = wiki_get_permissions($resource_id, intval($channel['channel_id']), $observer_hash);
				if(!$perms['read']) {
					logger('Wiki read permission denied.' . EOL);
					json_return_and_die(array('pages' => null, 'message' => 'Permission denied.', 'success' => false));					
				}
			}
			$page_list_html = widget_wiki_pages(array(
					'resource_id' => $resource_id, 
					'refresh' => true, 
					'channel' => argv(1)));
			json_return_and_die(array('pages' => $page_list_html, 'message' => '', 'success' => true));					
		}
		
		// Save a page
		if ((argc() === 4) && (argv(2) === 'save') && (argv(3) === 'page')) {
			
			$resource_id = $_POST['resource_id']; 
			$pageUrlName = $_POST['name'];
			$pageHtmlName = escape_tags($_POST['name']);
			$content = $_POST['content']; //Get new content
			$commitMsg = $_POST['commitMsg']; 
			if ($commitMsg === '') {
				$commitMsg = 'Updated ' . $pageHtmlName;
			}
			$nick = argv(1);
			$channel = get_channel_by_nick($nick);
			// Determine if observer has permission to save content
			if (local_channel() !== intval($channel['channel_id'])) {
				$observer_hash = get_observer_hash();
				$perms = wiki_get_permissions($resource_id, intval($channel['channel_id']), $observer_hash);
				if(!$perms['write']) {
					logger('Wiki write permission denied. ' . EOL);
					json_return_and_die(array('success' => false));					
				}
			}
			
			$saved = wiki_save_page(array('resource_id' => $resource_id, 'pageUrlName' => $pageUrlName, 'content' => $content));
			if($saved['success']) {
				$ob = \App::get_observer();
				$commit = wiki_git_commit(array(
						'commit_msg' => $commitMsg, 
						'resource_id' => $resource_id, 
						'observer' => $ob,
						'files' => array($pageUrlName.'.md')
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
			
			$resource_id = $_POST['resource_id'];
			$pageUrlName = $_POST['name'];
			
			$nick = argv(1);
			$channel = get_channel_by_nick($nick);
			// Determine if observer has permission to read content
			if (local_channel() !== intval($channel['channel_id'])) {
				$observer_hash = get_observer_hash();
				$perms = wiki_get_permissions($resource_id, intval($channel['channel_id']), $observer_hash);
				if(!$perms['read']) {
					logger('Wiki read permission denied.' . EOL);
					json_return_and_die(array('historyHTML' => '', 'message' => 'Permission denied.', 'success' => false));
				}
			}
			$historyHTML = widget_wiki_page_history(array(
					'resource_id' => $resource_id,
					'pageUrlName' => $pageUrlName
			));
			json_return_and_die(array('historyHTML' => $historyHTML, 'message' => '', 'success' => true));
		}

		// Delete a page
		if ((argc() === 4) && (argv(2) === 'delete') && (argv(3) === 'page')) {
			$resource_id = $_POST['resource_id']; 
			$pageUrlName = $_POST['name'];
			if ($pageUrlName === 'Home') {
				json_return_and_die(array('message' => 'Cannot delete Home','success' => false));
			}
			// Determine if observer has permission to delete pages
			$nick = argv(1);
			$channel = get_channel_by_nick($nick);			
			if (local_channel() !== intval($channel['channel_id'])) {
				$observer_hash = get_observer_hash();
				$perms = wiki_get_permissions($resource_id, intval($channel['channel_id']), $observer_hash);
				if(!$perms['write']) {
					logger('Wiki write permission denied. ' . EOL);
					json_return_and_die(array('success' => false));					
				}
			}
			$deleted = wiki_delete_page(array('resource_id' => $resource_id, 'pageUrlName' => $pageUrlName));
			if($deleted['success']) {
				$ob = \App::get_observer();
				$commit = wiki_git_commit(array(
						'commit_msg' => 'Deleted ' . $pageUrlName, 
						'resource_id' => $resource_id, 
						'observer' => $ob,
						'files' => null
						));
				if($commit['success']) {
					json_return_and_die(array('message' => 'Wiki git repo commit made', 'success' => true));
				} else {
					json_return_and_die(array('message' => 'Error making git commit','success' => false));					
				}
			} else {
				json_return_and_die(array('message' => 'Error deleting page', 'success' => false));					
			}
		}
		
		// Revert a page
		if ((argc() === 4) && (argv(2) === 'revert') && (argv(3) === 'page')) {
			$resource_id = $_POST['resource_id']; 
			$pageUrlName = $_POST['name'];
			$commitHash = $_POST['commitHash'];
			// Determine if observer has permission to revert pages
			$nick = argv(1);
			$channel = get_channel_by_nick($nick);			
			if (local_channel() !== intval($channel['channel_id'])) {
				$observer_hash = get_observer_hash();
				$perms = wiki_get_permissions($resource_id, intval($channel['channel_id']), $observer_hash);
				if(!$perms['write']) {
					logger('Wiki write permission denied.' . EOL);
					json_return_and_die(array('success' => false));					
				}
			}
			$reverted = wiki_revert_page(array('commitHash' => $commitHash, 'resource_id' => $resource_id, 'pageUrlName' => $pageUrlName));
			if($reverted['success']) {
				json_return_and_die(array('content' => $reverted['content'], 'message' => '', 'success' => true));					
			} else {
				json_return_and_die(array('content' => '', 'message' => 'Error reverting page', 'success' => false));					
			}
		}
		
		// Compare page revisions
		if ((argc() === 4) && (argv(2) === 'compare') && (argv(3) === 'page')) {
			$resource_id = $_POST['resource_id']; 
			$pageUrlName = $_POST['name'];
			$compareCommit = $_POST['compareCommit'];
			$currentCommit = $_POST['currentCommit'];
			// Determine if observer has permission to revert pages
			$nick = argv(1);
			$channel = get_channel_by_nick($nick);			
			if (local_channel() !== intval($channel['channel_id'])) {
				$observer_hash = get_observer_hash();
				$perms = wiki_get_permissions($resource_id, intval($channel['channel_id']), $observer_hash);
				if(!$perms['read']) {
					logger('Wiki read permission denied.' . EOL);
					json_return_and_die(array('success' => false));					
				}
			}
			$compare = wiki_compare_page(array('currentCommit' => $currentCommit, 'compareCommit' => $compareCommit, 'resource_id' => $resource_id, 'pageUrlName' => $pageUrlName));
			if($compare['success']) {
				$diffHTML = '<table class="text-center" width="100%"><tr><td class="lead" width="50%">Current Revision</td><td class="lead" width="50%">Selected Revision</td></tr></table>' . $compare['diff'];
				json_return_and_die(array('diff' => $diffHTML, 'message' => '', 'success' => true));					
			} else {
				json_return_and_die(array('diff' => '', 'message' => 'Error comparing page', 'success' => false));					
			}
		}
		
		// Rename a page
		if ((argc() === 4) && (argv(2) === 'rename') && (argv(3) === 'page')) {
			$resource_id = $_POST['resource_id']; 
			$pageUrlName = $_POST['oldName'];
			$pageNewName = $_POST['newName'];
			if ($pageUrlName === 'Home') {
				json_return_and_die(array('message' => 'Cannot rename Home','success' => false));
			}
			if(urlencode(escape_tags($pageNewName)) === '') {				
				json_return_and_die(array('message' => 'Error renaming page. Invalid name.', 'success' => false));
			}
			// Determine if observer has permission to rename pages
			$nick = argv(1);
			$channel = get_channel_by_nick($nick);			
			if (local_channel() !== intval($channel['channel_id'])) {
				$observer_hash = get_observer_hash();
				$perms = wiki_get_permissions($resource_id, intval($channel['channel_id']), $observer_hash);
				if(!$perms['write']) {
					logger('Wiki write permission denied. ' . EOL);
					json_return_and_die(array('success' => false));					
				}
			}
			$renamed = wiki_rename_page(array('resource_id' => $resource_id, 'pageUrlName' => $pageUrlName, 'pageNewName' => $pageNewName));
			if($renamed['success']) {
				$ob = \App::get_observer();
				$commit = wiki_git_commit(array(
						'commit_msg' => 'Renamed ' . urldecode($pageUrlName) . ' to ' . $renamed['page']['htmlName'], 
						'resource_id' => $resource_id, 
						'observer' => $ob,
						'files' => array($pageUrlName . '.md', $renamed['page']['fileName']),
						'all' => true
						));
				if($commit['success']) {
					json_return_and_die(array('name' => $renamed['page'], 'message' => 'Wiki git repo commit made', 'success' => true));
				} else {
					json_return_and_die(array('message' => 'Error making git commit','success' => false));					
				}
			} else {
				json_return_and_die(array('message' => 'Error renaming page', 'success' => false));					
			}
		}

		//notice('You must be authenticated.');
		json_return_and_die(array('message' => 'You must be authenticated.', 'success' => false));
		
	}
}
