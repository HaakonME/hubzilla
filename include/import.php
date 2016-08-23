<?php

require_once('include/menu.php');
require_once('include/perm_upgrade.php');

function import_channel($channel, $account_id, $seize) {

	if(! array_key_exists('channel_system',$channel)) {
		$channel['channel_system']  = (($channel['channel_pageflags'] & 0x1000) ? 1 : 0);
		$channel['channel_removed'] = (($channel['channel_pageflags'] & 0x8000) ? 1 : 0);
	}

	// Ignore the hash provided and re-calculate

	$channel['channel_hash'] = make_xchan_hash($channel['channel_guid'],$channel['channel_guid_sig']);

	// Check for duplicate channels

	$r = q("select * from channel where (channel_guid = '%s' or channel_hash = '%s' or channel_address = '%s' ) limit 1",
		dbesc($channel['channel_guid']),
		dbesc($channel['channel_hash']),
		dbesc($channel['channel_address'])
	);
	if($r && $r[0]['channel_guid'] == $channel['channel_guid'] && $r[0]['channel_pubkey'] === $channel['channel_pubkey'] && $r[0]['channel_hash'] === $channel['channel_hash'])
		return $r[0];

	if(($r) || (check_webbie(array($channel['channel_address'])) !== $channel['channel_address'])) {
		if($r[0]['channel_guid'] === $channel['channel_guid'] || $r[0]['channel_hash'] === $channel['channel_hash']) {
			logger('mod_import: duplicate channel. ', print_r($channel,true));
			notice( t('Cannot create a duplicate channel identifier on this system. Import failed.') . EOL);
			return false;
		}
		else {
			// try at most ten times to generate a unique address.
			$x = 0;
			$found_unique = false;
			do {
				$tmp = $channel['channel_address'] . mt_rand(1000,9999);
				$r = q("select * from channel where channel_address = '%s' limit 1",
					dbesc($tmp)
				);
				if(! $r) {
					$channel['channel_address'] = $tmp;
					$found_unique = true;
					break;
				}
				$x ++;
			} while ($x < 10);
			if(! $found_unique) {
				logger('mod_import: duplicate channel. randomisation failed.', print_r($channel,true));
				notice( t('Unable to create a unique channel address. Import failed.') . EOL);
				return false;
			}
		}		
	}

	unset($channel['channel_id']);
	$channel['channel_account_id'] = $account_id;
	$channel['channel_primary'] = (($seize) ? 1 : 0);

	if($channel['channel_pageflags'] & PAGE_ALLOWCODE) {
		if(! is_site_admin())
			$channel['channel_pageflags'] = $channel['channel_pageflags'] ^ PAGE_ALLOWCODE;
	}

	// remove all the permissions related settings, we will import/upgrade them after the channel
	// is created.

	$disallowed = [ 
		'channel_id',         'channel_r_stream',    'channel_r_profile', 'channel_r_abook', 
		'channel_r_storage',  'channel_r_pages',     'channel_w_stream',  'channel_w_wall', 
		'channel_w_comment',  'channel_w_mail',      'channel_w_like',    'channel_w_tagwall', 
		'channel_w_chat',     'channel_w_storage',   'channel_w_pages',   'channel_a_republish', 
		'channel_a_delegate', 'perm_limits' 
	];

	$clean = array();
	foreach($channel as $k => $v) {
		if(in_array($k,$disallowed))
			continue;
		$clean[$k] = $v;
	}

	if($clean) {
		dbesc_array($clean);

		$r = dbq("INSERT INTO channel (`" 
			. implode("`, `", array_keys($clean)) 
			. "`) VALUES ('" 
			. implode("', '", array_values($clean)) 
			. "')" 
		);
	}

	if(! $r) {
		logger('mod_import: channel clone failed. ', print_r($channel,true));
		notice( t('Channel clone failed. Import failed.') . EOL);
		return false;
	}

	$r = q("select * from channel where channel_account_id = %d and channel_guid = '%s' limit 1",
		intval($account_id),
		$channel['channel_guid']   // Already dbesc'd
	);
	if(! $r) {
		logger('mod_import: channel not found. ', print_r($channel,true));
		notice( t('Cloned channel not found. Import failed.') . EOL);
		return false;
	}

	// extract the permissions from the original imported array and use our new channel_id to set them
	// These could be in the old channel permission stule or the new pconfig. We have a function to
	// translate and store them no matter which they throw at us.

	$channel['channel_id'] = $r[0]['channel_id'];
	translate_channel_perms_inbound($channel);

	// reset
	$channel = $r[0];

	set_default_login_identity($account_id,$channel['channel_id'],false);
	logger('import step 1');
	$_SESSION['import_step'] = 1;
	return $channel;	

}

function import_config($channel,$configs) {

	if($channel && $configs) {
		foreach($configs as $config) {
			unset($config['id']);
			$config['uid'] = $channel['channel_id'];
			dbesc_array($config);
			$r = dbq("INSERT INTO pconfig (`" 
				. implode("`, `", array_keys($config)) 
				. "`) VALUES ('" 
				. implode("', '", array_values($config)) 
				. "')" );
		}
		load_pconfig($channel['channel_id']);
	}	
}


function import_profiles($channel,$profiles) {

	if($channel && $profiles) {
		foreach($profiles as $profile) {
			unset($profile['id']);
			$profile['aid'] = get_account_id();
			$profile['uid'] = $channel['channel_id'];

			convert_oldfields($profile,'name','fullname');
			convert_oldfields($profile,'with','partner');
			convert_oldfields($profile,'work','employment');


			// we are going to reset all profile photos to the original
			// somebody will have to fix this later and put all the applicable photos into the export
	
			$profile['photo'] = z_root() . '/photo/profile/l/' . $channel['channel_id'];
			$profile['thumb'] = z_root() . '/photo/profile/m/' . $channel['channel_id'];

			dbesc_array($profile);
			$r = dbq("INSERT INTO profile (`" 
				. implode("`, `", array_keys($profile)) 
				. "`) VALUES ('" 
				. implode("', '", array_values($profile)) 
				. "')" 
			);
		}
	}
}


