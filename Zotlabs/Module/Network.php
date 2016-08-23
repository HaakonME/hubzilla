<?php
namespace Zotlabs\Module;

require_once('include/items.php');
require_once('include/group.php');
require_once('include/contact_widgets.php');
require_once('include/conversation.php');
require_once('include/acl_selectors.php');


class Network extends \Zotlabs\Web\Controller {

	function init() {
		if(! local_channel()) {
			notice( t('Permission denied.') . EOL);
			return;
		}
	
		if(count($_GET) < 2) {
			$network_options = get_pconfig(local_channel(),'system','network_page_default');
			if($network_options)
				goaway('network' . '?f=&' . $network_options);
		}
	
		$channel = \App::get_channel();
		\App::$profile_uid = local_channel();
		head_set_icon($channel['xchan_photo_s']);
	
	}
	
	function get($update = 0, $load = false) {
	
		if(! local_channel()) {
			$_SESSION['return_url'] = \App::$query_string;
			return login(false);
		}
	
		if($load)
			$_SESSION['loadtime'] = datetime_convert();
	
		$arr = array('query' => \App::$query_string);
	
		call_hooks('network_content_init', $arr);
	
		$channel = \App::get_channel();
		$item_normal = item_normal();
	
		$datequery = $datequery2 = '';
	
		$group = 0;
	
		$nouveau    = false;
	
		$datequery  = ((x($_GET,'dend') && is_a_date_arg($_GET['dend'])) ? notags($_GET['dend']) : '');
		$datequery2 = ((x($_GET,'dbegin') && is_a_date_arg($_GET['dbegin'])) ? notags($_GET['dbegin']) : '');
		$nouveau    = ((x($_GET,'new')) ? intval($_GET['new']) : 0);
		$gid        = ((x($_GET,'gid')) ? intval($_GET['gid']) : 0);
		$category   = ((x($_REQUEST,'cat')) ? $_REQUEST['cat'] : '');
		$hashtags   = ((x($_REQUEST,'tag')) ? $_REQUEST['tag'] : '');
		$verb       = ((x($_REQUEST,'verb')) ? $_REQUEST['verb'] : '');
	
		$search = (($_GET['search']) ? $_GET['search'] : '');
		if($search) {
			if(strpos($search,'@') === 0) {
				$r = q("select abook_id from abook left join xchan on abook_xchan = xchan_hash where xchan_name = '%s' and abook_channel = %d limit 1",
					dbesc(substr($search,1)),
					intval(local_channel())
				);
				if($r) {
					$_GET['cid'] = $r[0]['abook_id'];
					$search = $_GET['search'] = '';
				}
			}
			elseif(strpos($search,'#') === 0) {
				$hashtags = substr($search,1);
				$search = $_GET['search'] = '';
			}
		}
	
		if($datequery)
			$_GET['order'] = 'post';
	
	
		// filter by collection (e.g. group)
	
		if($gid) {
			$r = q("SELECT * FROM groups WHERE id = %d AND uid = %d LIMIT 1",
				intval($gid),
				intval(local_channel())
			);
			if(! $r) {
				if($update)
					killme();
				notice( t('No such group') . EOL );
				goaway(z_root() . '/network');
				// NOTREACHED
			}
	
			$group      = $gid;
			$group_hash = $r[0]['hash'];
			$def_acl    = array('allow_gid' => '<' . $r[0]['hash'] . '>');
		}
	
		$o = '';
	
	
		// if no tabs are selected, defaults to comments
	
		$cid      = ((x($_GET,'cid'))   ? intval($_GET['cid'])   : 0);
		$star     = ((x($_GET,'star'))  ? intval($_GET['star'])  : 0);
		$order    = ((x($_GET,'order')) ? notags($_GET['order']) : 'comment');
		$liked    = ((x($_GET,'liked')) ? intval($_GET['liked']) : 0);
		$conv     = ((x($_GET,'conv'))  ? intval($_GET['conv'])  : 0);
		$spam     = ((x($_GET,'spam'))  ? intval($_GET['spam'])  : 0);
		$cmin     = ((x($_GET,'cmin'))  ? intval($_GET['cmin'])  : 0);
		$cmax     = ((x($_GET,'cmax'))  ? intval($_GET['cmax'])  : 99);
		$firehose = ((x($_GET,'fh'))    ? intval($_GET['fh'])    : 0);
		$file     = ((x($_GET,'file'))  ? $_GET['file']          : '');
	
	
		$deftag = '';
	
		if(x($_GET,'search') || x($_GET,'file'))
			$nouveau = true;
		if($cid) {
			$r = q("SELECT abook_xchan FROM abook WHERE abook_id = %d AND abook_channel = %d LIMIT 1",
				intval($cid),
				intval(local_channel())
			);
			if(! $r) {
				if($update) {
					killme();
				}
				notice( t('No such channel') . EOL );
				goaway(z_root() . '/network');
				// NOTREACHED
			}
			if($_GET['pf'] === '1')
				$deftag = '@' . t('forum') . '+' . intval($cid) . '+';
			else
				$def_acl = array('allow_cid' => '<' . $r[0]['abook_xchan'] . '>');
		}
	
		if(! $update) {
			$tabs = network_tabs();
			$o .= $tabs;
	
			// search terms header
			if($search) {
				$o .= replace_macros(get_markup_template("section_title.tpl"),array(
					'$title' => t('Search Results For:') . ' ' . htmlspecialchars($search, ENT_COMPAT,'UTF-8')
				));
			}
	
			nav_set_selected('network');

			$channel_acl = array(
				'allow_cid' => $channel['channel_allow_cid'], 
				'allow_gid' => $channel['channel_allow_gid'], 
				'deny_cid'  => $channel['channel_deny_cid'], 
				'deny_gid'  => $channel['channel_deny_gid']
			); 

			$private_editing = ((($group || $cid) && (! intval($_GET['pf']))) ? true : false);
	
			$x = array(
				'is_owner'         => true,
				'allow_location'   => ((intval(get_pconfig($channel['channel_id'],'system','use_browser_location'))) ? '1' : ''),
				'default_location' => $channel['channel_location'],
				'nickname'         => $channel['channel_address'],
				'lockstate'        => (($private_editing || $channel['channel_allow_cid'] || $channel['channel_allow_gid'] || $channel['channel_deny_cid'] || $channel['channel_deny_gid']) ? 'lock' : 'unlock'),
				'acl'              => populate_acl((($private_editing) ? $def_acl : $channel_acl), true, \Zotlabs\Lib\PermissionDescription::fromGlobalPermission('view_stream'), get_post_aclDialogDescription(), 'acl_dialog_post'),
				'permissions'      => (($private_editing) ? $def_acl : $channel_acl),
				'bang'             => (($private_editing) ? '!' : ''),
				'visitor'          => true,
				'profile_uid'      => local_channel(),
				'editor_autocomplete' => true,
				'bbco_autocomplete' => 'bbcode',
				'bbcode' => true
			);
			if($deftag)
				$x['pretext'] = $deftag;
	
	
			$status_editor = status_editor($a,$x);
			$o .= $status_editor;
	
		}
	
	
		// We don't have to deal with ACL's on this page. You're looking at everything
		// that belongs to you, hence you can see all of it. We will filter by group if
		// desired.
	
	
		$sql_options  = (($star)
			? " and item_starred = 1 "
			: '');
	
		$sql_nets = '';
	
		$sql_extra = " AND `item`.`parent` IN ( SELECT `parent` FROM `item` WHERE item_thread_top = 1 $sql_options ) ";
	
		if($group) {
			$contact_str = '';
			$contacts = group_get_members($group);
			if($contacts) {
				foreach($contacts as $c) {
					if($contact_str)
						$contact_str .= ',';
					$contact_str .= "'" . $c['xchan'] . "'";
				}
			}
			else {
				$contact_str = ' 0 ';
				info( t('Privacy group is empty'));
			}
	
			$sql_extra = " AND item.parent IN ( SELECT DISTINCT parent FROM item WHERE true $sql_options AND (( author_xchan IN ( $contact_str ) OR owner_xchan in ( $contact_str )) or allow_gid like '" . protect_sprintf('%<' . dbesc($group_hash) . '>%') . "' ) and id = parent $item_normal ) ";
	
			$x = group_rec_byhash(local_channel(), $group_hash);
	
			if($x) {
				$title = replace_macros(get_markup_template("section_title.tpl"),array(
					'$title' => t('Privacy group: ') . $x['gname']
				));
			}
	
			$o = $tabs;
			$o .= $title;
			$o .= $status_editor;
	
		}
	
		elseif($cid) {
	
			$r = q("SELECT abook.*, xchan.* from abook left join xchan on abook_xchan = xchan_hash where abook_id = %d and abook_channel = %d and abook_blocked = 0 limit 1",
				intval($cid),
				intval(local_channel())
			);
			if($r) {
				$sql_extra = " AND item.parent IN ( SELECT DISTINCT parent FROM item WHERE true $sql_options AND uid = " . intval(local_channel()) . " AND ( author_xchan = '" . dbesc($r[0]['abook_xchan']) . "' or owner_xchan = '" . dbesc($r[0]['abook_xchan']) . "' ) $item_normal ) ";
				$title = replace_macros(get_markup_template("section_title.tpl"),array(
					'$title' => '<a href="' . zid($r[0]['xchan_url']) . '" ><img src="' . zid($r[0]['xchan_photo_s'])  . '" alt="' . urlencode($r[0]['xchan_name']) . '" /></a> <a href="' . zid($r[0]['xchan_url']) . '" >' . $r[0]['xchan_name'] . '</a>'
				));
				$o = $tabs;
				$o .= $title;
				$o .= $status_editor;
			}
			else {
				notice( t('Invalid connection.') . EOL);
				goaway(z_root() . '/network');
			}
		}
	
		if(x($category)) {
			$sql_extra .= protect_sprintf(term_query('item', $category, TERM_CATEGORY));
		}
		if(x($hashtags)) {
			$sql_extra .= protect_sprintf(term_query('item', $hashtags, TERM_HASHTAG, TERM_COMMUNITYTAG));
		}
	
		if(! $update) {
			// The special div is needed for liveUpdate to kick in for this page.
			// We only launch liveUpdate if you aren't filtering in some incompatible
			// way and also you aren't writing a comment (discovered in javascript).
	
			if($gid || $cid || $cmin || ($cmax != 99) || $star || $liked || $conv || $spam || $nouveau || $list)
				$firehose = 0;
	
			$maxheight = get_pconfig(local_channel(),'system','network_divmore_height');
			if(! $maxheight)
				$maxheight = 400;
	
	
			$o .= '<div id="live-network"></div>' . "\r\n";
			$o .= "<script> var profile_uid = " . local_channel() 
				. "; var profile_page = " . \App::$pager['page'] 
				. "; divmore_height = " . intval($maxheight) . "; </script>\r\n";
	
			\App::$page['htmlhead'] .= replace_macros(get_markup_template("build_query.tpl"),array(
				'$baseurl' => z_root(),
				'$pgtype'  => 'network',
				'$uid'     => ((local_channel()) ? local_channel() : '0'),
				'$gid'     => (($gid) ? $gid : '0'),
				'$cid'     => (($cid) ? $cid : '0'),
				'$cmin'    => (($cmin) ? $cmin : '0'),
				'$cmax'    => (($cmax) ? $cmax : '0'),
				'$star'    => (($star) ? $star : '0'),
				'$liked'   => (($liked) ? $liked : '0'),
				'$conv'    => (($conv) ? $conv : '0'),
				'$spam'    => (($spam) ? $spam : '0'),
				'$fh'      => (($firehose) ? $firehose : '0'),
				'$nouveau' => (($nouveau) ? $nouveau : '0'),
				'$wall'    => '0',
				'$list'    => ((x($_REQUEST,'list')) ? intval($_REQUEST['list']) : 0),
				'$page'    => ((\App::$pager['page'] != 1) ? \App::$pager['page'] : 1),
				'$search'  => (($search) ? $search : ''),
				'$order'   => $order,
				'$file'    => $file,
				'$cats'    => $category,
				'$tags'    => $hashtags,
				'$dend'    => $datequery,
				'$mid'     => '',
				'$verb'     => $verb,
				'$dbegin'  => $datequery2
			));
		}
	
		$sql_extra3 = '';
	
		if($datequery) {
			$sql_extra3 .= protect_sprintf(sprintf(" AND item.created <= '%s' ", dbesc(datetime_convert(date_default_timezone_get(),'',$datequery))));
		}
		if($datequery2) {
			$sql_extra3 .= protect_sprintf(sprintf(" AND item.created >= '%s' ", dbesc(datetime_convert(date_default_timezone_get(),'',$datequery2))));
		}
	
		$sql_extra2 = (($nouveau) ? '' : " AND item.parent = item.id ");
		$sql_extra3 = (($nouveau) ? '' : $sql_extra3);
	
		if(x($_GET,'search')) {
			$search = escape_tags($_GET['search']);
			if(strpos($search,'#') === 0) {
				$sql_extra .= term_query('item',substr($search,1),TERM_HASHTAG,TERM_COMMUNITYTAG);
			}
			else {
				$sql_extra .= sprintf(" AND item.body like '%s' ",
					dbesc(protect_sprintf('%' . $search . '%'))
				);
			}
		}
	
		if($verb) {
			$sql_extra .= sprintf(" AND item.verb like '%s' ",
				dbesc(protect_sprintf('%' . $verb . '%'))
			);
		}
	
		if(strlen($file)) {
			$sql_extra .= term_query('item',$file,TERM_FILE);
		}
	
		if($conv) {
			$sql_extra .= sprintf(" AND parent IN (SELECT distinct(parent) from item where ( author_xchan like '%s' or item_mentionsme = 1 )) ",
				dbesc(protect_sprintf($channel['channel_hash']))
			);
		}
	
		if($update && ! $load) {
	
			// only setup pagination on initial page view
			$pager_sql = '';
	
		}
		else {
			$itemspage = get_pconfig(local_channel(),'system','itemspage');
			\App::set_pager_itemspage(((intval($itemspage)) ? $itemspage : 20));
			$pager_sql = sprintf(" LIMIT %d OFFSET %d ", intval(\App::$pager['itemspage']), intval(\App::$pager['start']));
		}
	
	
		if(($cmin != 0) || ($cmax != 99)) {
	
			// Not everybody who shows up in the network stream will be in your address book.
			// By default those that aren't are assumed to have closeness = 99; but this isn't
			// recorded anywhere. So if cmax is 99, we'll open the search up to anybody in
			// the stream with a NULL address book entry.
	
			$sql_nets .= " AND ";
	
			if($cmax == 99)
				$sql_nets .= " ( ";
	
			$sql_nets .= "( abook.abook_closeness >= " . intval($cmin) . " ";
			$sql_nets .= " AND abook.abook_closeness <= " . intval($cmax) . " ) ";
	
			if($cmax == 99)
				$sql_nets .= " OR abook.abook_closeness IS NULL ) ";
	
	
		}
	
		$abook_uids = " and abook.abook_channel = " . local_channel() . " ";
	
		if($firehose && (! get_config('system','disable_discover_tab'))) {
			require_once('include/channel.php');
			$sys = get_sys_channel();
			$uids = " and item.uid  = " . intval($sys['channel_id']) . " ";
			\App::$data['firehose'] = intval($sys['channel_id']);
		}
		else {
			$uids = " and item.uid = " . local_channel() . " ";
		}
	
		if(get_pconfig(local_channel(),'system','network_list_mode'))
			$page_mode = 'list';
		else
			$page_mode = 'client';
	
		$simple_update = (($update) ? " and item_unseen = 1 " : '');
	
		// This fixes a very subtle bug so I'd better explain it. You wake up in the morning or return after a day
		// or three and look at your matrix page - after opening up your browser. The first page loads just as it
		// should. All of a sudden a few seconds later, page 2 will get inserted at the beginning of the page
		// (before the page 1 content). The update code is actually doing just what it's supposed
		// to, it's fetching posts that have the ITEM_UNSEEN bit set. But the reason that page 2 content is being
		// returned in an UPDATE is because you hadn't gotten that far yet - you're still on page 1 and everything
		// that we loaded for page 1 is now marked as seen. But the stuff on page 2 hasn't been. So... it's being
		// treated as "new fresh" content because it is unseen. We need to distinguish it somehow from content
		// which "arrived as you were reading page 1". We're going to do this
		// by storing in your session the current UTC time whenever you LOAD a network page, and only UPDATE items
		// which are both ITEM_UNSEEN and have "changed" since that time. Cross fingers...
	
		if($update && $_SESSION['loadtime'])
			$simple_update = " AND (( item_unseen = 1 AND item.changed > '" . datetime_convert('UTC','UTC',$_SESSION['loadtime']) . "' )  OR item.changed > '" . datetime_convert('UTC','UTC',$_SESSION['loadtime']) . "' ) ";
		if($load)
			$simple_update = '';
	
		if($nouveau && $load) {
			// "New Item View" - show all items unthreaded in reverse created date order
	
			$items = q("SELECT item.*, item.id AS item_id, received FROM item
				left join abook on ( item.owner_xchan = abook.abook_xchan $abook_uids )
				WHERE true $uids $item_normal
				and (abook.abook_blocked = 0 or abook.abook_flags is null)
				$simple_update
				$sql_extra $sql_nets
				ORDER BY item.received DESC $pager_sql "
			);
	
			require_once('include/items.php');
	
			xchan_query($items);
	
			$items = fetch_post_tags($items,true);
		}
		elseif($update) {
	
			// Normal conversation view
	
			if($order === 'post')
					$ordering = "created";
			else
					$ordering = "commented";
	
			if($load) {

				// Fetch a page full of parent items for this page
	
				$r = q("SELECT distinct item.id AS item_id, $ordering FROM item
					left join abook on ( item.owner_xchan = abook.abook_xchan $abook_uids )
					WHERE true $uids $item_normal
					AND item.parent = item.id
					and (abook.abook_blocked = 0 or abook.abook_flags is null)
					$sql_extra3 $sql_extra $sql_nets
					ORDER BY $ordering DESC $pager_sql "
				);
	
			}
			else {
				// this is an update
				$r = q("SELECT item.parent AS item_id FROM item
					left join abook on ( item.owner_xchan = abook.abook_xchan $abook_uids )
					WHERE true $uids $item_normal $simple_update
					and (abook.abook_blocked = 0 or abook.abook_flags is null)
					$sql_extra3 $sql_extra $sql_nets "
				);
				$_SESSION['loadtime'] = datetime_convert();
			}

			// Then fetch all the children of the parents that are on this page
			$parents_str = '';
			$update_unseen = '';
	
			if($r) {
	
				$parents_str = ids_to_querystr($r,'item_id');
	
				$items = q("SELECT item.*, item.id AS item_id FROM item
					WHERE true $uids $item_normal
					AND item.parent IN ( %s )
					$sql_extra ",
					dbesc($parents_str)
				);
	
				xchan_query($items,true,(($firehose) ? local_channel() : 0));
				$items = fetch_post_tags($items,true);
				$items = conv_sort($items,$ordering);
			}
			else {
				$items = array();
			}
	
			if($page_mode === 'list') {
	
				/**
				 * in "list mode", only mark the parent item and any like activities as "seen". 
				 * We won't distinguish between comment likes and post likes. The important thing
				 * is that the number of unseen comments will be accurate. The SQL to separate the
				 * comment likes could also get somewhat hairy. 
				 */
	
				if($parents_str) {
					$update_unseen = " AND ( id IN ( " . dbesc($parents_str) . " )";
					$update_unseen .= " OR ( parent IN ( " . dbesc($parents_str) . " ) AND verb in ( '" . dbesc(ACTIVITY_LIKE) . "','" . dbesc(ACTIVITY_DISLIKE) . "' ))) ";
				}
			}
			else {
				if($parents_str) {
					$update_unseen = " AND parent IN ( " . dbesc($parents_str) . " )";
				}
			}
		}
	
		if(($update_unseen) && (! $firehose))
			$r = q("UPDATE item SET item_unseen = 0 WHERE item_unseen = 1 AND uid = %d $update_unseen ",
				intval(local_channel())
			);
	
		$mode = (($nouveau) ? 'network-new' : 'network');
	
		$o .= conversation($a,$items,$mode,$update,$page_mode);
	
		if(($items) && (! $update))
			$o .= alt_pager($a,count($items));
	
		return $o;
	}
	
}
