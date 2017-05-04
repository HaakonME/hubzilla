<?php

namespace Zotlabs\Access;

/**
 * @brief AccessList class.
 *
 * A class to hold an AccessList object with allowed and denied contacts and
 * groups.
 */
class AccessList {
	/**
	 * @brief Allow contacts
	 * @var string
	 */
	private $allow_cid;
	/**
	 * @brief Allow groups
	 * @var string
	 */
	private $allow_gid;
	/**
	 * @brief Deny contacts
	 * @var string
	 */
	private $deny_cid;
	/**
	 * @brief Deny groups
	 * @var string
	 */
	private $deny_gid;
	/**
	 * @brief Indicates if we are using the default constructor values or
	 * values that have been set explicitly.
	 * @var boolean
	 */
	private $explicit;


	/**
	 * @brief Constructor for AccessList class.
	 *
	 * @note The array to pass to the constructor is different from the array
	 * that you provide to the set() or set_from_array() functions.
	 *
	 * @param array $channel A channel array, where these entries are evaluated:
	 *   * \e string \b channel_allow_cid => string of allowed cids
	 *   * \e string \b channel_allow_gid => string of allowed gids
	 *   * \e string \b channel_deny_cid => string of denied cids
	 *   * \e string \b channel_deny_gid => string of denied gids
	 */
	function __construct($channel) {
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

	/**
	 * @brief Get if we are using the default constructor values
	 * or values that have been set explicitly.
	 *
	 * @return boolean
	 */
	function get_explicit() {
		return $this->explicit;
	}

	/**
	 * @brief Set access list from strings such as those in already
	 * existing stored data items.
	 *
	 * @note The array to pass to this set function is different from the array
	 * that you provide to the constructor or set_from_array().
	 *
	 * @param array $arr
	 *   * \e string \b allow_cid => string of allowed cids
	 *   * \e string \b allow_gid => string of allowed gids
	 *   * \e string \b deny_cid  => string of denied cids
	 *   * \e string \b deny_gid  => string of denied gids
	 * @param boolean $explicit (optional) default true
	 */
	function set($arr, $explicit = true) {
		$this->allow_cid = $arr['allow_cid'];
		$this->allow_gid = $arr['allow_gid'];
		$this->deny_cid  = $arr['deny_cid'];
		$this->deny_gid  = $arr['deny_gid'];

		$this->explicit  = $explicit;
	}

	/**
	 * @brief Return an array consisting of the current access list components
	 * where the elements are directly storable.
	 *
	 * @return Associative array with:
	 *   * \e string \b allow_cid => string of allowed cids
	 *   * \e string \b allow_gid => string of allowed gids
	 *   * \e string \b deny_cid  => string of denied cids
	 *   * \e string \b deny_gid  => string of denied gids
	 */
	function get() {
		return [
			'allow_cid' => $this->allow_cid,
			'allow_gid' => $this->allow_gid,
			'deny_cid'  => $this->deny_cid,
			'deny_gid'  => $this->deny_gid,
		];
	}

	/**
	 * @brief Set access list components from arrays, such as those provided by
	 * acl_selector().
	 *
	 * For convenience, a string (or non-array) input is assumed to be a
	 * comma-separated list and auto-converted into an array.
	 *
	 * @note The array to pass to this set function is different from the array
	 * that you provide to the constructor or set().
	 *
	 * @param array $arr An associative array with:
	 *   * \e array|string \b contact_allow => array with cids or comma-seperated string
	 *   * \e array|string \b group_allow   => array with gids or comma-seperated string
	 *   * \e array|string \b contact_deny  => array with cids or comma-seperated string
	 *   * \e array|string \b group_deny    => array with gids or comma-seperated string
	 * @param boolean $explicit (optional) default true
	 */
	function set_from_array($arr, $explicit = true) {
		$this->allow_cid = perms2str((is_array($arr['contact_allow']))
			? $arr['contact_allow'] : explode(',', $arr['contact_allow']));
		$this->allow_gid = perms2str((is_array($arr['group_allow']))
			? $arr['group_allow'] : explode(',', $arr['group_allow']));
		$this->deny_cid  = perms2str((is_array($arr['contact_deny']))
			? $arr['contact_deny'] : explode(',', $arr['contact_deny']));
		$this->deny_gid  = perms2str((is_array($arr['group_deny']))
			? $arr['group_deny'] : explode(',', $arr['group_deny']));

		$this->explicit = $explicit;
	}

	/**
	 * @brief Returns true if any access lists component is set.
	 *
	 * @return boolean Return true if any of allow_* deny_* values is set.
	 */
	function is_private() {
		return (($this->allow_cid || $this->allow_gid || $this->deny_cid || $this->deny_gid) ? true : false);
	}

}
