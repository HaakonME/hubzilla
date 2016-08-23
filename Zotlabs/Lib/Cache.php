<?php /** @file */

namespace Zotlabs\Lib;

	/**
	 *  cache api
	 */
	 
class Cache {
	public static function get($key) {

		$key = substr($key,0,254);

		$r = q("SELECT v FROM cache WHERE k = '%s' limit 1",
			dbesc($key)
		);
			
		if ($r)
			return $r[0]['v'];
		return null;
	}
		
	public static function set($key,$value) {

		$key = substr($key,0,254);

		$r = q("SELECT * FROM cache WHERE k = '%s' limit 1",
			dbesc($key)
		);
		if($r) {
			q("UPDATE cache SET v = '%s', updated = '%s' WHERE k = '%s'",
				dbesc($value),
				dbesc(datetime_convert()),
				dbesc($key));
		}
		else {
			q("INSERT INTO cache ( k, v, updated) VALUES ('%s','%s','%s')",
				dbesc($key),
				dbesc($value),
				dbesc(datetime_convert()));
		}
	}

		
	public static function clear() {
		q("DELETE FROM cache WHERE updated < '%s'",
			dbesc(datetime_convert('UTC','UTC',"now - 30 days")));			
	}
		
}
	 
