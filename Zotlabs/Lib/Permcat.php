<?php

namespace Zotlabs\Lib;

use \Zotlabs\Access as Zaccess;

class Permcat {

	private $permcats = [];

	public function __construct($channel_id) {

		$name = 'default';
		$localname = t('default','permcat');
		
		$perms = Zaccess\Permissions::FilledAutoPerms($channel_id);
		if(! $perms) {
			$role = get_pconfig($channel_id,'system','permissions_role');
			if($role) {
				$x = Zaccess\PermissionRoles::role_perms($role);
				$perms = Zaccess\Permissions::FilledPerms($x['perms_connect']);
			}
			if(! $perms) {
				$perms = Zaccess\Permissions::FilledPerms([]);
			}
		}

		$this->permcats[] = [
			'name'      => $name,
			'localname' => $localname,
			'perms'     => Zaccess\Permissions::Operms($perms)
		];


		$p = Zaccess\PermissionRoles::permcats($channel_id);
		if($p) {
			for($x = 0; $x < count($p); $x++) {
				$this->permcats[] = [
					'name'      => $p[$x][0],
					'localname' => $p[$x][1],
					'perms'     => Zaccess\Permissions::Operms(Zaccess\Permissions::FilledPerms($p[$x][2]))
				];
			}
		}
	}


	public function listing() {
		return $this->permcats;
	}

	public function fetch($name) {
		if($name && $this->permcats) {
			foreach($this->permcats as $permcat) {
				if(strcasecmp($permcat['name'],$name) === 0) {
					return $permcat;
				}
			}
		}
		return ['error' => true];
	}

}