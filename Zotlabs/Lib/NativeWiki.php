<?php

namespace Zotlabs\Lib;

define ( 'NWIKI_ITEM_RESOURCE_TYPE', 'nwiki' );

class NativeWiki {


	static public function listwikis($channel, $observer_hash) {

		$sql_extra = item_permissions_sql($channel['channel_id'], $observer_hash);
		$wikis = q("SELECT * FROM item 
			WHERE resource_type = '%s' AND mid = parent_mid AND uid = %d AND item_deleted = 0 $sql_extra", 
			dbesc(NWIKI_ITEM_RESOURCE_TYPE),
			intval($channel['channel_id'])
		);

		if($wikis) {
			foreach($wikis as &$w) {

				$w['json_allow_cid']  = acl2json($w['allow_cid']);
				$w['json_allow_gid']  = acl2json($w['allow_gid']);
				$w['json_deny_cid']   = acl2json($w['deny_cid']);
				$w['json_deny_gid']   = acl2json($w['deny_gid']);

				$w['rawName']  = get_iconfig($w, 'wiki', 'rawName');
				$w['htmlName'] = escape_tags($w['rawName']);
				$w['urlName']  = urlencode(urlencode($w['rawName']));
				$w['mimeType'] = get_iconfig($w, 'wiki', 'mimeType');
				$w['typelock'] = get_iconfig($w, 'wiki', 'typelock');
				$w['lockstate']     = (($w['allow_cid'] || $w['allow_gid'] || $w['deny_cid'] || $w['deny_gid']) ? 'lock' : 'unlock');
			}
		}
		// TODO: query db for wikis the observer can access. Return with two lists, for read and write access
		return array('wikis' => $wikis);
	}


	function create_wiki($channel, $observer_hash, $wiki, $acl) {

		// Generate unique resource_id using the same method as item_message_id()
		do {
			$dups = false;
			$resource_id = random_string();
			$r = q("SELECT mid FROM item WHERE resource_id = '%s' AND resource_type = '%s' AND uid = %d LIMIT 1", 
				dbesc($resource_id), 
				dbesc(NWIKI_ITEM_RESOURCE_TYPE),
				intval($channel['channel_id'])
			);
			if($r)
				$dups = true;
		} while($dups == true);

		$ac = $acl->get();
		$mid = item_message_id();

		$arr = array();	// Initialize the array of parameters for the post
		$item_hidden = ((intval($wiki['postVisible']) === 0) ? 1 : 0); 
		$wiki_url = z_root() . '/wiki/' . $channel['channel_address'] . '/' . $wiki['urlName'];
		$arr['aid'] = $channel['channel_account_id'];
		$arr['uid'] = $channel['channel_id'];
		$arr['mid'] = $mid;
		$arr['parent_mid'] = $mid;
		$arr['item_hidden'] = $item_hidden;
		$arr['resource_type'] = NWIKI_ITEM_RESOURCE_TYPE;
		$arr['resource_id'] = $resource_id;
		$arr['owner_xchan'] = $channel['channel_hash'];
		$arr['author_xchan'] = $observer_hash;
		$arr['plink'] = z_root() . '/channel/' . $channel['channel_address'] . '/?f=&mid=' . urlencode($arr['mid']);
		$arr['llink'] = $arr['plink'];
		$arr['title'] = $wiki['htmlName'];  // name of new wiki;
		$arr['allow_cid'] = $ac['allow_cid'];
		$arr['allow_gid'] = $ac['allow_gid'];
		$arr['deny_cid'] = $ac['deny_cid'];
		$arr['deny_gid'] = $ac['deny_gid'];
		$arr['item_wall'] = 1;
		$arr['item_origin'] = 1;
		$arr['item_thread_top'] = 1;
		$arr['item_private'] = intval($acl->is_private());
		$arr['verb'] = ACTIVITY_CREATE;
		$arr['obj_type'] = ACTIVITY_OBJ_WIKI;
		$arr['body'] = '[table][tr][td][h1]New Wiki[/h1][/td][/tr][tr][td][zrl=' . $wiki_url . ']' . $wiki['htmlName'] . '[/zrl][/td][/tr][/table]';

		$arr['public_policy'] = map_scope(\Zotlabs\Access\PermissionLimits::Get($channel['channel_id'],'view_wiki'),true);

		// Save the wiki name information using iconfig. This is shareable.
		if(! set_iconfig($arr, 'wiki', 'rawName', $wiki['rawName'], true)) {
			return array('item' => null, 'success' => false);
		}
		if(! set_iconfig($arr, 'wiki', 'mimeType', $wiki['mimeType'], true)) {
			return array('item' => null, 'success' => false);
		}

		set_iconfig($arr,'wiki','typelock',$wiki['typelock'],true);

		$post = item_store($arr);

		$item_id = $post['item_id'];

		if($item_id) {
			\Zotlabs\Daemon\Master::Summon(array('Notifier', 'activity', $item_id));
			return array('item' => $post['item'], 'item_id' => $item_id, 'success' => true);
		}
		else {
			return array('item' => null, 'success' => false);
		}
	}

