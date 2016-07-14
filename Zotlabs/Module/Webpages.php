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
		
		if(($_FILES) && array_key_exists('zip_file',$_FILES)) {
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
				json_return_and_die(array('message' => 'Invalid file MIME type'));
			}
			$zip = new \ZipArchive();
			if ($zip->open($source) === true) {
				$tmp_folder_name = random_string(5);
				$website = dirname($source) . '/' . $tmp_folder_name;
				$zip->extractTo($website); // change this to the correct site path
				$zip->close();
				@unlink($source);

				$hubsites = $this->import_website($website);
				$channel = \App::get_channel();
				$blocks = $this->import_blocks($channel, $hubsites['blocks']);
				$pages = $this->import_pages($channel, $hubsites['pages']);
				$layouts = $this->import_layouts($channel, $hubsites['layouts']);
				if($blocks || $pages || $layouts) {	// Without the if statement, the folder is deleted before the import_blocks function completes.
					rrmdir($website);
				}
			}
			
		
		}
	}
	
	private function import_website($path) {
			$hubsites = [];
			$pages = [];
			$blocks = [];
			$layouts = [];
			// Import pages
			$dirtoscan = $path . '/pages/';
			if (is_dir($dirtoscan)) {
					$dirlist = scandir($dirtoscan);
					if ($dirlist) {
							foreach ($dirlist as $element) {
									if ($element === '.' || $element === '..') {
											continue;
									}
									$folder = $dirtoscan . '/' . $element;
									if (is_dir($folder)) {
											$jsonfilepath = $folder . '/page.json';
											if (is_file($jsonfilepath)) {
													$pagejson = json_decode(file_get_contents($jsonfilepath), true);
													$pagejson['path'] = $folder . '/' . $pagejson['contentfile'];
													if ($pagejson['contentfile'] === '') {
															logger('Invalid page content file');
															return false;
													}
													$pagecontent = file_get_contents($folder . '/' . $pagejson['contentfile']);
													if (!$pagecontent) {
															logger('Failed to get file content for ' . $pagejson['contentfile']);
															return false;
													}
													$pages[] = $pagejson;
											}
									}
							}
					}
			}
			$hubsites['pages'] = $pages;
			// Import layouts
			$dirtoscan = $path . '/layouts/';
			if (is_dir($dirtoscan)) {
					$dirlist = scandir($dirtoscan);
					if ($dirlist) {
							foreach ($dirlist as $element) {
									if ($element === '.' || $element === '..') {
											continue;
									}
									$folder = $dirtoscan . '/' . $element;
									if (is_dir($folder)) {
											$jsonfilepath = $folder . '/layout.json';
											if (is_file($jsonfilepath)) {
													$layoutjson = json_decode(file_get_contents($jsonfilepath), true);
													$layoutjson['path'] = $folder . '/' . $layoutjson['contentfile'];
													if ($layoutjson['contentfile'] === '') {
															logger('Invalid layout content file');
															return false;
													}
													$layoutcontent = file_get_contents($folder . '/' . $layoutjson['contentfile']);
													if (!$layoutcontent) {
															logger('Failed to get file content for ' . $layoutjson['contentfile']);
															return false;
													}
													$layouts[] = $layoutjson;
											}
									}
							}
					}
			}
			$hubsites['layouts'] = $layouts;
			// Import blocks
			$dirtoscan = $path . '/blocks/';
			if (is_dir($dirtoscan)) {
					$dirlist = scandir($dirtoscan);
					if ($dirlist) {
							foreach ($dirlist as $element) {
									if ($element === '.' || $element === '..') {
											continue;
									}
									$folder = $dirtoscan . '/' . $element;
									if (is_dir($folder)) {
											$jsonfilepath = $folder . '/block.json';
											if (is_file($jsonfilepath)) {
													$block = json_decode(file_get_contents($jsonfilepath), true);
													$block['path'] = $folder . '/' . $block['contentfile'];
													if ($block['contentfile'] === '') {
															logger('Invalid block content file');
															return false;
													}
													$blockcontent = file_get_contents($folder . '/' . $block['contentfile']);
													if (!$blockcontent) {
															logger('Failed to get file content for ' . $block['contentfile']);
															return false;
													}
													$blocks[] = $block;
											}
									}
							}
					}
			}
			$hubsites['blocks'] = $blocks;
			return $hubsites;
	}
	
	private function import_blocks($channel, $blocks) {
    foreach ($blocks as &$b) {
        
        $arr = array();
        $arr['item_type'] = ITEM_TYPE_BLOCK;
        $namespace = 'BUILDBLOCK';
        $arr['uid'] = $channel['channel_id'];
        $arr['aid'] = $channel['channel_account_id'];
        
        $iid = q("select iid from iconfig where k = 'BUILDBLOCK' and v = '%s' and cat = 'system'",
                dbesc($b['name'])
        );
        if($iid) {
            $iteminfo = q("select mid,created,edited from item where id = %d",
                    intval($iid[0]['iid'])
            );
            $arr['mid'] = $arr['parent_mid'] = $iteminfo[0]['mid'];
            $arr['created'] = $iteminfo[0]['created'];
            $arr['edited'] = (($b['edited']) ? datetime_convert('UTC', 'UTC', $b['edited']) : datetime_convert());
        } else {
            $arr['created'] = (($b['created']) ? datetime_convert('UTC', 'UTC', $b['created']) : datetime_convert());
            $arr['edited'] = datetime_convert('UTC', 'UTC', '0000-00-00 00:00:00');
            $arr['mid'] = $arr['parent_mid'] = item_message_id();
        }
        $arr['title'] = $b['title'];
        $arr['body'] = file_get_contents($b['path']);
        $arr['owner_xchan'] = get_observer_hash();
        $arr['author_xchan'] = (($b['author_xchan']) ? $b['author_xchan'] : get_observer_hash());
        if(($b['mimetype'] === 'text/bbcode' || $b['mimetype'] === 'text/html' ||
                $b['mimetype'] === 'text/markdown' ||$b['mimetype'] === 'text/plain' ||
                $b['mimetype'] === 'application/x-pdl' ||$b['mimetype'] === 'application/x-php')) {
            $arr['mimetype'] = $b['mimetype'];
        } else {
            $arr['mimetype'] = 'text/bbcode';
        }

        $pagetitle = $b['name']; 

        // Verify ability to use html or php!!!
        $execflag = false;
        if ($arr['mimetype'] === 'application/x-php') {
            $z = q("select account_id, account_roles, channel_pageflags from account left join channel on channel_account_id = account_id where channel_id = %d limit 1", intval(local_channel())
            );

            if ($z && (($z[0]['account_roles'] & ACCOUNT_ROLE_ALLOWCODE) || ($z[0]['channel_pageflags'] & PAGE_ALLOWCODE))) {
                $execflag = true;
            }
        }

        $remote_id = 0;

        $z = q("select * from iconfig where v = '%s' and k = '%s' and cat = 'service' limit 1", dbesc($pagetitle), dbesc($namespace));

        $i = q("select id, edited, item_deleted from item where mid = '%s' and uid = %d limit 1", dbesc($arr['mid']), intval(local_channel())
        );
        if ($z && $i) {
            $remote_id = $z[0]['id'];
            $arr['id'] = $i[0]['id'];
            // don't update if it has the same timestamp as the original
            if ($arr['edited'] > $i[0]['edited'])
                $x = item_store_update($arr, $execflag);
        } else {
            if (($i) && (intval($i[0]['item_deleted']))) {
                // was partially deleted already, finish it off
                q("delete from item where mid = '%s' and uid = %d", dbesc($arr['mid']), intval(local_channel())
                );
            }
            $x = item_store($arr, $execflag);
        }
        if ($x['success']) {
            $item_id = $x['item_id'];
            update_remote_id($channel, $item_id, $arr['item_type'], $pagetitle, $namespace, $remote_id, $arr['mid']);
            $b['import_success'] = 1;
        } else {
            $b['import_success'] = 0;
        }
    }
    return $blocks;
}


