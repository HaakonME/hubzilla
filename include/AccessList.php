<?php


class AccessList {

	private $allow_cid;
	private $allow_gid;
	private $deny_cid;
	private $deny_gid;

	private $explicit;


	function _construct($channel) {

		if($channel) {		
			$this->allow_cid = $channel['channel_allow_cid'];
			$this->allow_gid = $channel['channel_allow_gid'];
			$this->deny_cid  = $channel['channel_deny_cid'];
			$this->deny_gid  = $channel['channel_deny_gid'];
		}
		else {
			$this->allow_cid = '';
			$this->allow_gid = '';
			$this->deny_cid  = '';
			$this->deny_gid  = '';
		}

		$this->explicit = false;
	}

	function get_explicit() {
		return $this->explicit;
	}

	function set($arr,$explicit = true) {
		$this->allow_cid = $arr['allow_cid'];
		$this->allow_gid = $arr['allow_gid'];
		$this->deny_cid  = $arr['deny_cid'];
		$this->deny_gid  = $arr['deny_gid'];

		$this->explicit = $explicit;
	}

	function get() {
		return array(
			'allow_cid' => $this->allow_cid,
			'allow_gid' => $this->allow_gid,
			'deny_cid'  => $this->deny_cid,
			'deny_gid'  => $this->deny_gid,
		);
	}

	function set_from_array($arr,$explicit = true) {
		$this->allow_cid = perms2str((is_array($arr['contact_allow'])) 
			? $arr['contact_allow'] : explode(',',$arr['contact_allow']));
		$this->allow_gid = perms2str((is_array($arr['group_allow']))
			? $arr['group_allow'] : explode(',',$arr['group_allow']));
		$this->deny_cid  = perms2str((is_array($arr['contact_deny']))
			? $arr['contact_deny'] : explode(',',$arr['contact_deny']));
		$this->deny_gid  = perms2str((is_array($arr['group_deny']))
			? $arr['group_deny'] : explode(',',$arr['group_deny']));

		$this->explicit = $explicit;
	}

	function is_private() {
		return (($this->allow_cid || $this->allow_gid || $this->deny_cid || $this->deny_gid) ? true : false);
	}

}