function import_hublocs($channel,$hublocs,$seize) {

	if($channel && $hublocs) {
		foreach($hublocs as $hubloc) {

			$hash = make_xchan_hash($hubloc['hubloc_guid'],$hubloc['hubloc_guid_sig']);
			if($hubloc['hubloc_network'] === 'zot' && $hash !== $hubloc['hubloc_hash']) {
				logger('forged hubloc: ' . print_r($hubloc,true));
				continue;
			}

			if(! array_key_exists('hubloc_primary',$hubloc)) {
				$hubloc['hubloc_primary'] = (($hubloc['hubloc_flags'] & 0x0001) ? 1 : 0);
				$hubloc['hubloc_orphancheck'] = (($hubloc['hubloc_flags'] & 0x0004) ? 1 : 0);
				$hubloc['hubloc_error'] = (($hubloc['hubloc_status'] & 0x0003) ? 1 : 0);
				$hubloc['hubloc_deleted'] = (($hubloc['hubloc_flags'] & 0x1000) ? 1 : 0);
			}

			$arr = array(
				'guid' => $hubloc['hubloc_guid'],
				'guid_sig' => $hubloc['hubloc_guid_sig'],
				'url' => $hubloc['hubloc_url'],
				'url_sig' => $hubloc['hubloc_url_sig']
			);
			if(($hubloc['hubloc_hash'] === $channel['channel_hash']) && intval($hubloc['hubloc_primary']) && ($seize))
				$hubloc['hubloc_primary'] = 0;

			if(! zot_gethub($arr)) {				
				unset($hubloc['hubloc_id']);
				dbesc_array($hubloc);
		
				$r = dbq("INSERT INTO hubloc (`" 
					. implode("`, `", array_keys($hubloc)) 
					. "`) VALUES ('" 
					. implode("', '", array_values($hubloc)) 
					. "')" 
				);
			}
		}
	}
}



function import_objs($channel,$objs) {

	if($channel && $objs) {
		foreach($objs as $obj) {

			// if it's the old term format - too hard to support
			if(! $obj['obj_created'])
				continue;

			$baseurl = $obj['obj_baseurl'];
			unset($obj['obj_id']);
			unset($obj['obj_baseurl']);

			$obj['obj_channel'] = $channel['channel_id'];

			if($baseurl && (strpos($obj['obj_url'],$baseurl . '/thing/') !== false)) {
				$obj['obj_url'] = str_replace($baseurl,z_root(),$obj['obj_url']);
			}

			if($obj['obj_imgurl']) {
	            $x = import_xchan_photo($obj['obj_imgurl'],$channel['channel_hash'],true);
				$obj['obj_imgurl'] = $x[0];
			}

			dbesc_array($obj);

			$r = dbq("INSERT INTO obj (`" 
				. implode("`, `", array_keys($obj)) 
				. "`) VALUES ('" 
				. implode("', '", array_values($obj)) 
				. "')" 
			);
		}
	}
}

function sync_objs($channel,$objs) {

	if($channel && $objs) {
		foreach($objs as $obj) {

			if(array_key_exists('obj_deleted',$obj) && $obj['obj_deleted'] && $obj['obj_obj']) {
				q("delete from obj where obj_obj = '%s' and obj_channel = %d limit 1",
					dbesc($obj['obj_obj']),
					intval($channel['channel_id'])
				);
				continue;
			}

			// if it's the old term format - too hard to support
			if(! $obj['obj_created'])
				continue;

			$baseurl = $obj['obj_baseurl'];
			unset($obj['obj_id']);
			unset($obj['obj_baseurl']);

			$obj['obj_channel'] = $channel['channel_id'];

			if($baseurl && (strpos($obj['obj_url'],$baseurl . '/thing/') !== false)) {
				$obj['obj_url'] = str_replace($baseurl,z_root(),$obj['obj_url']);
			}

			$exists = false;

			$x = q("select * from obj where obj_obj = '%s' and obj_channel = %d limit 1",
				dbesc($obj['obj_obj']),
				intval($channel['channel_id'])
			);
			if($x) {
				if($x[0]['obj_edited'] >= $obj['obj_edited'])
					continue;

				$exists = true;
			}

			if($obj['obj_imgurl']) {
	            $x = import_xchan_photo($obj['obj_imgurl'],$channel['channel_hash'],true);
				$obj['obj_imgurl'] = $x[0];
			}

			$hash = $obj['obj_obj'];
			
			if($exists) {
				unset($obj['obj_obj']);
				foreach($obj as $k => $v) {
					$r = q("UPDATE obj SET `%s` = '%s' WHERE obj_obj = '%s' AND obj_channel = %d",
						dbesc($k),
						dbesc($v),
						dbesc($hash),
						intval($channel['channel_id'])
					);
				}
			}
			else {						

				dbesc_array($obj);

				$r = dbq("INSERT INTO obj (`" 
					. implode("`, `", array_keys($obj)) 
					. "`) VALUES ('" 
					. implode("', '", array_values($obj)) 
					. "')" 
				);
			}
		}
	}
}





