<?php

namespace Zotlabs\Storage;

use PHPGit\Git as PHPGit;

require __DIR__ . '/../../library/PHPGit.autoload.php'; // Load PHPGit dependencies

/**
 * Description of Git
 *
 * @author Andrew Manning <andrewmanning@grid.reticu.li>
 */
class GitRepo {

	public $url = null;
	public $name = null;
	public $path = null;
	private $repoID = null;
	private $channel = null;
	private $git = null;
	private $repoBasePath = __DIR__ . '/../../store/git';

	function __construct($channel = 'sys', $name = null, $url = null, $clone = false) {
		$this->channel = $channel;
		$this->git = new PHPGit();
		if ($name) {
			$this->name = $name;
			$this->path = $this->repoBasePath . "/" . $this->channel . "/" . $this->name;
			if (file_exists($this->path)) {
				// ignore the $url input if it exists
				$this->git->setRepository($this->path);
				// TODO: get repo metadata 
			} else if ($url && validate_url($url)) {
				$this->url = $url;
				$this->repoID = random_string();
				// create the folder and clone the repo at url to that folder if $clone is true
				if ($clone) {
					if (mkdir($this->path, 0770, true)) {
						$this->git->setRepository($this->path);
						if (!$this->cloneRepo()) {
							// TODO: throw error
							logger('git clone failed: ' . json_encode($this->git));
						}
					} else {
						logger('git repo path could not be created: ' . json_encode($this->git));
					}
				}
			}
		} else {
			// Construct an empty GitRepo object 
			//$this->name = random_string(32);
			//$this->repoID = random_string();
		}
	}

	/**
	 * delete repository from disk
	 */
	public function delete() {
		return $this->delTree($this->getRepoPath());
	}

	public function getRepoPath() {
		return $this->path;
	}

	public function setRepoPath($directory) {
		if (file_exists($directory)) {
			$this->path->$directory;
			$this->git->setRepository($directory);
			return true;
		}
		return false;
	}

	public function getRepoID() {
		return $this->repoID;
	}

	public function setIdentity($user_name, $user_email) {
		// setup user for commit messages
		$this->git->config->set("user.name", $user_name, ['global' => false, 'system' => false]);
		$this->git->config->set("user.email", $user_email, ['global' => false, 'system' => false]);
	}

	public function cloneRepo() {
		if (validate_url($this->url) && file_exists($this->path)) {
			return $this->git->clone($this->url, $this->path);
		}
	}

	public static function probeRepo($dir) {
		if (!file_exists($dir)) {
			return null;
		}
		$git = new PHPGit();
		$git->setRepository($dir);
		$repo = array();
		$repo['remote'] = $git->remote();
		$repo['branches'] = $git->branch(['all' => true]);
		$repo['logs'] = $git->log(array('limit' => 50));		
		return $repo;
	}

}
