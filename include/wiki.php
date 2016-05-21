<?php
/**
 * @file include/wiki.php
 * @brief Wiki related functions.
 */

define ( 'WIKI_ITEM_RESOURCE_TYPE', 'wiki' );

function wiki_create() {
	
}

function wiki_delete() {
	
}

function wiki_list($observer_hash) {
	// TODO: query db for wikis the observer can access. Return with two lists, for read and write access
	return array('write' => array('wiki1'), 'read' => array('wiki1', 'wiki2'));
}

function wiki_pages() {
	// TODO: scan wiki folder for pages
	return array('pages' => array('page1.md', 'page2.md'));
}

function wiki_create_wiki($channel, $name, $acl) {

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
    $arr = array();  // Initialize the array of parameters for the post
    $perms = $acl->get();
    $allow_cid = expand_acl($perms['allow_cid']);
    $players = array($channel['channel_hash'], $allow_cid[0]);
    
    $item_hidden = 0; // TODO: Allow form creator to send post to ACL about new game automatically
    $game_url = z_root() . '/chess/' . $channel['channel_address'] . '/' . $resource_id;
    $arr['aid']           = $channel['channel_account_id'];
    $arr['uid']           = $channel['channel_id'];
    $arr['mid']           = $mid;
    $arr['parent_mid']    = $mid;
    $arr['item_hidden']     = $item_hidden;
    $arr['resource_type']   = WIKI_ITEM_RESOURCE_TYPE;  
    $arr['resource_id']   = $resource_id;
    $arr['owner_xchan']     = $channel['channel_hash'];
    $arr['author_xchan']    = $channel['channel_hash'];
    // Store info about the type of chess item using the "title" field
    // Other types include 'move' for children items but may in the future include
    // additional types that will determine how the "object" field is interpreted
    $arr['title']         = $name;     
    $arr['allow_cid']       = $ac['allow_cid'];
    $arr['item_wall']       = 1;
    $arr['item_origin']     = 1;
    $arr['item_thread_top'] = 1;
    $arr['item_private']    = intval($acl->is_private());
    $arr['verb']          = ACTIVITY_CREATE;
    $arr['obj_type']      = ACTIVITY_OBJ_WIKI;
    $arr['object']        = $object;
    $arr['body']          = '[table][tr][td][h1]New Chess Game[/h1][/td][/tr][tr][td][zrl='.$game_url.']Click here to play[/zrl][/td][/tr][/table]';
    
    $post = item_store($arr);
    $item_id = $post['item_id'];

    if ($item_id) {
        proc_run('php', "include/notifier.php", "activity", $item_id);
        return array('item' => $arr, 'status' => true);
    } else {
        return array('item' => null, 'status' => false);
    }    
}