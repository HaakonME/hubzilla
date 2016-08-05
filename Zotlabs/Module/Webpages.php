<?php
namespace Zotlabs\Module;

require_once('include/channel.php');
require_once('include/conversation.php');
require_once('include/acl_selectors.php');


class Webpages extends \Zotlabs\Web\Controller {

	function init() {
	
		if(argc() > 1 && argv(1) === 'sys' && is_site_admin()) {
			$sys = get_sys_channel();
			if($sys && intval($sys['channel_id'])) {
				\App::$is_sys = true;
			}
		}
	
		if(argc() > 1)
			$which = argv(1);
		else
			return;
	
		profile_load($which);
	
	}
	
	
	function get() {
	
		if(! \App::$profile) {
			notice( t('Requested profile is not available.') . EOL );
			\App::$error = 404;
			return;
		}
	
		$which = argv(1);
		
		$_SESSION['return_url'] = \App::$query_string;
	
		$uid = local_channel();
		$owner = 0;
		$channel = null;
		$observer = \App::get_observer();
	
		$channel = \App::get_channel();

		switch ($_SESSION['action']) {
        case 'import':
						$_SESSION['action'] = null;
						$o .= replace_macros(get_markup_template('webpage_import.tpl'), array(
							'$title'    => t('Import Webpage Elements'),
							'$importbtn' => t('Import selected'),
							'$action' => 'import',
							'$pages' => $_SESSION['pages'],
							'$layouts' => $_SESSION['layouts'],
							'$blocks' => $_SESSION['blocks'],
						));
						return $o;
				
        case 'importselected':
						$_SESSION['action'] = null;
						break;
				default :
						$_SESSION['action'] = null;
						break;
		}
		
		
		if(\App::$is_sys && is_site_admin()) {
			$sys = get_sys_channel();
			if($sys && intval($sys['channel_id'])) {
				$uid = $owner = intval($sys['channel_id']);
				$channel = $sys;
				$observer = $sys;
			}
		}
	
		if(! $owner) {
			// Figure out who the page owner is.
			$r = q("select channel_id from channel where channel_address = '%s'",
				dbesc($which)
			);
			if($r) {
				$owner = intval($r[0]['channel_id']);
			}
		}
	
		$ob_hash = (($observer) ? $observer['xchan_hash'] : '');
	
		$perms = get_all_perms($owner,$ob_hash);
	
		if(! $perms['write_pages']) {
			notice( t('Permission denied.') . EOL);
			return;
		}
	
		$mimetype = (($_REQUEST['mimetype']) ? $_REQUEST['mimetype'] : get_pconfig($owner,'system','page_mimetype'));
	
		$layout = (($_REQUEST['layout']) ? $_REQUEST['layout'] : get_pconfig($owner,'system','page_layout'));
	
		// Create a status editor (for now - we'll need a WYSIWYG eventually) to create pages
		// Nickname is set to the observers xchan, and profile_uid to the owner's.  
		// This lets you post pages at other people's channels.
	
		if((! $channel) && ($uid) && ($uid == \App::$profile_uid)) {
			$channel = \App::get_channel();
		}
		if($channel) {
			$channel_acl = array(
				'allow_cid' => $channel['channel_allow_cid'],
				'allow_gid' => $channel['channel_allow_gid'],
				'deny_cid'  => $channel['channel_deny_cid'],
				'deny_gid'  => $channel['channel_deny_gid']
			);
		}
		else
			$channel_acl = array();
	
		$is_owner = ($uid && $uid == $owner);
		$o = profile_tabs($a, $is_owner, \App::$profile['channel_address']);
	
		$x = array(
			'webpage' => ITEM_TYPE_WEBPAGE,
			'is_owner' => true,
			'nickname' => \App::$profile['channel_address'],
			'lockstate' => (($channel['channel_allow_cid'] || $channel['channel_allow_gid'] || $channel['channel_deny_cid'] || $channel['channel_deny_gid']) ? 'lock' : 'unlock'),
			'acl' => (($is_owner) ? populate_acl($channel_acl,false, \Zotlabs\Lib\PermissionDescription::fromGlobalPermission('view_pages')) : ''),
			'permissions' => (($is_owner) ? $channel_acl : ''),
			'showacl' => (($is_owner) ? true : false),
			'visitor' => true,
			'hide_location' => true,
			'hide_voting' => true,
			'profile_uid' => intval($owner),
			'mimetype' => $mimetype,
			'mimeselect' => true,
			'layout' => $layout,
			'layoutselect' => true,
			'expanded' => true,
			'novoting'=> true,
			'bbco_autocomplete' => 'bbcode',
			'bbcode' => true
		);
		
		if($_REQUEST['title'])
			$x['title'] = $_REQUEST['title'];
		if($_REQUEST['body'])
			$x['body'] = $_REQUEST['body'];
		if($_REQUEST['pagetitle'])
			$x['pagetitle'] = $_REQUEST['pagetitle'];
	
		$editor = status_editor($a,$x);
	
		// Get a list of webpages.  We can't display all them because endless scroll makes that unusable, 
		// so just list titles and an edit link.
	
	
		/** @TODO - this should be replaced with pagelist_widget */
	
		$sql_extra = item_permissions_sql($owner);
	

		$r = q("select * from iconfig left join item on iconfig.iid = item.id 
			where item.uid = %d and iconfig.cat = 'system' and iconfig.k = 'WEBPAGE' and item_type = %d 
			$sql_extra order by item.created desc",
			intval($owner),
			intval(ITEM_TYPE_WEBPAGE)
		);

//		$r = q("select * from item_id left join item on item_id.iid = item.id 
//			where item_id.uid = %d and service = 'WEBPAGE' and item_type = %d $sql_extra order by item.created desc",
//			intval($owner),
//			intval(ITEM_TYPE_WEBPAGE)
//		);
	
		$pages = null;
	
		if($r) {
			$pages = array();
			foreach($r as $rr) {
				unobscure($rr);
	
				$lockstate = (($rr['allow_cid'] || $rr['allow_gid'] || $rr['deny_cid'] || $rr['deny_gid']) ? 'lock' : 'unlock');
	
				$element_arr = array(
					'type'		=> 'webpage',
					'title'		=> $rr['title'],
					'body'		=> $rr['body'],
					'created'	=> $rr['created'],
					'edited'	=> $rr['edited'],
					'mimetype'	=> $rr['mimetype'],
					'pagetitle'	=> $rr['v'],
					'mid'		=> $rr['mid'],
					'layout_mid'    => $rr['layout_mid']
				);
				$pages[$rr['iid']][] = array(
					'url'		=> $rr['iid'],
					'pagetitle'	=> $rr['v'],
					'title'		=> $rr['title'],
					'created'	=> datetime_convert('UTC',date_default_timezone_get(),$rr['created']),
					'edited'	=> datetime_convert('UTC',date_default_timezone_get(),$rr['edited']),
					'bb_element'	=> '[element]' . base64url_encode(json_encode($element_arr)) . '[/element]',
					'lockstate'     => $lockstate
				);
			}
		}
	
	
		//Build the base URL for edit links
		$url = z_root() . '/editwebpage/' . $which;
		
		$o .= replace_macros(get_markup_template('webpagelist.tpl'), array(
			'$listtitle'    => t('Webpages'),
			'$baseurl'      => $url,
			'$create'       => t('Create'),
			'$edit'         => t('Edit'),
			'$share'	=> t('Share'),
			'$delete'	=> t('Delete'),
			'$pages'        => $pages,
			'$channel'      => $which,
			'$editor'	=> $editor,
			'$view'         => t('View'),
			'$preview'      => t('Preview'),
			'$actions_txt'  => t('Actions'),
			'$pagelink_txt' => t('Page Link'),
			'$title_txt'    => t('Page Title'),
			'$created_txt'  => t('Created'),
			'$edited_txt'   => t('Edited')
		));
	
		return $o;
	}
	
