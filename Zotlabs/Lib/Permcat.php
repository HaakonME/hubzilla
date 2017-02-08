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


		$p = $this->load_permcats($channel_id);
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

	public function load_permcats($uid) {

		$permcats = [
			[ 'follower', t('follower','permcat'),
				[ 'view_stream','view_profile','view_contacts','view_storage','view_pages','view_wiki',
				  'post_like' ]
			],

			[ 'contributor', t('contributor','permcat'),
				[ 'view_stream','view_profile','view_contacts','view_storage','view_pages','view_wiki',
				  'post_wall','post_comments','write_wiki','post_like','tag_deliver','chat' ]
			],
			[ 'trusted', t('trusted','permcat'),
				[ 'view_stream','view_profile','view_contacts','view_storage','view_pages',
				  'write_storage','post_wall','write_pages','write_wiki','post_comments','post_like','tag_deliver',
				  'chat', 'republish' ]
			],
			[ 'moderator', t('moderator','permcat'),
				[ 'view_stream','view_profile','view_contacts','view_storage','view_pages',
				  'write_storage','post_wall','write_pages','write_wiki','post_comments','post_like','tag_deliver',
				  'chat', 'republish' ]
			]
		];

		if($uid) {
			$x = q("select * from pconfig where uid = %d and cat = 'permcat'",
				intval($uid)
			);
			if($x) {
				foreach($x as $xv) {
					$value = ((preg_match('|^a:[0-9]+:{.*}$|s', $xv['v'])) ? unserialize($xv['v']) : $xv['v']);
					$permcats[] = [ $xv['k'], $xv['k'], $value ];
				}
			}
		}					

		call_hooks('permcats',$permcats);

		return $permcats;

	}

	static public function update($channel_id, $name,$permarr) {
		PConfig::Set($channel_id,'permcat',$name,$permarr);
	}

	static public function delete($channel_id,$name) {
		PConfig::Delete($channel_id,'permcat',$name);
	}


}