function import_apps($channel,$apps) {

	if($channel && $apps) {
		foreach($apps as $app) {

			$term = ((array_key_exists('term',$app) && is_array($app['term'])) ? $app['term'] : null); 

			unset($app['id']);
			unset($app['app_channel']);
			unset($app['term']);

			$app['app_channel'] = $channel['channel_id'];

			if($app['app_photo']) {
	            $x = import_xchan_photo($app['app_photo'],$channel['channel_hash'],true);
				$app['app_photo'] = $x[0];
			}

			$hash = $app['app_id'];

			dbesc_array($app);
			$r = dbq("INSERT INTO app (`" 
				. implode("`, `", array_keys($app)) 
				. "`) VALUES ('" 
				. implode("', '", array_values($app)) 
				. "')" 
			);

			if($term) {
				$x = q("select * from app where app_id = '%s' and app_channel = %d limit 1",
					dbesc($hash),
					intval($channel['channel_id'])
				);
				if($x) {
					foreach($term as $t) {
						if(array_key_exists('type',$t))
							$t['ttype'] = $t['type'];
						store_item_tag($channel['channel_id'],$x[0]['id'],TERM_OBJ_APP,$t['ttype'],escape_tags($t['term']),escape_tags($t['url']));
					}
				}
			}



		}
	}
}



function sync_apps($channel,$apps) {

	if($channel && $apps) {
		foreach($apps as $app) {

			$exists = false;
			$term = ((array_key_exists('term',$app)) ? $app['term'] : null);

			$x = q("select * from app where app_id = '%s' and app_channel = %d limit 1",
				dbesc($app['app_id']),
				intval($channel['channel_id'])
			);
			if($x) {
				$exists = $x[0];
			}
			
			if(array_key_exists('app_deleted',$app) && $app['app_deleted'] && $app['app_id']) {
                q("delete from app where app_id = '%s' and app_channel = %d limit 1",
                    dbesc($app['app_id']),
                    intval($channel['channel_id'])
                );
				if($exists) {
					q("delete from term where otype = %d and oid = %d",
						intval(TERM_OBJ_APP),
						intval($exists['id'])
            		);
				}
                continue;
            }

			unset($app['id']);
			unset($app['app_channel']);
			unset($app['term']);

			if($exists) {
				q("delete from term where otype = %d and oid = %d",
					intval(TERM_OBJ_APP),
					intval($exists['id'])
            	);
			}

			if(! $app['app_created'] || $app['app_created'] === NULL_DATE)
				$app['app_created'] = datetime_convert();
			if(! $app['app_edited'] || $app['app_edited'] === NULL_DATE)
				$app['app_edited'] = datetime_convert();

			$app['app_channel'] = $channel['channel_id'];

			if($app['app_photo']) {
				$x = import_xchan_photo($app['app_photo'],$channel['channel_hash'],true);
				$app['app_photo'] = $x[0];
			}

			if($exists && $term) {
				foreach($term as $t) {
					if(array_key_exists('type',$t))
						$t['ttype'] = $t['type'];
					store_item_tag($channel['channel_id'],$exists['id'],TERM_OBJ_APP,$t['ttype'],escape_tags($t['term']),escape_tags($t['url']));
				}
			}

			if($exists) {
				if($exists['app_edited'] >= $app['app_edited'])
					continue;
			}
			$hash = $app['app_id'];

			if($exists) {
				unset($app['app_id']);
				foreach($app as $k => $v) {
					$r = q("UPDATE app SET `%s` = '%s' WHERE app_id = '%s' AND app_channel = %d",
						dbesc($k),
						dbesc($v),
						dbesc($hash),
						intval($channel['channel_id'])
					);
				}
			}
			else {
				dbesc_array($app);
				$r = dbq("INSERT INTO app (`" 
					. implode("`, `", array_keys($app)) 
					. "`) VALUES ('" 
					. implode("', '", array_values($app)) 
					. "')" 
				);
				if($term) {
					$x = q("select * from app where app_id = '%s' and app_channel = %d limit 1",
						dbesc($hash),
						intval($channel['channel_id'])
					);
					if($x) {
						foreach($term as $t) {
							if(array_key_exists('type',$t))
								$t['ttype'] = $t['type'];
							store_item_tag($channel['channel_id'],$x[0]['id'],TERM_OBJ_APP,$t['ttype'],escape_tags($t['term']),escape_tags($t['url']));
						}
					}
				}
			}
		}
	}
}



function import_chatrooms($channel,$chatrooms) {

	if($channel && $chatrooms) {
		foreach($chatrooms as $chatroom) {

			if(! $chatroom['cr_name'])
				continue;

			unset($chatroom['cr_id']);
			unset($chatroom['cr_aid']);
			unset($chatroom['cr_uid']);

			$chatroom['cr_aid'] = $channel['channel_account_id'];
			$chatroom['cr_uid'] = $channel['channel_id'];

			dbesc_array($chatroom);
			$r = dbq("INSERT INTO chatroom (`" 
				. implode("`, `", array_keys($chatroom)) 
				. "`) VALUES ('" 
				. implode("', '", array_values($chatroom)) 
				. "')" 
			);
		}
	}
}



function sync_chatrooms($channel,$chatrooms) {

	if($channel && $chatrooms) {
		foreach($chatrooms as $chatroom) {

			if(! $chatroom['cr_name'])
				continue;

			if(array_key_exists('cr_deleted',$chatroom) && $chatroom['cr_deleted']) {
                q("delete from chatroom where cr_name = '%s' and cr_uid = %d limit 1",
                    dbesc($chatroom['cr_name']),
                    intval($channel['channel_id'])
                );
                continue;
            }


			unset($chatroom['cr_id']);
			unset($chatroom['cr_aid']);
			unset($chatroom['cr_uid']);

			if(! $chatroom['cr_created'] || $chatroom['cr_created'] === NULL_DATE)
				$chatroom['cr_created'] = datetime_convert();
			if(! $chatroom['cr_edited'] || $chatroom['cr_edited'] === NULL_DATE)
				$chatroom['cr_edited'] = datetime_convert();

			$chatroom['cr_aid'] = $channel['channel_account_id'];
			$chatroom['cr_uid'] = $channel['channel_id'];

			$exists = false;

			$x = q("select * from chatroom where cr_name = '%s' and cr_uid = %d limit 1",
				dbesc($chatroom['cr_name']),
				intval($channel['channel_id'])
			);
			if($x) {
				if($x[0]['cr_edited'] >= $chatroom['cr_edited'])
					continue;
				$exists = true;
			}
			$name = $chatroom['cr_name'];

			if($exists) {
				foreach($chatroom as $k => $v) {
					$r = q("UPDATE chatroom SET `%s` = '%s' WHERE cr_name = '%s' AND cr_uid = %d",
						dbesc($k),
						dbesc($v),
						dbesc($name),
						intval($channel['channel_id'])
					);
				}
			}
			else {
				dbesc_array($chatroom);
				$r = dbq("INSERT INTO chatroom (`" 
					. implode("`, `", array_keys($chatroom)) 
					. "`) VALUES ('" 
					. implode("', '", array_values($chatroom)) 
					. "')" 
				);
			}
		}
	}
}