private function import_pages($channel, $pages) {
    foreach ($pages as &$p) {
        
        $arr = array();
        $arr['item_type'] = ITEM_TYPE_WEBPAGE;
        $namespace = 'WEBPAGE';
        $arr['uid'] = $channel['channel_id'];
        $arr['aid'] = $channel['channel_account_id'];

        if($p['pagelink']) {
                require_once('library/urlify/URLify.php');
                $pagetitle = strtolower(\URLify::transliterate($p['pagelink']));
        }
        $arr['layout_mid'] = ''; // by default there is no layout associated with the page
        // If a layout was specified, find it in the database and get its info. If
        // it does not exist, leave layout_mid empty
        logger('hubsites plugin: $p[layout] = ' . $p['layout']);
        if($p['layout'] !== '') {
            $liid = q("select iid from iconfig where k = 'PDL' and v = '%s' and cat = 'system'",
                    dbesc($p['layout'])
            );
            if($liid) {
                $linfo = q("select mid from item where id = %d",
                        intval($liid[0]['iid'])
                );
                logger('hubsites plugin: $linfo= ' . json_encode($linfo,true));
                $arr['layout_mid'] = $linfo[0]['mid'];
            }                 
        }
        // See if the page already exists
        $iid = q("select iid from iconfig where k = 'WEBPAGE' and v = '%s' and cat = 'system'",
                dbesc($pagetitle)
        );
        if($iid) {
            // Get the existing page info
            $pageinfo = q("select mid,layout_mid,created,edited from item where id = %d",
                    intval($iid[0]['iid'])
            );
            $arr['mid'] = $arr['parent_mid'] = $pageinfo[0]['mid'];
            $arr['created'] = $pageinfo[0]['created'];
            $arr['edited'] = (($p['edited']) ? datetime_convert('UTC', 'UTC', $p['edited']) : datetime_convert());
        } else {
            $arr['created'] = (($p['created']) ? datetime_convert('UTC', 'UTC', $p['created']) : datetime_convert());
            $arr['edited'] = datetime_convert('UTC', 'UTC', '0000-00-00 00:00:00');
            $arr['mid'] = $arr['parent_mid'] = item_message_id();
        }
        $arr['title'] = $p['title'];
        $arr['body'] = file_get_contents($p['path']);
        $arr['term'] = $p['term'];  // Not sure what this is supposed to be
        
        $arr['owner_xchan'] = get_observer_hash();
        $arr['author_xchan'] = (($p['author_xchan']) ? $p['author_xchan'] : get_observer_hash());
        if(($p['mimetype'] === 'text/bbcode' || $p['mimetype'] === 'text/html' ||
                $p['mimetype'] === 'text/markdown' ||$p['mimetype'] === 'text/plain' ||
                $p['mimetype'] === 'application/x-pdl' ||$p['mimetype'] === 'application/x-php')) {
            $arr['mimetype'] = $p['mimetype'];
        } else {
            $arr['mimetype'] = 'text/bbcode';
        }

        // Verify ability to use html or php!!!
        $execflag = false;
        if ($arr['mimetype'] === 'application/x-php') {
            $z = q("select account_id, account_roles, channel_pageflags from account left join channel on channel_account_id = account_id where channel_id = %d limit 1", intval(local_channel())
            );

            if ($z && (($z[0]['account_roles'] & ACCOUNT_ROLE_ALLOWCODE) || ($z[0]['channel_pageflags'] & PAGE_ALLOWCODE))) {
                $execflag = true;
            }
        }

        $remote_id = 0;

        $z = q("select * from iconfig where v = '%s' and k = '%s' and cat = 'system' limit 1",
                dbesc($pagetitle),
                dbesc($namespace)
        );

        $i = q("select id, edited, item_deleted from item where mid = '%s' and uid = %d limit 1", 
                dbesc($arr['mid']), 
                intval(local_channel())
        );
        if ($z && $i) {
            $remote_id = $z[0]['id'];
            $arr['id'] = $i[0]['id'];
            // don't update if it has the same timestamp as the original
            if ($arr['edited'] > $i[0]['edited'])
                $x = item_store_update($arr, $execflag);
        } else {
            if (($i) && (intval($i[0]['item_deleted']))) {
                // was partially deleted already, finish it off
                q("delete from item where mid = '%s' and uid = %d", dbesc($arr['mid']), intval(local_channel())
                );
            }
            logger('hubsites plugin: item_store= ' . json_encode($arr,true));
            $x = item_store($arr, $execflag);
        }
        if ($x['success']) {
            $item_id = $x['item_id'];
            update_remote_id($channel, $item_id, $arr['item_type'], $pagetitle, $namespace, $remote_id, $arr['mid']);
            $p['import_success'] = 1;
        } else {
            $p['import_success'] = 0;
        }
    }
    return $pages;
    
}

