<?php

/**
 * @file include/config.php
 * @brief Arbitrary configuration storage.
 *
 * Arrays get stored as serialized strings.
 * Booleans are stored as integer 0/1.
 *

 * - <b>config</b> is used for hub specific configurations. It overrides the
 * configurations from .htconfig file. The storage is of size TEXT.
 * - <b>pconfig</b> is used for channel specific configurations and takes a
 * <i>channel_id</i> as identifier. It stores for example which features are
 * enabled per channel. The storage is of size MEDIUMTEXT.
 * @code{.php} $var = get_pconfig(local_channel(), 'category', 'key');@endcode
 * - <b>xconfig</b> is the same as pconfig, except that it uses <i>xchan</i> as
 * an identifier. This is for example for people who do not have a local account.
 * The storage is of size MEDIUMTEXT.
 * @code{.php}
 * $observer = App::get_observer_hash();
 * if ($observer) {
 *     $var = get_xconfig($observer, 'category', 'key');
 * }@endcode
 *
 * - get_config() and set_config() can also be done through the command line tool
 * @ref util/config.md "util/config"
 * - get_pconfig() and set_pconfig() can also be done through the command line tool
 * @ref util/pconfig.md "util/pconfig" and takes a channel_id as first argument. 
 *
 */


use Zotlabs\Lib as Zlib;



function load_config($family) {

	Zlib\Config::Load($family);

}

function get_config($family, $key) {

	return Zlib\Config::Get($family,$key);

}

function set_config($family, $key, $value) {

	return Zlib\Config::Set($family,$key,$value);

}

function del_config($family, $key) {

	return Zlib\Config::Delete($family,$key);

}


function load_pconfig($uid) {

	Zlib\PConfig::Load($uid);

}

function get_pconfig($uid, $family, $key, $instore = false) {

	return Zlib\PConfig::Get($uid,$family,$key,$instore = false);

}


function set_pconfig($uid, $family, $key, $value) {

	return Zlib\PConfig::Set($uid,$family,$key,$value);

}

function del_pconfig($uid, $family, $key) {

	return Zlib\PConfig::Delete($uid,$family,$key);

}



/**
 * @brief Loads a full xchan's configuration into a cached storage.
 *
 * All configuration values of the given observer hash are stored in global
 * cache which is available under the global variable App::$config[$xchan].
 *
 * @param string $xchan
 *  The observer's hash
 * @return void|false Returns false if xchan is not set
 */
function load_xconfig($xchan) {

	if(! $xchan)
		return false;

	if(! array_key_exists($xchan, App::$config))
		App::$config[$xchan] = array();

	$r = q("SELECT * FROM xconfig WHERE xchan = '%s'",
		dbesc($xchan)
	);

	if($r) {
		foreach($r as $rr) {
			$k = $rr['k'];
			$c = $rr['cat'];
			if(! array_key_exists($c, App::$config[$xchan])) {
				App::$config[$xchan][$c] = array();
				App::$config[$xchan][$c]['config_loaded'] = true;
			}
			App::$config[$xchan][$c][$k] = $rr['v'];
		}
	}
}

/**
 * @brief Get a particular observer's config variable given the category
 * name ($family) and a key.
 *
 * Get a particular observer's config value from the given category ($family)
 * and the $key from a cached storage in App::$config[$xchan].
 *
 * Returns false if not set.
 *
 * @param string $xchan
 *  The observer's hash
 * @param string $family
 *  The category of the configuration value
 * @param string $key
 *  The configuration key to query
 * @return mixed Stored $value or false if it does not exist
 */
function get_xconfig($xchan, $family, $key) {

	if(! $xchan)
		return false;

	if(! array_key_exists($xchan, App::$config))
		load_xconfig($xchan);

	if((! array_key_exists($family, App::$config[$xchan])) || (! array_key_exists($key, App::$config[$xchan][$family])))
		return false;

	return ((! is_array(App::$config[$xchan][$family][$key])) && (preg_match('|^a:[0-9]+:{.*}$|s', App::$config[$xchan][$family][$key])) 
		? unserialize(App::$config[$xchan][$family][$key])
		: App::$config[$xchan][$family][$key]
	);
}

/**
 * @brief Sets a configuration value for an observer.
 *
 * Stores a config value ($value) in the category ($family) under the key ($key)
 * for the observer's $xchan hash.
 *
 * @note Please do not store booleans - convert to 0/1 integer values!
 *
 * @param string $xchan
 *  The observer's hash
 * @param string $family
 *  The category of the configuration value
 * @param string $key
 *  The configuration key to set
 * @param string $value
 *  The value to store
 * @return mixed Stored $value or false
 */