function import_items($channel,$items,$sync = false,$relocate = null) {

	if($channel && $items) {
		$allow_code = false;
		$r = q("select account_id, account_roles, channel_pageflags from account left join channel on channel_account_id = account_id 
			where channel_id = %d limit 1",
			intval($channel['channel_id'])
		);
		if($r) {
			if(($r[0]['account_roles'] & ACCOUNT_ROLE_ALLOWCODE) || ($r[0]['channel_pageflags'] & PAGE_ALLOWCODE)) {
				$allow_code = true;
			}
		}

		$deliver = false;  // Don't deliver any messages or notifications when importing

		foreach($items as $i) {
			$item_result = false;
			$item = get_item_elements($i,$allow_code);
			if(! $item)
				continue;

			if($relocate && $item['mid'] === $item['parent_mid']) {
				item_url_replace($channel,$item,$relocate['url'],z_root(),$relocate['channel_address']);
			}

			$r = q("select id, edited from item where mid = '%s' and uid = %d limit 1",
				dbesc($item['mid']),
				intval($channel['channel_id'])
			);
			if($r) {

				// flags may have changed and we are probably relocating the post, 
				// so force an update even if we have the same timestamp

				if($item['edited'] >= $r[0]['edited']) {
					$item['id'] = $r[0]['id'];
					$item['uid'] = $channel['channel_id'];
					$item_result = item_store_update($item,$allow_code,$deliver);
				}	
			}
			else {
				$item['aid'] = $channel['channel_account_id'];
				$item['uid'] = $channel['channel_id'];
				$item_result = item_store($item,$allow_code,$deliver);
			}

			if($sync && $item['item_wall']) {
				// deliver singletons if we have any
				if($item_result && $item_result['success']) {
					Zotlabs\Daemon\Master::Summon( [ 'Notifier','single_activity',$item_result['item_id'] ]);
				}
			}
		}
	}
}


function sync_items($channel,$items,$relocate = null) {
	import_items($channel,$items,true,$relocate);
}



function import_item_ids($channel,$itemids) {
	if($channel && $itemids) {
		foreach($itemids as $i) {
			$r = q("select id from item where mid = '%s' and uid = %d limit 1",
				dbesc($i['mid']),
				intval($channel['channel_id'])
			);
			if(! $r)
				continue;
			$z = q("select * from iconfig where iconfig.cat = 'system' and iconfig.k = '%s' 
				and iconfig.v = '%s' and iid = %d limit 1",
				dbesc($i['service']),
				dbesc($i['sid']),
				intval($r[0]['id'])
			);
			if(! $z) {
				\Zotlabs\Lib\IConfig::Set($r[0]['id'],'system',$i['service'],$i['sid'],true);
			}
		}
	}
}

function import_events($channel,$events) {

	if($channel && $events) {
		foreach($events as $event) {
			unset($event['id']);
			$event['aid'] = $channel['channel_account_id'];
			$event['uid'] = $channel['channel_id'];
			convert_oldfields($event,'start','dtstart');
			convert_oldfields($event,'finish','dtend');
			convert_oldfields($event,'type','etype');
			convert_oldfields($event,'ignore','dismissed');

			dbesc_array($event);
			$r = dbq("INSERT INTO event (`" 
				. implode("`, `", array_keys($event)) 
				. "`) VALUES ('" 
				. implode("', '", array_values($event)) 
				. "')" 
			);
		}
	}
}


function sync_events($channel,$events) {

	if($channel && $events) {
		foreach($events as $event) {

			if((! $event['event_hash']) || (! $event['start']))
				continue;

			if($event['event_deleted']) {
				$r = q("delete from event where event_hash = '%s' and uid = %d limit 1",
					dbesc($event['event_hash']),
					intval($channel['channel_id'])
				);	
				continue;
			}

			unset($event['id']);
			$event['aid'] = $channel['channel_account_id'];
			$event['uid'] = $channel['channel_id'];

			convert_oldfields($event,'start','dtstart');
			convert_oldfields($event,'finish','dtend');
			convert_oldfields($event,'type','etype');
			convert_oldfields($event,'ignore','dismissed');


			$exists = false;

			$x = q("select * from event where event_hash = '%s' and uid = %d limit 1",
				dbesc($event['event_hash']),
				intval($channel['channel_id'])
			);
			if($x) {
				if($x[0]['edited'] >= $event['edited'])
					continue;
				$exists = true;
			}

			if($exists) {
				foreach($event as $k => $v) {
					$r = q("UPDATE event SET `%s` = '%s' WHERE event_hash = '%s' AND uid = %d",
						dbesc($k),
						dbesc($v),
						dbesc($event['event_hash']),
						intval($channel['channel_id'])
					);
				}
			}
			else {
				dbesc_array($event);
				$r = dbq("INSERT INTO event (`" 
					. implode("`, `", array_keys($event)) 
					. "`) VALUES ('" 
					. implode("', '", array_values($event)) 
					. "')" 
				);
			}
		}
	}
}


