<?php

namespace Zotlabs\Module;

use \Zotlabs\Access as Zaccess;

class Permcat extends \Zotlabs\Web\Controller {

	private $permcats = [];

	public function init() {
		if(! local_channel())
			return;

		$name = 'default';
		$localname = t('default','permcat');
		
		$perms = Zaccess\Permissions::FilledAutoPerms(local_channel());
		if(! $perms) {
			$role = get_pconfig(local_channel(),'system','permissions_role');
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


		$p = Zaccess\PermissionRoles::permcats(local_channel());
		if($p) {
			for($x = 0; $x < count($p); $x++) {
				$this->permcats[] = [
					'name'      => $p[$x][0],
					'localname' => $p[$x][1],
					'perms'     => Zaccess\Permissions::Operms(Zaccess\Permissions::FilledPerms($p[$x][2]))
				];
			}
		}

		if(argc() > 1 && $this->permcats) {
			foreach($this->permcats as $permcat) {
				if(strcasecmp($permcat['name'],argv(1)) === 0) {
					json_return_and_die($permcat);
				}
			}
			json_return_and_die(['error' => true]);
		}

		json_return_and_die($this->permcats);

	}

}