function set_xconfig($xchan, $family, $key, $value) {

	// manage array value
	$dbvalue = ((is_array($value))  ? serialize($value) : $value);
	$dbvalue = ((is_bool($dbvalue)) ? intval($dbvalue)  : $dbvalue);

	if(get_xconfig($xchan, $family, $key) === false) {
		if(! array_key_exists($xchan, App::$config))
			App::$config[$xchan] = array();
		if(! array_key_exists($family, App::$config[$xchan]))
			App::$config[$xchan][$family] = array();

		App::$config[$xchan][$family][$key] = $value;
		$ret = q("INSERT INTO xconfig ( xchan, cat, k, v ) VALUES ( '%s', '%s', '%s', '%s' ) ",
			dbesc($xchan),
			dbesc($family),
			dbesc($key),
			dbesc($dbvalue)
		);
		if($ret)
			return $value;
		return $ret;
	}

	$ret = q("UPDATE xconfig SET v = '%s' WHERE xchan = '%s' and cat = '%s' AND k = '%s'",
		dbesc($dbvalue),
		dbesc($xchan),
		dbesc($family),
		dbesc($key)
	);

	App::$config[$xchan][$family][$key] = $value;

	if($ret)
		return $value;
	return $ret;
}

/**
 * @brief Deletes the given key from the observer's config.
 *
 * Removes the configured value from the stored cache in App::$config[$xchan]
 * and removes it from the database.
 *
 * @param string $xchan
 *  The observer's hash
 * @param string $family
 *  The category of the configuration value
 * @param string $key
 *  The configuration key to delete
 * @return mixed
 */
function del_xconfig($xchan, $family, $key) {

	$ret = false;

	if(x(App::$config[$xchan][$family], $key))
		unset(App::$config[$xchan][$family][$key]);
	$ret = q("DELETE FROM xconfig WHERE xchan = '%s' AND cat = '%s' AND k = '%s'",
		dbesc($xchan),
		dbesc($family),
		dbesc($key)
	);
	return $ret;
}


// account configuration storage is built on top of the under-utilised xconfig

function load_aconfig($account_id) {
	load_xconfig('a_' . $account_id);
}

function get_aconfig($account_id, $family, $key) {
	return get_xconfig('a_' . $account_id, $family, $key);
}

function set_aconfig($account_id, $family, $key, $value) {
	return set_xconfig('a_' . $account_id, $family, $key, $value);
}

function del_aconfig($account_id, $family, $key) {
	return del_xconfig('a_' . $account_id, $family, $key);
}


function load_abconfig($chash,$xhash) {
	$r = q("select * from abconfig where chan = '%s' and xchan = '%s'",
		dbesc($chash),
		dbesc($xhash)
	);
	return $r;
}

function get_abconfig($chash,$xhash,$family,$key) {
	$r = q("select * from abconfig where chan = '%s' and xchan = '%s' and cat = '%s' and k = '%s' limit 1",
		dbesc($chash),
		dbesc($xhash),
		dbesc($family),
		dbesc($key)		
	);
	if($r) {
		return ((preg_match('|^a:[0-9]+:{.*}$|s', $r[0]['v'])) ? unserialize($r[0]['v']) : $r[0]['v']);
	}
	return false;
}


function set_abconfig($chash,$xhash,$family,$key,$value) {

	$dbvalue = ((is_array($value))  ? serialize($value) : $value);
	$dbvalue = ((is_bool($dbvalue)) ? intval($dbvalue)  : $dbvalue);

	if(get_abconfig($chash,$xhash,$family,$key) === false) {
		$r = q("insert into abconfig ( chan, xchan, cat, k, v ) values ( '%s', '%s', '%s', '%s', '%s' ) ",
			dbesc($chash),
			dbesc($xhash),
			dbesc($family),
			dbesc($key),
			dbesc($dbvalue)		
		);
	}
	else {
		$r = q("update abconfig set v = '%s' where chan = '%s' and xchan = '%s' and cat = '%s' and k = '%s' ",
			dbesc($dbvalue),		
			dbesc($chash),
			dbesc($xhash),
			dbesc($family),
			dbesc($key)
		);
	}
	if($r)
		return $value;
	return false;
}


function del_abconfig($chash,$xhash,$family,$key) {

	$r = q("delete from abconfig where chan = '%s' and xchan = '%s' and cat = '%s' and k = '%s' ",
		dbesc($chash),
		dbesc($xhash),
		dbesc($family),
		dbesc($key)
	);

	return $r;
}