function import_menus($channel,$menus) {


	if($channel && $menus) {
		foreach($menus as $menu) {
			$m = array();
			$m['menu_channel_id'] = $channel['channel_id'];
			$m['menu_name'] = $menu['pagetitle'];
			$m['menu_desc'] = $menu['desc'];
			if($menu['created'])
				$m['menu_created'] = datetime_convert($menu['created']);
			if($menu['edited'])
				$m['menu_edited'] = datetime_convert($menu['edited']);

			$m['menu_flags'] = 0;
			if($menu['flags']) {
				if(in_array('bookmark',$menu['flags']))
					$m['menu_flags'] |= MENU_BOOKMARK;
				if(in_array('system',$menu['flags']))
					$m['menu_flags'] |= MENU_SYSTEM;

			}

			$menu_id = menu_create($m);

			if($menu_id) {
				if(is_array($menu['items'])) {
					foreach($menu['items'] as $it) {
						$mitem = array();

						$mitem['mitem_link'] = str_replace('[channelurl]',z_root() . '/channel/' . $channel['channel_address'],$it['link']);
						$mitem['mitem_link'] = str_replace('[pageurl]',z_root() . '/page/' . $channel['channel_address'],$it['link']);
						$mitem['mitem_link'] = str_replace('[cloudurl]',z_root() . '/cloud/' . $channel['channel_address'],$it['link']);
						$mitem['mitem_link'] = str_replace('[baseurl]',z_root(),$it['link']);

						$mitem['mitem_desc'] = escape_tags($it['desc']);
						$mitem['mitem_order'] = intval($it['order']);
						if(is_array($it['flags'])) {
							$mitem['mitem_flags'] = 0;
							if(in_array('zid',$it['flags']))
								$mitem['mitem_flags'] |= MENU_ITEM_ZID;
							if(in_array('new-window',$it['flags']))
								$mitem['mitem_flags'] |= MENU_ITEM_NEWWIN;
							if(in_array('chatroom',$it['flags']))
								$mitem['mitem_flags'] |= MENU_ITEM_CHATROOM;
						}
						menu_add_item($menu_id,$channel['channel_id'],$mitem);
					}
				}	
			}
		}
	}


}


function sync_menus($channel,$menus) {

	if($channel && $menus) {
		foreach($menus as $menu) {
			$m = array();
			$m['menu_channel_id'] = $channel['channel_id'];
			$m['menu_name'] = $menu['pagetitle'];
			$m['menu_desc'] = $menu['desc'];
			if($menu['created'])
				$m['menu_created'] = datetime_convert($menu['created']);
			if($menu['edited'])
				$m['menu_edited'] = datetime_convert($menu['edited']);

			$m['menu_flags'] = 0;
			if($menu['flags']) {
				if(in_array('bookmark',$menu['flags']))
					$m['menu_flags'] |= MENU_BOOKMARK;
				if(in_array('system',$menu['flags']))
					$m['menu_flags'] |= MENU_SYSTEM;

			}

			$editing = false;

			$r = q("select * from menu where menu_name = '%s' and menu_channel_id = %d limit 1",
				dbesc($m['menu_name']),
				intval($channel['channel_id'])
			);
			if($r) {
				if($r[0]['menu_edited'] >= $m['menu_edited'])
					continue;
				if($menu['menu_deleted']) {
					menu_delete_id($r[0]['menu_id'],$channel['channel_id']);
					continue;
				}
				$menu_id = $r[0]['menu_id'];
				$m['menu_id'] = $r[0]['menu_id'];
				$x = menu_edit($m);
				if(! $x)
					continue;
				$editing = true;
			}
			if(! $editing) {
				$menu_id = menu_create($m);
			}
			if($menu_id) {
				if($editing) {
					// don't try syncing - just delete all the entries and start over
					q("delete from menu_item where mitem_menu_id = %d",
						intval($menu_id)
					);
				}

				if(is_array($menu['items'])) {
					foreach($menu['items'] as $it) {
						$mitem = array();


						$mitem['mitem_link'] = str_replace('[channelurl]',z_root() . '/channel/' . $channel['channel_address'],$it['link']);
						$mitem['mitem_link'] = str_replace('[pageurl]',z_root() . '/page/' . $channel['channel_address'],$it['link']);
						$mitem['mitem_link'] = str_replace('[cloudurl]',z_root() . '/cloud/' . $channel['channel_address'],$it['link']);
						$mitem['mitem_link'] = str_replace('[baseurl]',z_root(),$it['link']);

						$mitem['mitem_desc'] = escape_tags($it['desc']);
						$mitem['mitem_order'] = intval($it['order']);
						if(is_array($it['flags'])) {
							$mitem['mitem_flags'] = 0;
							if(in_array('zid',$it['flags']))
								$mitem['mitem_flags'] |= MENU_ITEM_ZID;
							if(in_array('new-window',$it['flags']))
								$mitem['mitem_flags'] |= MENU_ITEM_NEWWIN;
							if(in_array('chatroom',$it['flags']))
								$mitem['mitem_flags'] |= MENU_ITEM_CHATROOM;
						}
						menu_add_item($menu_id,$channel['channel_id'],$mitem);
					}
				}	
			}
		}
	}
}



function import_likes($channel,$likes) {
	if($channel && $likes) {
		foreach($likes as $like) {
			if($like['deleted']) {
				q("delete from likes where liker = '%s' and likee = '%s' and verb = '%s' and target_type = '%s' and target_id = '%s'",
					dbesc($like['liker']),
					dbesc($like['likee']),
					dbesc($like['verb']),
					dbesc($like['target_type']),
					dbesc($like['target_id'])
				);
				continue;
			}
			
			unset($like['id']);
			unset($like['iid']);
			$like['channel_id'] = $channel['channel_id'];
			$r = q("select * from likes where liker = '%s' and likee = '%s' and verb = '%s' and target_type = '%s' and target_id = '%s' and i_mid = '%s'",
				dbesc($like['liker']),
				dbesc($like['likee']),
				dbesc($like['verb']),
				dbesc($like['target_type']),
				dbesc($like['target_id']),
				dbesc($like['i_mid'])
			);
			if($r)
				continue;

			dbesc_array($like);
			$r = dbq("INSERT INTO likes (`" 
				. implode("`, `", array_keys($like)) 
				. "`) VALUES ('" 
				. implode("', '", array_values($like)) 
				. "')" );
		}
	}	
}

