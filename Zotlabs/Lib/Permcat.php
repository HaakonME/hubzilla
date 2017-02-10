<?php

namespace Zotlabs\Lib;

use \Zotlabs\Access as Zaccess;

class Permcat {

	private $permcats = [];

	public function __construct($channel_id) {

		$perms = [];

		// first check role perms for a perms_connect setting

		$role = get_pconfig($channel_id,'system','permissions_role');
		if($role) {
			$x = Zaccess\PermissionRoles::role_perms($role);
			if($x['perms_connect']) {
				$perms = Zaccess\Permissions::FilledPerms($x['perms_connect']);
			}
		}

		// if no role perms it may be a custom role, see if there any autoperms

		if(! $perms) {
			$perms = Zaccess\Permissions::FilledAutoPerms($channel_id);
		}

		// if no autoperms it may be a custom role with manual perms

		if(! $perms) {
			$r = q("select channel_hash from channel where channel_id = %d",
				intval($channel_id)
			);
			if($r) {
				$x = q("select * from abconfig where chan = %d and xchan = '%s' and cat = 'my_perms'",
					intval($channel_id),
					dbesc($r[0]['channel_hash'])
				);
				if($x) {
					foreach($x as $xv) {
						$perms[$xv['k']] = intval($xv['v']);
					}
				}
			}
		}

		// nothing was found - create a filled permission array where all permissions are 0

		if(! $perms) {
			$perms = Zaccess\Permissions::FilledPerms([]);
		}

		$this->permcats[] = [
			'name'      => 'default',
			'localname' => t('default','permcat'),
			'perms'     => Zaccess\Permissions::Operms($perms),
			'system'    => 1
		];


		$p = $this->load_permcats($channel_id);
		if($p) {
			for($x = 0; $x < count($p); $x++) {
				$this->permcats[] = [
					'name'      => $p[$x][0],
					'localname' => $p[$x][1],
					'perms'     => Zaccess\Permissions::Operms(Zaccess\Permissions::FilledPerms($p[$x][2])),
					'system'    => intval($p[$x][3])
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

	public function load_permcats($uid) {

		$permcats = [
			[ 'follower', t('follower','permcat'),
				[ 'view_stream','view_profile','view_contacts','view_storage','view_pages','view_wiki',
				  'post_like' ], 1
			],
			[ 'contributor', t('contributor','permcat'),
				[ 'view_stream','view_profile','view_contacts','view_storage','view_pages','view_wiki',
				  'post_wall','post_comments','write_wiki','post_like','tag_deliver','chat' ], 1
			],
			[ 'publisher', t('publisher','permcat'),
				[ 'view_stream','view_profile','view_contacts','view_storage','view_pages',
				  'write_storage','post_wall','write_pages','write_wiki','post_comments','post_like','tag_deliver',
				  'chat', 'republish' ], 1
			]
		];

		if($uid) {
			$x = q("select * from pconfig where uid = %d and cat = 'permcat'",
				intval($uid)
			);
			if($x) {
				foreach($x as $xv) {
					$value = ((preg_match('|^a:[0-9]+:{.*}$|s', $xv['v'])) ? unserialize($xv['v']) : $xv['v']);
					$permcats[] = [ $xv['k'], $xv['k'], $value, 0 ];
				}
			}
		}					

		call_hooks('permcats',$permcats);

		return $permcats;

	}

	static public function find_permcat($arr,$name) {
		if((! $arr) || (! $name))
			return false;
		foreach($arr as $p)
			if($p['name'] == $name)
				return $p['value'];
	}

	static public function update($channel_id, $name,$permarr) {
		PConfig::Set($channel_id,'permcat',$name,$permarr);
	}

	static public function delete($channel_id,$name) {
		PConfig::Delete($channel_id,'permcat',$name);
	}


}