function get_iconfig(&$item, $family, $key) {

	$is_item = false;
	if(is_array($item)) {
		$is_item = true;
		if((! array_key_exists('iconfig',$item)) || (! is_array($item['iconfig'])))
			$item['iconfig'] = array();

		if(array_key_exists('item_id',$item))
			$iid = $item['item_id'];
		else
			$iid = $item['id'];
	}
	elseif(intval($item))
		$iid = $item;

	if(! $iid)
		return false;

	if(is_array($item) && array_key_exists('iconfig',$item) && is_array($item['iconfig'])) {
		foreach($item['iconfig'] as $c) {
			if($c['iid'] == $iid && $c['cat'] == $family && $c['k'] == $key)
				return $c['v'];
		}
	}
		 
	$r = q("select * from iconfig where iid = %d and cat = '%s' and k = '%s' limit 1",
		intval($iid),
		dbesc($family),
		dbesc($key)
	);
	if($r) {
		$r[0]['v'] = ((preg_match('|^a:[0-9]+:{.*}$|s',$r[0]['v'])) ? unserialize($r[0]['v']) : $r[0]['v']);
		if($is_item)
			$item['iconfig'][] = $r[0];
		return $r[0]['v'];
	}
	return false;

}

/**
 * set_iconfig(&$item, $family, $key, $value, $sharing = false);
 *
 * $item - item array or item id. If passed an array the iconfig meta information is
 *    added to the item structure (which will need to be saved with item_store eventually).
 *    If passed an id, the DB is updated, but may not be federated and/or cloned.
 * $family - namespace of meta variable
 * $key - key of meta variable
 * $value - value of meta variable
 * $sharing - boolean (default false); if true the meta information is propagated with the item
 *   to other sites/channels, mostly useful when $item is an array and has not yet been stored/delivered.
 *   If the meta information is added after delivery and you wish it to be shared, it may be necessary to 
 *   alter the item edited timestamp and invoke the delivery process on the updated item. The edited 
 *   timestamp needs to be altered in order to trigger an item_store_update() at the receiving end.
 */
 

function set_iconfig(&$item, $family, $key, $value, $sharing = false) {

	$dbvalue = ((is_array($value))  ? serialize($value) : $value);
	$dbvalue = ((is_bool($dbvalue)) ? intval($dbvalue)  : $dbvalue);

	$is_item = false;
	$idx = null;

	if(is_array($item)) {
		$is_item = true;
		if((! array_key_exists('iconfig',$item)) || (! is_array($item['iconfig'])))
			$item['iconfig'] = array();
		elseif($item['iconfig']) {
			for($x = 0; $x < count($item['iconfig']); $x ++) {
				if($item['iconfig'][$x]['cat'] == $family && $item['iconfig'][$x]['k'] == $key) {
					$idx = $x;
				}
			}
		}
		$entry = array('cat' => $family, 'k' => $key, 'v' => $value, 'sharing' => $sharing);

		if(is_null($idx))
			$item['iconfig'][] = $entry;
		else
			$item['iconfig'][$idx] = $entry;
		return $value;
	}

	if(intval($item))
		$iid = intval($item);

	if(! $iid)
		return false;

	if(get_iconfig($item, $family, $key) === false) {
		$r = q("insert into iconfig( iid, cat, k, v, sharing ) values ( %d, '%s', '%s', '%s', %d ) ",
			intval($iid),
			dbesc($family),
			dbesc($key),
			dbesc($dbvalue),
			intval($sharing)
		);
	}
	else {
		$r = q("update iconfig set v = '%s', sharing = %d where iid = %d and cat = '%s' and  k = '%s' ",
			dbesc($dbvalue),
			intval($sharing),
			intval($iid),
			dbesc($family),
			dbesc($key)
		);
	}

	if(! $r)
		return false;

	return $value;
}



function del_iconfig(&$item, $family, $key) {


	$is_item = false;
	$idx = null;

	if(is_array($item)) {
		$is_item = true;
		if(is_array($item['iconfig'])) {
			for($x = 0; $x < count($item['iconfig']); $x ++) {
				if($item['iconfig'][$x]['cat'] == $family && $item['iconfig'][$x]['k'] == $key) {
					unset($item['iconfig'][$x]);
				}
			}
		}
		return true;
	}

	if(intval($item))
		$iid = intval($item);

	if(! $iid)
		return false;

	return q("delete from iconfig where iid = %d and cat = '%s' and  k = '%s' ",
		intval($iid),
		dbesc($family),
		dbesc($key)
	);

}

