<?php
namespace Zotlabs\Module;
/**
 * @file mod/thing.php
 * @brief
 */

require_once('include/items.php');
require_once('include/security.php');
require_once('include/selectors.php');
require_once('include/acl_selectors.php');


class Thing extends \Zotlabs\Web\Controller {

	function init() {
	
		if(! local_channel())
			return;
	
		$channel    = \App::get_channel();
	
		$term_hash = (($_REQUEST['term_hash']) ? $_REQUEST['term_hash'] : '');
	
		$name = escape_tags($_REQUEST['term']);
		$verb = escape_tags($_REQUEST['verb']);
		$activity = intval($_REQUEST['activity']);
		$profile_guid = escape_tags($_REQUEST['profile_assign']);
		$url = $_REQUEST['url'];
		$photo = $_REQUEST['img'];
	
		$hash = random_string();
	
		$verbs = obj_verbs();
	
		/**
		 * verbs: [0] = first person singular, e.g. "I want", [1] = 3rd person singular, e.g. "Bill wants" 
		 * We use the first person form when creating an activity, but the third person for use in activities
		 * @FIXME There is no accounting for verb gender for languages where this is significant. We may eventually
		 * require obj_verbs() to provide full conjugations and specify which form to use in the $_REQUEST params to this module.
		 */
	
		$translated_verb = $verbs[$verb][1];
	
		/*
		 * The site administrator can do things that normals cannot.
		 * This is restricted because it will likely cause
		 * an activitystreams protocol violation and the activity might
		 * choke in some other network and result in unnecessary 
		 * support requests. It isn't because we're trying to be heavy-handed
		 * about what you can and can't do. 
		 */
	
		if(! $translated_verb) {
			if(is_site_admin())
				$translated_verb = $verb;
		}
	
		/*
		 * Things, objects: We do not provide definite (a, an) or indefinite (the) articles or singular/plural designators
		 * That needs to be specified in your thing. e.g. Mike has "a carrot", Greg wants "balls", Bob likes "the Boston Red Sox".  
		 */
	
		/*
		 * Future work on this module might produce more complex activities with targets, e.g. Phillip likes Karen's moustache
		 * and to describe other non-thing objects like channels, such as Karl wants Susan - where Susan represents a channel profile.
		 */
	 
		if((! $name) || (! $translated_verb))
			return;
	
		$acl = new \Zotlabs\Access\AccessList($channel);
	
		if(array_key_exists('contact_allow',$_REQUEST)
			|| array_key_exists('group_allow',$_REQUEST)
			|| array_key_exists('contact_deny',$_REQUEST)
			|| array_key_exists('group_deny',$_REQUEST)) {
			$acl->set_from_array($_REQUEST);
		}
	
		$x = $acl->get();
	 
		if($term_hash) {
			$t = q("select * from obj where obj_obj = '%s' and obj_channel = %d limit 1",
				dbesc($term_hash),
				intval(local_channel())
			);
			if(! $t) {
				notice( t('Item not found.') . EOL);
				return;
			}
			$orig_record = $t[0];
			if($photo != $orig_record['obj_imgurl']) {
				$arr = import_xchan_photo($photo,get_observer_hash(),true);
				$local_photo = $arr[0];
				$local_photo_type = $arr[3];
			}
			else
				$local_photo = $orig_record['obj_imgurl'];
	
			$r = q("update obj set obj_term = '%s', obj_url = '%s', obj_imgurl = '%s', obj_edited = '%s', allow_cid = '%s', allow_gid = '%s', deny_cid = '%s', deny_gid = '%s' where obj_obj = '%s' and obj_channel = %d ",
				dbesc($name),
				dbesc(($url) ? $url : z_root() . '/thing/' . $term_hash),
				dbesc($local_photo),
				dbesc(datetime_convert()),
				dbesc($x['allow_cid']),
				dbesc($x['allow_gid']),
				dbesc($x['deny_cid']),
				dbesc($x['deny_gid']),
				dbesc($term_hash),
				intval(local_channel())
			);
	
			info( t('Thing updated') . EOL);
	
			$r = q("select * from obj where obj_channel = %d and obj_obj = '%s' limit 1",
				intval(local_channel()),
				dbesc($term_hash)
			);
			if($r) {
				build_sync_packet(0, array('obj' => $r));
			}
	
			return;
		}
	
		$sql = (($profile_guid) ? " and profile_guid = '" . dbesc($profile_guid) . "' " : " and is_default = 1 ");
		$p = q("select profile_guid, is_default from profile where uid = %d $sql limit 1",
			intval(local_channel())
		);
	
		if($p)
			$profile = $p[0];
		else
			return;
	
		$local_photo = null;
	
		if($photo) {
			$arr = import_xchan_photo($photo,get_observer_hash(),true);
			$local_photo = $arr[0];
			$local_photo_type = $arr[3];
		}
	
		$created = datetime_convert();
		$url = (($url) ? $url : z_root() . '/thing/' . $hash);
	
		$r = q("insert into obj ( obj_page, obj_verb, obj_type, obj_channel, obj_obj, obj_term, obj_url, obj_imgurl, obj_created, obj_edited, allow_cid, allow_gid, deny_cid, deny_gid ) values ('%s','%s', %d, %d, '%s','%s','%s','%s','%s','%s','%s','%s','%s','%s') ",
			dbesc($profile['profile_guid']),
			dbesc($verb),
			intval(TERM_OBJ_THING),
			intval(local_channel()),
			dbesc($hash),
			dbesc($name),
			dbesc($url),
			dbesc(($photo) ? $local_photo : ''),
			dbesc($created),
			dbesc($created),
			dbesc($x['allow_cid']),
			dbesc($x['allow_gid']),
			dbesc($x['deny_cid']),
			dbesc($x['deny_gid'])
		);
	
		if(! $r) {
			notice( t('Object store: failed'));
			return;
		}
	
		info( t('Thing added'));
		
		$r = q("select * from obj where obj_channel = %d and obj_obj = '%s' limit 1",
			intval(local_channel()),
			dbesc($hash)
		);
		if($r) {
			build_sync_packet(0, array('obj' => $r));
		}
	
		if($activity) {
			$arr = array();
			$links = array(array('rel' => 'alternate','type' => 'text/html', 'href' => $url));
			if($local_photo)
				$links[] = array('rel' => 'photo', 'type' => $local_photo_type, 'href' => $local_photo);
	
			$objtype = ACTIVITY_OBJ_THING;
	
			$obj = json_encode(array(
				'type'    => $objtype,
				'id'      => $url,
				'link'    => $links,
				'title'   => $name,
				'content' => $name
			));
	
			$bodyverb = str_replace('OBJ: ', '',t('OBJ: %1$s %2$s %3$s'));
	
			$arr['owner_xchan']  = $channel['channel_hash'];
			$arr['author_xchan'] = $channel['channel_hash'];
	
			$arr['item_origin'] = 1;
			$arr['item_wall'] = 1;
			$arr['item_thread_top'] = 1;
	
			$ulink = '[zrl=' . $channel['xchan_url'] . ']' . $channel['channel_name'] . '[/zrl]';
			$plink = '[zrl=' . $url . ']' . $name . '[/zrl]';
	
			$arr['body'] =  sprintf( $bodyverb, $ulink, $translated_verb, $plink );
	
			if($local_photo)
				$arr['body'] .= "\n\n[zmg]" . $local_photo . "[/zmg]";
	
			$arr['verb'] = $verb;
			$arr['obj_type'] = $objtype;
			$arr['obj'] = $obj;
	
			if(! $profile['is_default']) {
				$arr['item_private'] = true;
				$str = '';
				$r = q("select abook_xchan from abook where abook_channel = %d and abook_profile = '%s'",
					intval(local_channel()),
					dbesc($profile_guid)
				);
				if($r) {
					$arr['allow_cid'] = '';
					foreach($r as $rr)
						$arr['allow_cid'] .= '<' . $rr['abook_xchan'] . '>';
				}
				else
					$arr['allow_cid'] = '<' . get_observer_hash() . '>';
			}
	
			$ret = post_activity_item($arr);
		}
	}
	
	
	function get() {
	
		// @FIXME one problem with things is we can't share them unless we provide the channel in the url
		// so we can definitively lookup the owner. 
	
		if(argc() == 2) {
	
			$r = q("select obj_channel from obj where obj_type = %d and obj_obj = '%s' limit 1",
				intval(TERM_OBJ_THING),
				dbesc(argv(1))
			);
			if($r) 
				$sql_extra = permissions_sql($r[0]['obj_channel']);
	
			$r = q("select * from obj where obj_type = %d and obj_obj = '%s' $sql_extra limit 1",
				intval(TERM_OBJ_THING),
				dbesc(argv(1))
			);
	
			if($r) {
				return replace_macros(get_markup_template('show_thing.tpl'), array(
					'$header' => t('Show Thing'),
					'$edit' => t('Edit'),
					'$delete' => t('Delete'),
					'$canedit' => ((local_channel() && local_channel() == $r[0]['obj_channel']) ? true : false), 
					'$thing' => $r[0] ));
			}
			else {
				notice( t('item not found.') . EOL);
				return;
			}
		}
	
		$channel = \App::get_channel();
	
		if(! (local_channel() && $channel)) {
			notice( t('Permission denied.') . EOL);
			return;
		}
	
		$acl = new \Zotlabs\Access\AccessList($channel);
		$channel_acl = $acl->get();
	
		$lockstate = (($acl->is_private()) ? 'lock' : 'unlock');
	
		$thing_hash = '';
	
		if(argc() == 3 && argv(1) === 'edit') {
			$thing_hash = argv(2);
	
			$r = q("select * from obj where obj_type = %d and obj_obj = '%s' limit 1",
				intval(TERM_OBJ_THING),
				dbesc($thing_hash)
			);
	
			if((! $r) || ($r[0]['obj_channel'] != local_channel())) {
				notice( t('Permission denied.') . EOL);
				return '';
			}
	
			$o .= replace_macros(get_markup_template('thing_edit.tpl'),array(
				'$thing_hdr' => t('Edit Thing'),
				'$multiprof' => feature_enabled(local_channel(),'multi_profiles'),
				'$profile_lbl' => t('Select a profile'),
				'$profile_select' => contact_profile_assign($r[0]['obj_page']),
				'$verb_lbl' => $channel['channel_name'],
				'$verb_select' => obj_verb_selector($r[0]['obj_verb']),
				'$activity' => array('activity',t('Post an activity'),true,t('Only sends to viewers of the applicable profile')),
				'$thing_hash' => $thing_hash,
				'$thing_lbl' => t('Name of thing e.g. something'),
				'$thething' => $r[0]['obj_term'],
				'$url_lbl' => t('URL of thing (optional)'),
				'$theurl' => $r[0]['obj_url'],
				'$img_lbl' => t('URL for photo of thing (optional)'),
				'$imgurl' => $r[0]['obj_imgurl'],
				'$permissions' => t('Permissions'),
				'$aclselect' => populate_acl($channel_acl,false),
				'$allow_cid' => acl2json($channel_acl['allow_cid']),
				'$allow_gid' => acl2json($channel_acl['allow_gid']),
				'$deny_cid' => acl2json($channel_acl['deny_cid']),
				'$deny_gid' => acl2json($channel_acl['deny_gid']),
				'$lockstate' => $lockstate,
				'$submit' => t('Submit')
			));
	
			return $o;
		}
	
		if(argc() == 3 && argv(1) === 'drop') {
			$thing_hash = argv(2);
	
			$r = q("select * from obj where obj_type = %d and obj_obj = '%s' limit 1",
				intval(TERM_OBJ_THING),
				dbesc($thing_hash)
			);
	
			if((! $r) || ($r[0]['obj_channel'] != local_channel())) {
				notice( t('Permission denied.') . EOL);
				return '';
			}
	
			$x = q("delete from obj where obj_obj = '%s' and obj_type = %d and obj_channel = %d",
				dbesc($thing_hash),
				intval(TERM_OBJ_THING),
				intval(local_channel())
			);
	
			$r[0]['obj_deleted'] = 1;
	
			build_sync_packet(0,array('obj' => $r));
	
			return $o;
		}
	
		$o .= replace_macros(get_markup_template('thing_input.tpl'),array(
			'$thing_hdr' => t('Add Thing to your Profile'),
			'$multiprof' => feature_enabled(local_channel(),'multi_profiles'),
			'$profile_lbl' => t('Select a profile'),
			'$profile_select' => contact_profile_assign(''),
			'$verb_lbl' => $channel['channel_name'],
			'$activity' => array('activity',t('Post an activity'),((array_key_exists('activity',$_REQUEST)) ? $_REQUEST['activity'] : true),t('Only sends to viewers of the applicable profile')),
			'$verb_select' => obj_verb_selector(),
			'$thing_lbl' => t('Name of thing e.g. something'),
			'$url_lbl' => t('URL of thing (optional)'),
			'$img_lbl' => t('URL for photo of thing (optional)'),
			'$permissions' => t('Permissions'),
			'$aclselect' => populate_acl($channel_acl,false),
			'$allow_cid' => acl2json($channel_acl['allow_cid']),
			'$allow_gid' => acl2json($channel_acl['allow_gid']),
			'$deny_cid' => acl2json($channel_acl['deny_cid']),
			'$deny_gid' => acl2json($channel_acl['deny_gid']),
			'$lockstate' => $lockstate,
			'$submit' => t('Submit')
		));
	
		return $o;
	}
	
}
