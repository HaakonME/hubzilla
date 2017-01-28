<?php /** @file */

function prune_hub_reinstalls() {

	$r = q("select site_url from site where site_type = %d",
		intval(SITE_TYPE_ZOT)
	);
	if($r) {
		foreach($r as $rr) {
			$x = q("select count(*) as t, hubloc_sitekey, max(hubloc_connected) as c from hubloc where hubloc_url = '%s' group by hubloc_sitekey order by c",
				dbesc($rr['site_url'])
			);

			// see if this url has more than one sitekey, indicating it has been re-installed.

			if(count($x) > 1) {
				
				$d1 = datetime_convert('UTC','UTC',$x[0]['c']);
				$d2 = datetime_convert('UTC','UTC','now - 3 days');

				// allow some slop period, say 3 days - just in case this is a glitch or transient occurrence
				// Then remove any hublocs pointing to the oldest entry.

				if(($d1 < $d2) && ($x[0]['hubloc_sitekey'])) {
					logger('prune_hub_reinstalls: removing dead hublocs at ' . $rr['site_url']);
					$y = q("delete from hubloc where hubloc_sitekey = '%s'",
						dbesc($x[0]['hubloc_sitekey'])
					);
				}
			}
		}
	}
}

function remove_obsolete_hublocs() {

	logger('remove_obsolete_hublocs',LOGGER_DEBUG);

	// Get rid of any hublocs which are ours but aren't valid anymore - 
	// e.g. they point to a different and perhaps transient URL that we aren't using.

	// I need to stress that this shouldn't happen. fix_system_urls() fixes hublocs
	// when it discovers the URL has changed. So it's unclear how we could end up
	// with URLs pointing to the old site name. But it happens. This may be an artifact
	// of an old bug or maybe a regression in some newer code. In any event, they
	// mess up communications and we have to take action if we find any. 

	// First make sure we have any hublocs (at all) with this URL and sitekey.
	// We don't want to perform this operation while somebody is in the process
	// of renaming their hub or installing certs.

	$r = q("select hubloc_id from hubloc where hubloc_url = '%s' and hubloc_sitekey = '%s'",
		dbesc(z_root()),
		dbesc(get_config('system','pubkey'))
	);
	if((! $r) || (! count($r)))
		return;

	$channels = array();

	// Good. We have at least one *valid* hubloc.

	// Do we have any invalid ones?

	$r = q("select hubloc_id from hubloc where hubloc_sitekey = '%s' and hubloc_url != '%s'",
		dbesc(get_config('system','pubkey')),
		dbesc(z_root())
	);
	$p = q("select hubloc_id from hubloc where hubloc_sitekey != '%s' and hubloc_url = '%s'",
		dbesc(get_config('system','pubkey')),
		dbesc(z_root())
	);
	if(is_array($r) && is_array($p))
		$r = array_merge($r,$p);

	if(! $r)
		return;

	// We've got invalid hublocs. Get rid of them.

	logger('remove_obsolete_hublocs: removing ' . count($r) . ' hublocs.');

	$interval = ((get_config('system','delivery_interval') !== false) 
			? intval(get_config('system','delivery_interval')) : 2 );

	foreach($r as $rr) {
		q("update hubloc set hubloc_deleted = 1 where hubloc_id = %d",
			intval($rr['hubloc_id'])
		);

		$x = q("select channel_id from channel where channel_hash = '%s' limit 1",
			dbesc($rr['hubloc_hash']) 
		);
		if($x) {
			Zotlabs\Daemon\Master::Summon(array('Notifier','location',$x[0]['channel_id']));
			if($interval)
				@time_sleep_until(microtime(true) + (float) $interval);
		}
	}
}


// This actually changes other structures to match the given (presumably current) hubloc primary selection

function hubloc_change_primary($hubloc) {

	if(! is_array($hubloc)) {
		logger('no hubloc');
		return false;
	}
	if(! (intval($hubloc['hubloc_primary']))) {
		logger('not primary: ' . $hubloc['hubloc_url']);
		return false;
	}

	logger('setting primary: ' . $hubloc['hubloc_url']);

	// See if there's a local channel

	$r = q("select channel_id, channel_primary from channel where channel_hash = '%s' limit 1",
		dbesc($hubloc['hubloc_hash'])
	);
	if($r) {
		if(! $r[0]['channel_primary']) {
			q("update channel set channel_primary = 1 where channel_id = %d",
				intval($r[0]['channel_id'])
			);
		}
		else {
			q("update channel set channel_primary = 0 where channel_id = %d",
				intval($r[0]['channel_id'])
			);
		}
	}

	// do we even have an xchan for this hubloc and if so is it already set as primary?

	$r = q("select * from xchan where xchan_hash = '%s' limit 1",
		dbesc($hubloc['hubloc_hash'])
	);
	if(! $r) {
		logger('xchan not found');		
		return false;
	}
	if($r[0]['xchan_addr'] === $hubloc['hubloc_addr']) {
		logger('xchan already changed');
		return false;
	}

	$url = $hubloc['hubloc_url'];
	$lwebbie = substr($hubloc['hubloc_addr'],0,strpos($hubloc['hubloc_addr'],'@'));

	$r = q("update xchan set xchan_addr = '%s', xchan_url = '%s', xchan_follow = '%s', xchan_connurl = '%s' where xchan_hash = '%s'",
		dbesc($hubloc['hubloc_addr']),
		dbesc($url . '/channel/' . $lwebbie),
		dbesc($url . '/follow?f=&url=%s'),
		dbesc($url . '/poco/' . $lwebbie),
		dbesc($hubloc['hubloc_hash'])
	);
	if(! $r)
		logger('xchan_update failed.');

	logger('primary hubloc changed.' . print_r($hubloc,true),LOGGER_DEBUG);
	return true;

}

// We use the post url to distinguish between http and https hublocs. 
// The https might be alive, and the http dead.

function hubloc_mark_as_down($posturl) {
	$r = q("update hubloc set hubloc_status = ( hubloc_status | %d ) where hubloc_callback = '%s'",
		intval(HUBLOC_OFFLINE),
		dbesc($posturl)
	);
}




function ping_site($url) {

		$ret = array('success' => false);

		$sys = get_sys_channel();

		$m = zot_build_packet($sys,'ping');
		$r = zot_zot($url . '/post',$m);
		if(! $r['success']) {
			$ret['message'] = 'no answer from ' . $url;
			return $ret;
		}
		$packet_result = json_decode($r['body'],true);
		if(! $packet_result['success']) {
			$ret['message'] = 'packet failure from ' . $url;		
			return $ret;
		}

		if($packet_result['success']) {
			$ret['success'] = true;
		}
		else {
			$ret['message'] = 'unknown error from ' . $url;
		}

		return $ret;
}
