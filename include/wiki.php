<?php
/**
 * @file include/wiki.php
 * @brief Wiki related functions.
 */

use \Zotlabs\Storage\GitRepo as GitRepo;
define ( 'WIKI_ITEM_RESOURCE_TYPE', 'wiki' );

function wiki_list($nick, $observer_hash) {
	if (local_channel() || remote_channel()) {
		$sql_extra = item_permissions_sql(get_channel_by_nick($nick)['channel_id'], $observer_hash);
	} else {
		$sql_extra = " AND item_private = 0 ";
	}
	$wikis = q("SELECT * FROM item WHERE resource_type = '%s' AND mid = parent_mid AND item_deleted = 0 $sql_extra", 
			dbesc(WIKI_ITEM_RESOURCE_TYPE)
	);
	// TODO: query db for wikis the observer can access. Return with two lists, for read and write access
	return array('wikis' => $wikis);
}

function wiki_pages() {
	// TODO: scan wiki folder for pages
	return array('pages' => array('page1.md', 'page2.md'));
}

function wiki_init_wiki($channel, $name) {
	// Store the path as a relative path, but pass absolute path to mkdir
	$path = 'store/[data]/git/'.$channel['channel_address'].'/wiki/'.$name;
	if (!os_mkdir(__DIR__ . '/../' . $path, 0770, true)) {
		logger('Error creating wiki path: ' . $name);
		return null;
	}
	// Create GitRepo object 	
	$git = new GitRepo($channel['channel_address'], null, false, $name, __DIR__ . '/../' . $path);	
	if(!$git->initRepo()) {
		logger('Error creating new git repo in ' . $git->path);
		return null;
	}
	
	return array('path' => $path);
}

function wiki_create_wiki($channel, $observer_hash, $name, $acl) {
	$wikiinit = wiki_init_wiki($channel, $name);	
	if (!$wikiinit['path']) {
		notice('Error creating wiki');
		return array('item' => null, 'success' => false);
	}
	$path = $wikiinit['path'];
	// Generate unique resource_id using the same method as item_message_id()
	do {
		$dups = false;
		$resource_id = random_string();
		$r = q("SELECT mid FROM item WHERE resource_id = '%s' AND resource_type = '%s' AND uid = %d LIMIT 1", 
			dbesc($resource_id), 
			dbesc(WIKI_ITEM_RESOURCE_TYPE), 
			intval($channel['channel_id'])
		);
		if (count($r))
			$dups = true;
	} while ($dups == true);
	$ac = $acl->get();
	$mid = item_message_id();
	$arr = array();	// Initialize the array of parameters for the post
	$item_hidden = 0; // TODO: Allow form creator to send post to ACL about new game automatically
	$wiki_url = z_root() . '/wiki/' . $channel['channel_address'] . '/' . $name;
	$arr['aid'] = $channel['channel_account_id'];
	$arr['uid'] = $channel['channel_id'];
	$arr['mid'] = $mid;
	$arr['parent_mid'] = $mid;
	$arr['item_hidden'] = $item_hidden;
	$arr['resource_type'] = WIKI_ITEM_RESOURCE_TYPE;
	$arr['resource_id'] = $resource_id;
	$arr['owner_xchan'] = $channel['channel_hash'];
	$arr['author_xchan'] = $observer_hash;
	$arr['plink'] = z_root() . '/channel/' . $channel['channel_address'] . '/?f=&mid=' . $arr['mid'];
	$arr['llink'] = $arr['plink'];
	$arr['title'] = $name;		 // name of new wiki;
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
	$arr['object'] = array('path' => $path);
	$arr['body'] = '[table][tr][td][h1]New Wiki[/h1][/td][/tr][tr][td][zrl=' . $wiki_url . ']' . $name . '[/zrl][/td][/tr][/table]';

	$post = item_store($arr);
	$item_id = $post['item_id'];

	if ($item_id) {
		proc_run('php', "include/notifier.php", "activity", $item_id);
		return array('item' => $arr, 'success' => true);
	} else {
		return array('item' => null, 'success' => false);
	}
}

function wiki_delete_wiki($resource_id) {
		$item = q("SELECT id FROM item WHERE resource_type = '%s' AND resource_id = '%s' AND item_deleted = 0 limit 1",
            dbesc(WIKI_ITEM_RESOURCE_TYPE),
            dbesc($resource_id)
    );
    if (!$item) {
        return array('items' => null, 'success' => false);   
    } else {
        $drop = drop_item($item[0]['id'],false,DROPITEM_NORMAL,true);
				$object = json_decode($item[0]['object'], true);
				$abs_path = __DIR__ . '/../' . $object['path'];
				logger('path to delete: ' . $abs_path);
				$pathdel = true;
				//$pathdel = rrmdir($abs_path);
				
        return array('item' => $item, 'success' => (($drop === 1 && $pathdel) ? true : false));   
    }
}