private function import_layouts($channel, $layouts) {
    foreach ($layouts as &$p) {
        
        $arr = array();
        $arr['item_type'] = ITEM_TYPE_PDL;
        $namespace = 'PDL';
        $arr['uid'] = $channel['channel_id'];
        $arr['aid'] = $channel['channel_account_id'];
        $pagetitle = $p['name'];
        // See if the layout already exists
        $iid = q("select iid from iconfig where k = 'PDL' and v = '%s' and cat = 'system'",
                dbesc($pagetitle)
        );
        if($iid) {
            // Get the existing layout info
            $info = q("select mid,layout_mid,created,edited from item where id = %d",
                    intval($iid[0]['iid'])
            );
            $arr['mid'] = $arr['parent_mid'] = $info[0]['mid'];
            $arr['created'] = $info[0]['created'];
            $arr['edited'] = (($p['edited']) ? datetime_convert('UTC', 'UTC', $p['edited']) : datetime_convert());
        } else {
            $arr['created'] = (($p['created']) ? datetime_convert('UTC', 'UTC', $p['created']) : datetime_convert());
            $arr['edited'] = datetime_convert('UTC', 'UTC', '0000-00-00 00:00:00');
            $arr['mid'] = $arr['parent_mid'] = item_message_id();
        }
        $arr['title'] = $p['description'];
        $arr['body'] = file_get_contents($p['path']);
        $arr['term'] = $p['term'];  // Not sure what this is supposed to be
        
        $arr['owner_xchan'] = get_observer_hash();
        $arr['author_xchan'] = (($p['author_xchan']) ? $p['author_xchan'] : get_observer_hash());
        $arr['mimetype'] = 'text/bbcode';

        $remote_id = 0;

        $z = q("select * from iconfig where v = '%s' and k = '%s' and cat = 'system' limit 1",
                dbesc($pagetitle),
                dbesc($namespace)
        );

        $i = q("select id, edited, item_deleted from item where mid = '%s' and uid = %d limit 1", 
                dbesc($arr['mid']), 
                intval(local_channel())
        );
        if ($z && $i) {
            $remote_id = $z[0]['id'];
            $arr['id'] = $i[0]['id'];
            // don't update if it has the same timestamp as the original
            if ($arr['edited'] > $i[0]['edited'])
                $x = item_store_update($arr, $execflag);
        } else {
            if (($i) && (intval($i[0]['item_deleted']))) {
                // was partially deleted already, finish it off
                q("delete from item where mid = '%s' and uid = %d", dbesc($arr['mid']), intval(local_channel())
                );
            }
            $x = item_store($arr, $execflag);
        }
        if ($x['success']) {
            $item_id = $x['item_id'];
            update_remote_id($channel, $item_id, $arr['item_type'], $pagetitle, $namespace, $remote_id, $arr['mid']);
            $p['import_success'] = 1;
        } else {
            $p['import_success'] = 0;
        }
    }
    return $layouts;
    
}


}