	function update_wiki($channel_id, $observer_hash, $arr, $acl) {

		$w = self::get_wiki($channel_id, $observer_hash, $arr['resource_id']);
		$item = $w['wiki'];

		if(! $item) {
			return array('item' => null, 'success' => false);
		}

		$x = $acl->get();

		$item['allow_cid']    = $x['allow_cid'];
		$item['allow_gid']    = $x['allow_gid'];
		$item['deny_cid']     = $x['deny_cid'];
		$item['deny_gid']     = $x['deny_gid'];
		$item['item_private'] = intval($acl->is_private());

		$update_title = false;

		if($item['title'] !== $arr['updateRawName']) {
			$update_title = true;
			$item['title'] = $arr['updateRawName'];
		}

		$update = item_store_update($item);

		$item_id = $update['item_id'];

		// update acl for any existing wiki pages

		q("update item set allow_cid = '%s', allow_gid = '%s', deny_cid = '%s', deny_gid = '%s', item_private = %d where resource_type = 'nwikipage' and resource_id = '%s'",
			dbesc($item['allow_cid']), 
			dbesc($item['allow_gid']), 
			dbesc($item['deny_cid']), 
			dbesc($item['deny_gid']), 
			dbesc($item['item_private']), 
			dbesc($arr['resource_id'])
		); 


		if($update['item_id']) {
			info( t('Wiki updated successfully'));
			if($update_title) {
				// Update the wiki name information using iconfig.
				if(! set_iconfig($update['item_id'], 'wiki', 'rawName', $arr['updateRawName'], true)) {
					return array('item' => null, 'success' => false);
				}
			}
			return array('item' => $update['item'], 'item_id' => $update['item_id'], 'success' => $update['success']);
		}
		else {
			return array('item' => null, 'success' => false);
		}
	}

	static public function sync_a_wiki_item($uid,$id,$resource_id) {


		$r = q("SELECT * from item WHERE uid = %d AND ( id = %d OR ( resource_type = '%s' and resource_id = '%s' )) ",
			intval($uid),
			intval($id),
			dbesc(NWIKI_ITEM_RESOURCE_TYPE),
			dbesc($resource_id)
		);
		if($r) {
			$q = q("select * from item where resource_type = 'nwikipage' and resource_id = '%s'",
				dbesc($r[0]['resource_type'])
			);
			if($q) {
				$r = array_merge($r,$q);
			}
			xchan_query($r);
			$sync_item = fetch_post_tags($r);
			build_sync_packet($uid,array('wiki' => array(encode_item($sync_item[0],true))));
		}
	}

	function delete_wiki($channel_id,$observer_hash,$resource_id) {

		$w = self::get_wiki($channel_id,$observer_hash,$resource_id);
		$item = $w['wiki'];
		if(! $item) {
			return array('item' => null, 'success' => false);
		} 
		else {
			$drop = drop_item($item['id'], false, DROPITEM_NORMAL, true);
		}

		info( t('Wiki files deleted successfully'));

		return array('item' => $item, 'item_id' => $item['id'], 'success' => (($drop === 1) ? true : false));
	}


	static public function get_wiki($channel_id, $observer_hash, $resource_id) {
		
		$sql_extra = item_permissions_sql($channel_id,$observer_hash);

		$item = q("SELECT * FROM item WHERE uid = %d AND resource_type = '%s' AND resource_id = '%s' AND item_deleted = 0 
			$sql_extra limit 1",
			intval($channel_id), 
			dbesc(NWIKI_ITEM_RESOURCE_TYPE),
			dbesc($resource_id)
		);
		if(! $item) {
			return array('wiki' => null);
		}
		else {
		
			$w = $item[0];	// wiki item table record
			// Get wiki metadata
			$rawName  = get_iconfig($w, 'wiki', 'rawName');
			$mimeType = get_iconfig($w, 'wiki', 'mimeType');
			$typelock = get_iconfig($w, 'wiki', 'typelock');

			return array(
				'wiki'     => $w,
				'rawName'  => $rawName,
				'htmlName' => escape_tags($rawName),
				'urlName'  => urlencode(urlencode($rawName)),
				'mimeType' => $mimeType,
				'typelock' => $typelock
			);
		}
	}


	static public function exists_by_name($uid, $urlName) {

		$sql_extra = item_permissions_sql($uid);		

		$item = q("SELECT item.id, resource_id FROM item left join iconfig on iconfig.iid = item.id 
			WHERE resource_type = '%s' AND iconfig.v = '%s' AND uid = %d 
			AND item_deleted = 0 $sql_extra limit 1", 
			dbesc(NWIKI_ITEM_RESOURCE_TYPE), 
			dbesc(urldecode($urlName)), 
			intval($uid)
		);

		if($item) {
			return array('id' => $item[0]['id'], 'resource_id' => $item[0]['resource_id']);
		} 
		else {
			return array('id' => null, 'resource_id' => null);
		}
	}


	static public function get_permissions($resource_id, $owner_id, $observer_hash) {
		// TODO: For now, only the owner can edit
		$sql_extra = item_permissions_sql($owner_id, $observer_hash);

		if(local_channel() && local_channel() == $owner_id) {
			return [ 'read' => true, 'write' => true, 'success' => true ];
		}

		$r = q("SELECT * FROM item WHERE uid = %d and resource_type = '%s' AND resource_id = '%s' $sql_extra LIMIT 1",
			intval($owner_id),
			dbesc(NWIKI_ITEM_RESOURCE_TYPE), 
			dbesc($resource_id)
		);

		if(! $r) {
			return array('read' => false, 'write' => false, 'success' => true);
		}
		else {
			// TODO: Create a new permission setting for wiki analogous to webpages. Until
			// then, use webpage permissions
			$write = perm_is_allowed($owner_id, $observer_hash,'write_wiki');
			return array('read' => true, 'write' => $write, 'success' => true);
		}
	}
}