function import_conv($channel,$convs) {
	if($channel && $convs) {
		foreach($convs as $conv) {
			if($conv['deleted']) {
				q("delete from conv where guid = '%s' and uid = %d limit 1",
					dbesc($conv['guid']),
					intval($channel['channel_id'])
				);
				continue;
			}
			
			unset($conv['id']);

			$conv['uid'] = $channel['channel_id'];
			$conv['subject'] = str_rot47(base64url_encode($conv['subject']));

			$r = q("select id from conv where guid = '%s' and uid = %d limit 1",
				dbesc($conv['guid']),
				intval($channel['channel_id'])
			);
			if($r)
				continue;

			dbesc_array($conv);
			$r = dbq("INSERT INTO conv (`" 
				. implode("`, `", array_keys($conv)) 
				. "`) VALUES ('" 
				. implode("', '", array_values($conv)) 
				. "')" );
		}
	}	
}



function import_mail($channel,$mails,$sync = false) {
	if($channel && $mails) {
		foreach($mails as $mail) {
			if(array_key_exists('flags',$mail) && in_array('deleted',$mail['flags'])) {
				q("delete from mail where mid = '%s' and uid = %d limit 1",
					dbesc($mail['message_id']),
					intval($channel['channel_id'])
				);
				continue;
			}
			if(array_key_exists('flags',$mail) && in_array('recalled',$mail['flags'])) {
				q("update mail set mail_recalled = 1 where mid = '%s' and uid = %d limit 1",
					dbesc($mail['message_id']),
					intval($channel['channel_id'])
				);
				continue;
			}

			$m = get_mail_elements($mail);
			if(! $m)
				continue;

			$m['aid'] = $channel['channel_account_id'];
			$m['uid'] = $channel['channel_id'];
			$mail_id = mail_store($m);
			if($sync && $mail_id) {
				Zotlabs\Daemon\Master::Summon(array('Notifier','single_mail',$mail_id));
			}
 		}
	}	
}

function sync_mail($channel,$mails) {
	import_mail($channel,$mails,true);
}

