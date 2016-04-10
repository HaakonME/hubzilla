<?php

namespace Zotlabs\Web;


class SessionHandler implements \SessionHandlerInterface {

	private $session_exists;
	private $session_expire;


	function open ($s, $n) {
		$this->session_exists = 0;
		$this->session_expire = 180000;
		return true;
	}

	function read ($id) {

		if(x($id))
			$r = q("SELECT `data` FROM `session` WHERE `sid`= '%s'", dbesc($id));

		if($r) {
			$this->session_exists = true;
			return $r[0]['data'];
		}

		return '';
	}


	function write ($id, $data) {

		if(! $id || ! $data) {
			return false;
		}

		// Can't just use $data here because we can't be certain of the serialisation algorithm

		if($_SESSION && array_key_exists('remember_me',$_SESSION) && intval($_SESSION['remember_me']))
			$expire = time() + (60 * 60 * 24 * 365);
		else
			$expire = time() + $this->session_expire;
		$default_expire = time() + 300;

		if($this->session_exists) {
			q("UPDATE `session`
				SET `data` = '%s', `expire` = '%s' WHERE `sid` = '%s'",
				dbesc($data),
				dbesc($expire),
				dbesc($id)
			);
		} 
		else {
			q("INSERT INTO `session` (sid, expire, data) values ('%s', '%s', '%s')",
				dbesc($id),
				dbesc($default_expire),
				dbesc($data)
			);
		}

		return true;
	}

	
	function close() {
		return true;
	}


	function destroy ($id) {
		q("DELETE FROM `session` WHERE `sid` = '%s'", dbesc($id));
		return true;
	}


	function gc($expire) {
		q("DELETE FROM session WHERE expire < %d", dbesc(time()));
		return true;
	}


}