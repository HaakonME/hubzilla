<?php

namespace Zotlabs\Access;

class PermissionLimits {
	static public function Set($channel_id,$perm,$perm_limit) {
		$r = q("select * from perm_limits where channel_id = %d and perm = '%s' limit 1",
			intval($channel_id),
			dbesc($perm)
		);
		if($r) {
			if($r[0]['perm_limit'] != $perm_limit) {
				$x = q("update perm_limits set perm_limit = %d where id = %d",
					dbesc($perm_limit),
					intval($r[0]['id'])
				);
			}
		}
		else {
			$r = q("insert into perm_limits ( perm, channel_id, perm_limit ) 
				values ( '%s', %d, %d ) ",
				dbesc($perm),
				intval($channel_id),
				intval($perm_limit)
			);
		}
	}

	static public function Get($channel_id,$perm = '') {
		if($perm) {
			$r = q("select * from perm_limits where channel_id = %d and perm = '%s' limit 1",
				intval($channel_id),
				dbesc($perm)
			);
			if($r)
				return $r[0];
			return false;
		}
		else {
			return q("select * from perm_limits where channel_id = %d",
				intval($channel_id)
			);
		}
	}	

}