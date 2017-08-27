<?php
namespace Zotlabs\Module;

require_once('include/channel.php');
require_once('include/conversation.php');
require_once('include/acl_selectors.php');


class Cards extends \Zotlabs\Web\Controller {

	function init() {
	
		if(argc() > 1)
			$which = argv(1);
		else
			return;
	
		profile_load($which);
	
	}
	
	function get($update = 0, $load = false) {
	
		if(observer_prohibited(true)) {
			return login();
		}

		if(! \App::$profile) {
			notice( t('Requested profile is not available.') . EOL );
			\App::$error = 404;
			return;
		}

		if(! feature_enabled(\App::$profile_uid,'cards')) {
			return;
		}

		nav_set_selected(t('Cards'));

		head_add_link([ 
			'rel'   => 'alternate',
			'type'  => 'application/json+oembed',
			'href'  => z_root() . '/oep?f=&url=' . urlencode(z_root() . '/' . \App::$query_string),
			'title' => 'oembed'
		]);


		$category = (($_REQUEST['cat']) ? escape_tags(trim($_REQUEST['cat'])) : '');
					
		if($category) {
			$sql_extra2 .= protect_sprintf(term_item_parent_query(\App::$profile['profile_uid'],'item', $category, TERM_CATEGORY));
		}


		$which = argv(1);
		
		$selected_card = ((argc() > 2) ? argv(2) : '');

		$_SESSION['return_url'] = \App::$query_string;
	
		$uid      = local_channel();
		$owner    = \App::$profile_uid;
		$observer = \App::get_observer();
	
		$ob_hash = (($observer) ? $observer['xchan_hash'] : '');
		
		if(! perm_is_allowed($owner,$ob_hash,'view_pages')) {
			notice( t('Permission denied.') . EOL);
			return;
		}
		
		$is_owner = ($uid && $uid == $owner);
	
		$channel = channelx_by_n($owner);

		if($channel) {
			$channel_acl = array(
				'allow_cid' => $channel['channel_allow_cid'],
				'allow_gid' => $channel['channel_allow_gid'],
				'deny_cid'  => $channel['channel_deny_cid'],
				'deny_gid'  => $channel['channel_deny_gid']
			);
		}
		else {
			$channel_acl = [ 'allow_cid' => '', 'allow_gid' => '', 'deny_cid' => '', 'deny_gid' => '' ];
		}
	


		if(perm_is_allowed($owner,$ob_hash,'write_pages')) {

			$x = [
				'webpage'           => ITEM_TYPE_CARD,
				'is_owner'          => true,
				'content_label'     => t('Add Card'),
				'button'            => t('Create'),
				'nickname'          => $channel['channel_address'],
				'lockstate'         => (($channel['channel_allow_cid'] || $channel['channel_allow_gid'] 
					|| $channel['channel_deny_cid'] || $channel['channel_deny_gid']) ? 'lock' : 'unlock'),
				'acl'               => (($is_owner) ? populate_acl($channel_acl, false, 
					\Zotlabs\Lib\PermissionDescription::fromGlobalPermission('view_pages')) : ''),
				'permissions'       => $channel_acl,
				'showacl'           => (($is_owner) ? true : false),
				'visitor'           => true,
				'hide_location'     => false,
				'hide_voting'       => false,
				'profile_uid'       => intval($owner),
				'mimetype'          => 'text/bbcode',
				'mimeselect'        => false,
				'layoutselect'      => false,
				'expanded'          => false,
				'novoting'          => false,
				'catsenabled'       => feature_enabled($owner,'categories'),
				'bbco_autocomplete' => 'bbcode',
				'bbcode'            => true
			];

			if($_REQUEST['title'])
				$x['title'] = $_REQUEST['title'];
			if($_REQUEST['body'])
				$x['body'] = $_REQUEST['body'];
			$editor = status_editor($a,$x);

		}
		else {
			$editor = '';
		}
		
		
		$sql_extra = item_permissions_sql($owner);

		if($selected_card) {
			$r = q("select * from iconfig where iconfig.cat = 'system' and iconfig.k = 'CARD' and iconfig.v = '%s' limit 1",
				dbesc($selected_card)
			);
			if($r) {
				$sql_extra .= "and item.id = " . intval($r[0]['iid']) . " ";
			}
		}
				
		$r = q("select * from item 
			where item.uid = %d and item_type = %d 
			$sql_extra order by item.created desc",
			intval($owner),
			intval(ITEM_TYPE_CARD)
		);

		$item_normal = " and item.item_hidden = 0 and item.item_type in (0,6) and item.item_deleted = 0
			and item.item_unpublished = 0 and item.item_delayed = 0 and item.item_pending_remove = 0
			and item.item_blocked = 0 ";

		if($r) {

			$parents_str = ids_to_querystr($r,'id');

			$items = q("SELECT item.*, item.id AS item_id
				FROM item
				WHERE item.uid = %d $item_normal
				AND item.parent IN ( %s )
				$sql_extra $sql_extra2 ",
				intval(\App::$profile['profile_uid']),
				dbesc($parents_str)
			);
			if($items) {
				xchan_query($items);
				$items = fetch_post_tags($items, true);
				$items = conv_sort($items,'updated');
			}
			else
				$items = [];
		}

		$mode = 'cards';
			
     	$content = conversation($items,$mode,false,'traditional');

		$o = replace_macros(get_markup_template('cards.tpl'), [
			'$title' => t('Cards'),
			'$editor' => $editor,
			'$content' => $content,
			'$pager' => alt_pager($a,count($items))
		]);

        return $o;
    }

}