function sync_files($channel,$files) {

	require_once('include/attach.php');

	if($channel && $files) {
		foreach($files as $f) {
			if(! $f)
				continue;

			$fetch_url = $f['fetch_url'];
			$oldbase = dirname($fetch_url);
			$original_channel = $f['original_channel'];

			if(! ($fetch_url && $original_channel))
				continue;		

			if($f['attach']) {
				$attachment_stored = false;
				foreach($f['attach'] as $att) {

					convert_oldfields($att,'data','content');

					if($att['deleted']) {
						attach_delete($channel,$att['hash']);
						continue;
					}

					$attach_exists = false;
					$x = attach_by_hash($att['hash']);
					logger('sync_files duplicate check: attach_exists=' . $attach_exists, LOGGER_DEBUG);
					logger('sync_files duplicate check: att=' . print_r($att,true), LOGGER_DEBUG);
					logger('sync_files duplicate check: attach_by_hash() returned ' . print_r($x,true), LOGGER_DEBUG);

					if($x['success']) {
						$attach_exists = true;
						$attach_id = $x[0]['id'];
					}

					$newfname = 'store/' . $channel['channel_address'] . '/' . get_attach_binname($att['content']);

 					unset($att['id']);
					$att['aid'] = $channel['channel_account_id'];
					$att['uid'] = $channel['channel_id'];


					// check for duplicate folder names with the same parent. 
					// If we have a duplicate that doesn't match this hash value
					// change the name so that the contents won't be "covered over" 
					// by the existing directory. Use the same logic we use for 
					// duplicate files. 

					if(strpos($att['filename'],'.') !== false) {
						$basename = substr($att['filename'],0,strrpos($att['filename'],'.'));
						$ext = substr($att['filename'],strrpos($att['filename'],'.'));
					}
					else {
						$basename = $att['filename'];
						$ext = '';
					}

					$r = q("select filename from attach where ( filename = '%s' OR filename like '%s' ) and folder = '%s' and hash != '%s' ",
						dbesc($basename . $ext),
						dbesc($basename . '(%)' . $ext),
						dbesc($att['folder']),
						dbesc($att['hash'])
					);

					if($r) {
						$x = 1;

						do {
							$found = false;
							foreach($r as $rr) {
								if($rr['filename'] === $basename . '(' . $x . ')' . $ext) {
									$found = true;
									break;
								}
							}
							if($found)
								$x++;
						}			
						while($found);
						$att['filename'] = $basename . '(' . $x . ')' . $ext;
					}
					else
						$att['filename'] = $basename . $ext;

					// end duplicate detection

// @fixme - update attachment structures if they are modified rather than created

					$att['content'] = $newfname;

					// Note: we use $att['hash'] below after it has been escaped to
					// fetch the file contents. 
					// If the hash ever contains any escapable chars this could cause
					// problems. Currently it does not. 

					dbesc_array($att);


					if($attach_exists) {
						logger('sync_files attach exists: ' . print_r($att,true), LOGGER_DEBUG);
						$str = '';
    						foreach($att as $k => $v) {
				        		if($str)
            							$str .= ",";
        						$str .= " `" . $k . "` = '" . $v . "' ";
    						}
						$r = dbq("update `attach` set " . $str . " where id = " . intval($attach_id) );
					}
					else {
						logger('sync_files attach does not exists: ' . print_r($att,true), LOGGER_DEBUG);
						$r = dbq("INSERT INTO attach (`" 
							. implode("`, `", array_keys($att)) 
							. "`) VALUES ('" 
							. implode("', '", array_values($att)) 
							. "')" );
					}


					// is this a directory?

					if($att['filetype'] === 'multipart/mixed' && $att['is_dir']) {
						os_mkdir($newfname, STORAGE_DEFAULT_PERMISSIONS,true);
						$attachment_stored = true;
						continue;
					}
					else {

						// it's a file
						// for the sync version of this algorithm (as opposed to 'offline import')
						// we will fetch the actual file from the source server so it can be 
						// streamed directly to disk and avoid consuming PHP memory if it's a huge
						// audio/video file or something. 

						$time = datetime_convert();

						$parr = array('hash' => $channel['channel_hash'], 
							'time' => $time, 
							'resource' => $att['hash'],
							'revision' => 0,
							'signature' => base64url_encode(rsa_sign($channel['channel_hash'] . '.' . $time, $channel['channel_prvkey']))
						);

						$store_path = $newfname;

						$fp = fopen($newfname,'w');
						if(! $fp) {
							logger('failed to open storage file.',LOGGER_NORMAL,LOG_ERR);
							continue;
						}
						$redirects = 0;
						$x = z_post_url($fetch_url,$parr,$redirects,array('filep' => $fp));
						fclose($fp);

						if($x['success']) {
							$attachment_stored = true;
						}
						continue;
					}
				}
			}
			if(! $attachment_stored) {
				// @TODO should we queue this and retry or delete everything or what? 
				logger('attachment store failed',LOGGER_NORMAL,LOG_ERR);
			}
			if($f['photo']) {
				foreach($f['photo'] as $p) {
 					unset($p['id']);
					$p['aid'] = $channel['channel_account_id'];
					$p['uid'] = $channel['channel_id'];

					convert_oldfields($p,'data','content');
					convert_oldfields($p,'scale','imgscale');
					convert_oldfields($p,'size','filesize');
					convert_oldfields($p,'type','mimetype');

					// if this is a profile photo, undo the profile photo bit
					// for any other photo which previously held it.

					if($p['photo_usage'] == PHOTO_PROFILE) {
						$e = q("update photo set photo_usage = %d where photo_usage = %d
							and resource_id != '%s' and uid = %d ",
							intval(PHOTO_NORMAL),
							intval(PHOTO_PROFILE),
							dbesc($p['resource_id']),
							intval($channel['channel_id'])
						);
					}

					// same for cover photos

					if($p['photo_usage'] == PHOTO_COVER) {
						$e = q("update photo set photo_usage = %d where photo_usage = %d
							and resource_id != '%s' and uid = %d ",
							intval(PHOTO_NORMAL),
							intval(PHOTO_COVER),
							dbesc($p['resource_id']),
							intval($channel['channel_id'])
						);
					}

					if($p['imgscale'] === 0 && $p['os_storage'])
						$p['content'] = $store_path;
					else
						$p['content'] = base64_decode($p['content']);


					$exists = q("select * from photo where resource_id = '%s' and imgscale = %d and uid = %d limit 1",
						dbesc($p['resource_id']),
						intval($p['imgscale']),
						intval($channel['channel_id'])
					);

					dbesc_array($p);

					if($exists) {
					    $str = '';
    					foreach($p as $k => $v) {
				        	if($str)
            					$str .= ",";
        					$str .= " `" . $k . "` = '" . $v . "' ";
    					}
					    $r = dbq("update `photo` set " . $str . " where id = " . intval($exists[0]['id']) );
					}
					else {
						$r = dbq("INSERT INTO photo (`" 
							. implode("`, `", array_keys($p)) 
							. "`) VALUES ('" 
							. implode("', '", array_values($p)) 
							. "')" );
					}
				}
			}
			if($f['item']) {
				sync_items($channel,$f['item'],
					['channel_address' => $original_channel,'url' => $oldbase]
				);
			}
		}
	}
}


function convert_oldfields(&$arr,$old,$new) {
	if(array_key_exists($old,$arr)) {
		$arr[$new] = $arr[$old];
		unset($arr[$old]);
	}
}