	function post() {
		
    $action = $_REQUEST['action'];
		if( $action ){
			switch ($action) {
        case 'scan':
					
					// the state of this variable tracks whether website files have been scanned (null, true, false)
					$cloud = null;	
					
					// Website files are to be imported from an uploaded zip file
					if(($_FILES) && array_key_exists('zip_file',$_FILES) && isset($_POST['w_upload'])) {
						$source = $_FILES["zip_file"]["tmp_name"];
						$type = $_FILES["zip_file"]["type"];
						$okay = false;
						$accepted_types = array('application/zip', 'application/x-zip-compressed', 'multipart/x-zip', 'application/x-compressed');
						foreach ($accepted_types as $mime_type) {
							if ($mime_type == $type) {
								$okay = true;
								break;
							}
						}
						if(!$okay) {
							notice( t('Invalid file type.') . EOL);
							return;
						}
						$zip = new \ZipArchive();
						if ($zip->open($source) === true) {
							$tmp_folder_name = random_string(5);
							$website = dirname($source) . '/' . $tmp_folder_name;
							$zip->extractTo($website); // change this to the correct site path
							$zip->close();
							@unlink($source);	// delete the compressed file now that the content has been extracted
							$cloud = false;
						} else {
							notice( t('Error opening zip file') . EOL);
							return null;
						}	
					} 

					// Website files are to be imported from the channel cloud files
					if (($_POST) && array_key_exists('path',$_POST) && isset($_POST['cloudsubmit'])) {

						$channel = \App::get_channel();
						$dirpath = get_dirpath_by_cloudpath($channel, $_POST['path']);
						if(!$dirpath) {
							notice( t('Invalid folder path.') . EOL);
							return null;
						}
						$cloud = true;

					}
					
					// If the website files were uploaded or specified in the cloud files, then $cloud
					// should be either true or false
					if ($cloud !== null) {
						require_once('include/import.php');
						$elements = [];
						if($cloud) {
								$path = $_POST['path'];
						} else {
								$path = $website;
						}
						$elements['pages'] = scan_webpage_elements($path, 'page', $cloud);
						$elements['layouts'] = scan_webpage_elements($path, 'layout', $cloud);
						$elements['blocks'] = scan_webpage_elements($path, 'block', $cloud);
						$_SESSION['blocks'] = $elements['blocks'];
						$_SESSION['layouts'] = $elements['layouts'];
						$_SESSION['pages'] = $elements['pages'];
						if(!(empty($elements['pages']) && empty($elements['blocks']) && empty($elements['layouts']))) {
							//info( t('Webpages elements detected.') . EOL);
							$_SESSION['action'] = 'import';
						} else {
							notice( t('No webpage elements detected.') . EOL);
							$_SESSION['action'] = null;
						}
						
					}
					
					// If the website elements were imported from a zip file, delete the temporary decompressed files
					if ($cloud === false && $website && $elements) {
						rrmdir($website);	// Delete the temporary decompressed files
					}
					
					break;
					
				case 'importselected':
						require_once('include/import.php');
						$channel = \App::get_channel();
						
						// Import layout first so that pages that reference new layouts will find
						// the mid of layout items in the database						
						
            // Obtain the user-selected layouts to import and import them
            $checkedlayouts = $_POST['layout'];
            $layouts = [];
            if (!empty($checkedlayouts)) {
                foreach ($checkedlayouts as $name) {
                    foreach ($_SESSION['layouts'] as &$layout) {
                        if ($layout['name'] === $name) {
                            $layout['import'] = 1;
                            $layoutstoimport[] = $layout;
                        }
                    }
                }
								foreach ($layoutstoimport as $elementtoimport) {
										$layouts[] = import_webpage_element($elementtoimport, $channel, 'layout');
								}
            }
            $_SESSION['import_layouts'] = $layouts;
            
            // Obtain the user-selected blocks to import and import them
            $checkedblocks = $_POST['block'];
            $blocks = [];
            if (!empty($checkedblocks)) {
                foreach ($checkedblocks as $name) {
                    foreach ($_SESSION['blocks'] as &$block) {
                        if ($block['name'] === $name) {
                            $block['import'] = 1;
                            $blockstoimport[] = $block;
                        }
                    }
                }
								foreach ($blockstoimport as $elementtoimport) {
										$blocks[] = import_webpage_element($elementtoimport, $channel, 'block');
								}
            }
            $_SESSION['import_blocks'] = $blocks;
            
            // Obtain the user-selected pages to import and import them
            $checkedpages = $_POST['page'];
            $pages = [];
            if (!empty($checkedpages)) {
                foreach ($checkedpages as $pagelink) {
                    foreach ($_SESSION['pages'] as &$page) {
                        if ($page['pagelink'] === $pagelink) {
                            $page['import'] = 1;
                            $pagestoimport[] = $page;
                        }
                    }
                }
								foreach ($pagestoimport as $elementtoimport) {
										$pages[] = import_webpage_element($elementtoimport, $channel, 'page');
								}
            }
            $_SESSION['import_pages'] = $pages;
						if(!(empty($_SESSION['import_pages']) && empty($_SESSION['import_blocks']) && empty($_SESSION['import_layouts']))) {
								info( t('Import complete.') . EOL);
						}
						break;

				default :
					break;
			}
		}
		
				
		
		
	}
	
}
