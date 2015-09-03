<?php


function import_channel($channel) {

	if(! array_key_exists('channel_system',$channel)) {
		$channel['channel_system']  = (($channel['channel_pageflags'] & 0x1000) ? 1 : 0);
		$channel['channel_removed'] = (($channel['channel_pageflags'] & 0x8000) ? 1 : 0);
	}

	$r = q("select * from channel where (channel_guid = '%s' or channel_hash = '%s' or channel_address = '%s' ) limit 1",
		dbesc($channel['channel_guid']),
		dbesc($channel['channel_hash']),
		dbesc($channel['channel_address'])
	);

	// We should probably also verify the hash 
	
	if($r) {
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
	$channel['channel_account_id'] = get_account_id();
	$channel['channel_primary'] = (($seize) ? 1 : 0);
	
	dbesc_array($channel);

	$r = dbq("INSERT INTO channel (`" 
		. implode("`, `", array_keys($channel)) 
		. "`) VALUES ('" 
		. implode("', '", array_values($channel)) 
		. "')" 
	);

	if(! $r) {
		logger('mod_import: channel clone failed. ', print_r($channel,true));
		notice( t('Channel clone failed. Import failed.') . EOL);
		return false;
	}

	$r = q("select * from channel where channel_account_id = %d and channel_guid = '%s' limit 1",
		intval(get_account_id()),
		$channel['channel_guid']   // Already dbesc'd
	);
	if(! $r) {
		logger('mod_import: channel not found. ', print_r($channel,true));
		notice( t('Cloned channel not found. Import failed.') . EOL);
		return false;
	}
	// reset
	$channel = $r[0];

	set_default_login_identity(get_account_id(),$channel['channel_id'],false);
	logger('import step 1');
	$_SESSION['import_step'] = 1;
	ref_session_write(session_id(), serialize($_SESSION));
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

			unset($app['id']);
			unset($app['app_channel']);

			$app['app_channel'] = $channel['channel_id'];

			if($app['app_photo']) {
	            $x = import_xchan_photo($app['app_photo'],$channel['channel_hash'],true);
				$app['app_photo'] = $x[0];
			}

			dbesc_array($app);
			$r = dbq("INSERT INTO app (`" 
				. implode("`, `", array_keys($app)) 
				. "`) VALUES ('" 
				. implode("', '", array_values($app)) 
				. "')" 
			);
		}
	}
}



function sync_apps($channel,$apps) {

	if($channel && $apps) {
		foreach($apps as $app) {

           if(array_key_exists('app_deleted',$app) && $app['app_deleted'] && $app['app_id']) {
                q("delete from app where app_id = '%s' and app_channel = %d limit 1",
                    dbesc($app['app_id']),
                    intval($channel['channel_id'])
                );
                continue;
            }

			unset($app['id']);
			unset($app['app_channel']);

			if(! $app['app_created'] || $app['app_created'] === NULL_DATE)
				$app['app_created'] = datetime_convert();
			if(! $app['app_edited'] || $app['app_edited'] === NULL_DATE)
				$app['app_edited'] = datetime_convert();

			$app['app_channel'] = $channel['channel_id'];

			if($app['app_photo']) {
				$x = import_xchan_photo($app['app_photo'],$channel['channel_hash'],true);
				$app['app_photo'] = $x[0];
			}

			$exists = false;

			$x = q("select * from app where app_id = '%s' and app_channel = %d limit 1",
				dbesc($app['app_id']),
				intval($channel['channel_id'])
			);
			if($x) {
				if($x[0]['app_edited'] >= $obj['app_edited'])
					continue;
				$exists = true;
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
			}
		}
	}
}