function scan_webpage_elements($path, $type, $cloud = false) {
		$channel = \App::get_channel();
		$dirtoscan = $path;
		switch ($type) {
			case 'page':
				$dirtoscan .= '/pages/';
				$json_filename = 'page.json';
				break;
			case 'layout':
				$dirtoscan .= '/layouts/';
				$json_filename = 'layout.json';
				break;
			case 'block':
				$dirtoscan .= '/blocks/';
				$json_filename = 'block.json';
				break;
			default :
				return array();
		}
		if($cloud) {
			$dirtoscan = get_dirpath_by_cloudpath($channel, $dirtoscan);
		}
		$elements = [];
		if (is_dir($dirtoscan)) {
			$dirlist = scandir($dirtoscan);
			if ($dirlist) {
				foreach ($dirlist as $element) {
					if ($element === '.' || $element === '..') {
						continue;
					}
					$folder = $dirtoscan . '/' . $element;
					if (is_dir($folder)) {
						if($cloud) {
							$jsonfilepath = $folder . '/' . get_filename_by_cloudname($json_filename, $channel, $folder);
						} else {
							$jsonfilepath = $folder . '/' . $json_filename;
						}
						if (is_file($jsonfilepath)) {
							$metadata = json_decode(file_get_contents($jsonfilepath), true);
							if($cloud) {
								$contentfilename = get_filename_by_cloudname($metadata['contentfile'], $channel, $folder);
								$metadata['path'] = $folder . '/' . $contentfilename;
							} else {
								$contentfilename = $metadata['contentfile'];
								$metadata['path'] = $folder . '/' . $contentfilename;
							}
							if ($metadata['contentfile'] === '') {
								logger('Invalid ' . $type . ' content file');
								return false;
							}
							$content = file_get_contents($folder . '/' . $contentfilename);
							if (!$content) {
								logger('Failed to get file content for ' . $metadata['contentfile']);
								return false;
							}
							$elements[] = $metadata;
						}
					}
				}
			}
		}
		return $elements;
	}
	

	function import_webpage_element($element, $channel, $type) {
		
		$arr = array();		// construct information for the webpage element item table record
		
		switch ($type) {
			//
			//	PAGES
			//
			case 'page':
        $arr['item_type'] = ITEM_TYPE_WEBPAGE;
        $namespace = 'WEBPAGE';
				$name = $element['pagelink'];
        if($name) {
						require_once('library/urlify/URLify.php');
						$name = strtolower(\URLify::transliterate($name));
        }
				$arr['title'] = $element['title'];
        $arr['term'] = $element['term'];
				$arr['layout_mid'] = ''; // by default there is no layout associated with the page
				// If a layout was specified, find it in the database and get its info. If
        // it does not exist, leave layout_mid empty
        if($element['layout'] !== '') {
            $liid = q("select iid from iconfig where k = 'PDL' and v = '%s' and cat = 'system'",
                    dbesc($element['layout'])
            );
            if($liid) {
                $linfo = q("select mid from item where id = %d",
                        intval($liid[0]['iid'])
                );
                $arr['layout_mid'] = $linfo[0]['mid'];
            }                 
        }
				break;
			//
			//	LAYOUTS
			//
			case 'layout':
        $arr['item_type'] = ITEM_TYPE_PDL;
        $namespace = 'PDL';
				$name = $element['name'];
				$arr['title'] = $element['description'];
        $arr['term'] = $element['term'];
				break;
			//
			//	BLOCKS
			//
			case 'block':
        $arr['item_type'] = ITEM_TYPE_BLOCK;
        $namespace = 'BUILDBLOCK';
				$name = $element['name'];
				$arr['title'] = $element['title'];
				
				break;
			default :
				return null;	// return null if invalid element type
		}
		
		$arr['uid'] = $channel['channel_id'];
		$arr['aid'] = $channel['channel_account_id'];
		
	  // Check if an item already exists based on the name
		$iid = q("select iid from iconfig where k = '" . $namespace . "' and v = '%s' and cat = 'system'",
						dbesc($name)
		);
		if($iid) { // If the item does exist, get the item metadata
				$iteminfo = q("select mid,created,edited from item where id = %d",
								intval($iid[0]['iid'])
				);
				$arr['mid'] = $arr['parent_mid'] = $iteminfo[0]['mid'];
				$arr['created'] = $iteminfo[0]['created'];
				$arr['edited'] = (($element['edited']) ? datetime_convert('UTC', 'UTC', $element['edited']) : datetime_convert());
		} else { // otherwise, generate the creation times and unique id
				$arr['created'] = (($element['created']) ? datetime_convert('UTC', 'UTC', $element['created']) : datetime_convert());
				$arr['edited'] = datetime_convert('UTC', 'UTC', '0000-00-00 00:00:00');
				$arr['mid'] = $arr['parent_mid'] = item_message_id();
		}
		// Import the actual element content
		$arr['body'] = file_get_contents($element['path']);
		// The element owner is the channel importing the elements
		$arr['owner_xchan'] = get_observer_hash();
		// The author is either the owner or whomever was specified
		$arr['author_xchan'] = (($element['author_xchan']) ? $element['author_xchan'] : get_observer_hash());
		// Import mimetype if it is a valid mimetype for the element
		$mimetypes = [	'text/bbcode',
										'text/html',
										'text/markdown',
										'text/plain',
										'application/x-pdl',
										'application/x-php'	
		];
		// Blocks and pages can have any mimetype, but layouts must be text/bbcode
		if((in_array($element['mimetype'], $mimetypes))	&& ($type === 'page' || $type === 'block') ) {
				$arr['mimetype'] = $element['mimetype'];
		} else {
				$arr['mimetype'] = 'text/bbcode';
		}

		// Verify ability to use html or php!!!
		$execflag = false;
		if ($arr['mimetype'] === 'application/x-php') {
				$z = q("select account_id, account_roles, channel_pageflags from account "
					. "left join channel on channel_account_id = account_id where channel_id = %d limit 1", 
					intval(local_channel())
				);

				if ($z && (($z[0]['account_roles'] & ACCOUNT_ROLE_ALLOWCODE) || ($z[0]['channel_pageflags'] & PAGE_ALLOWCODE))) {
						$execflag = true;
				}
		}
		
		$z = q("select * from iconfig where v = '%s' and k = '%s' and cat = 'service' limit 1", 
			dbesc($name), 
			dbesc($namespace)
		);

		$i = q("select id, edited, item_deleted from item where mid = '%s' and uid = %d limit 1", 
			dbesc($arr['mid']), 
			intval(local_channel())
		);
		$remote_id = 0;
		if ($z && $i) {
				$remote_id = $z[0]['id'];
				$arr['id'] = $i[0]['id'];
				// don't update if it has the same timestamp as the original
				if ($arr['edited'] > $i[0]['edited'])
						$x = item_store_update($arr, $execflag);
		} else {
				if (($i) && (intval($i[0]['item_deleted']))) {
						// was partially deleted already, finish it off
						q("delete from item where mid = '%s' and uid = %d", 
							dbesc($arr['mid']), 
							intval(local_channel())
						);
				}
				$x = item_store($arr, $execflag);
		}
		if ($x['success']) {
				$item_id = $x['item_id'];
				update_remote_id($channel, $item_id, $arr['item_type'], $name, $namespace, $remote_id, $arr['mid']);
				$element['import_success'] = 1;
		} else {
				$element['import_success'] = 0;
		}
		
		return $element;
    
}
