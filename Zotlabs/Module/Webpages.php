<?php
namespace Zotlabs\Module;

require_once('include/channel.php');
require_once('include/conversation.php');
require_once('include/acl_selectors.php');
require_once('include/PermissionDescription.php');


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
	
		profile_load($a,$which);
	
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
			'acl' => (($is_owner) ? populate_acl($channel_acl,false, \PermissionDescription::fromGlobalPermission('view_pages')) : ''),
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
	
		$r = q("select * from item_id left join item on item_id.iid = item.id 
			where item_id.uid = %d and service = 'WEBPAGE' and item_type = %d $sql_extra order by item.created desc",
			intval($owner),
			intval(ITEM_TYPE_WEBPAGE)
		);
	
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
					'pagetitle'	=> $rr['sid'],
					'mid'		=> $rr['mid'],
					'layout_mid'    => $rr['layout_mid']
				);
				$pages[$rr['iid']][] = array(
					'url'		=> $rr['iid'],
					'pagetitle'	=> $rr['sid'],
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
	
}
