<?php
namespace Zotlabs\Module;

/* ACL selector json backend */
require_once("include/acl_selectors.php");
require_once("include/group.php");


class Acl extends \Zotlabs\Web\Controller {

	function init(){
	
	//	logger('mod_acl: ' . print_r($_REQUEST,true));
	
		$start = (x($_REQUEST,'start')?$_REQUEST['start']:0);
		$count = (x($_REQUEST,'count')?$_REQUEST['count']:100);
		$search = (x($_REQUEST,'search')?$_REQUEST['search']:"");
		$type = (x($_REQUEST,'type')?$_REQUEST['type']:"");
		$noforums = (x($_REQUEST,'n') ? $_REQUEST['n'] : false);	
	
		// List of channels whose connections to also suggest, e.g. currently viewed channel or channels mentioned in a post
		$extra_channels = (x($_REQUEST,'extra_channels') ? $_REQUEST['extra_channels'] : array());
	
		// For use with jquery.autocomplete for private mail completion
	
		if(x($_REQUEST,'query') && strlen($_REQUEST['query'])) {
			if(! $type)
				$type = 'm';
			$search = $_REQUEST['query'];
		}
	
		if(!(local_channel()))
			if(!($type == 'x' || $type == 'c'))
				killme();
	
		if ($search != "") {
			$sql_extra = " AND `name` LIKE " . protect_sprintf( "'%" . dbesc($search) . "%'" ) . " ";
			$sql_extra2 = "AND ( xchan_name LIKE " . protect_sprintf( "'%" . dbesc($search) . "%'" ) . " OR xchan_addr LIKE " . protect_sprintf( "'%" . dbesc($search) . ((strpos($search,'@') === false) ? "%@%'"  : "%'")) . ") ";
	
			// This horrible mess is needed because position also returns 0 if nothing is found. W/ould be MUCH easier if it instead returned a very large value
			// Otherwise we could just order by LEAST(POSITION($search IN xchan_name),POSITION($search IN xchan_addr)).
			$order_extra2 = "CASE WHEN xchan_name LIKE " . protect_sprintf( "'%" . dbesc($search) . "%'" ) ." then POSITION('".dbesc($search)."' IN xchan_name) else position('".dbesc($search)."' IN xchan_addr) end, ";
			$col = ((strpos($search,'@') !== false) ? 'xchan_addr' : 'xchan_name' );
			$sql_extra3 = "AND $col like " . protect_sprintf( "'%" . dbesc($search) . "%'" ) . " ";
	
		} else {
			$sql_extra = $sql_extra2 = $sql_extra3 = "";
		}
		
		
		$groups = array();
		$contacts = array();
		
		if ($type=='' || $type=='g'){
	
			$r = q("SELECT `groups`.`id`, `groups`.`hash`, `groups`.`gname`
					FROM `groups`,`group_member` 
					WHERE `groups`.`deleted` = 0 AND `groups`.`uid` = %d 
					AND `group_member`.`gid`=`groups`.`id`
					$sql_extra
					GROUP BY `groups`.`id`
					ORDER BY `groups`.`gname` 
					LIMIT %d OFFSET %d",
				intval(local_channel()),
				intval($count),
				intval($start)
			);

			if($r) {	
				foreach($r as $g){
		//		logger('acl: group: ' . $g['gname'] . ' members: ' . group_get_members_xchan($g['id']));
					$groups[] = array(
						"type"  => "g",
						"photo" => "images/twopeople.png",
						"name"  => $g['gname'],
						"id"	=> $g['id'],
						"xid"   => $g['hash'],
						"uids"  => group_get_members_xchan($g['id']),
						"link"  => ''
					);
				}
			}
		}
	
		if ($type=='' || $type=='c') {
			$extra_channels_sql  = ''; 
			// Only include channels who allow the observer to view their permissions
			foreach($extra_channels as $channel) {
				if(perm_is_allowed(intval($channel), get_observer_hash(),'view_contacts'))
					$extra_channels_sql .= "," . intval($channel);
			}
	
			$extra_channels_sql = substr($extra_channels_sql,1); // Remove initial comma
	
			// Getting info from the abook is better for local users because it contains info about permissions
			if(local_channel()) {
				if($extra_channels_sql != '')
					$extra_channels_sql = " OR (abook_channel IN ($extra_channels_sql)) and abook_hidden = 0 ";
	
				$r = q("SELECT abook_id as id, xchan_hash as hash, xchan_name as name, xchan_photo_s as micro, xchan_url as url, xchan_addr as nick, abook_their_perms, abook_flags, abook_self 
					FROM abook left join xchan on abook_xchan = xchan_hash 
					WHERE (abook_channel = %d $extra_channels_sql) AND abook_blocked = 0 and abook_pending = 0 and xchan_deleted = 0 $sql_extra2 order by $order_extra2 xchan_name asc" ,
					intval(local_channel())
				);
	
			}
			else { // Visitors
				$r = q("SELECT xchan_hash as id, xchan_hash as hash, xchan_name as name, xchan_photo_s as micro, xchan_url as url, xchan_addr as nick, 0 as abook_their_perms, 0 as abook_flags, 0 as abook_self
					FROM xchan left join xlink on xlink_link = xchan_hash
					WHERE xlink_xchan  = '%s' AND xchan_deleted = 0 $sql_extra2 order by $order_extra2 xchan_name asc" ,
					dbesc(get_observer_hash())
				);
	
				// Find contacts of extra channels
				// This is probably more complicated than it needs to be
				if($extra_channels_sql) {
					// Build a list of hashes that we got previously so we don't get them again
					$known_hashes = array("'".get_observer_hash()."'");
					if($r)
						foreach($r as $rr) 
							$known_hashes[] = "'".$rr['hash']."'";
					$known_hashes_sql = 'AND xchan_hash not in ('.join(',',$known_hashes).')';
	
					$r2 = q("SELECT abook_id as id, xchan_hash as hash, xchan_name as name, xchan_photo_s as micro, xchan_url as url, xchan_addr as nick, abook_their_perms, abook_flags, abook_self 
						FROM abook left join xchan on abook_xchan = xchan_hash 
						WHERE abook_channel IN ($extra_channels_sql) $known_hashes_sql AND abook_blocked = 0 and abook_pending = 0 and abook_hidden = 0 and xchan_deleted = 0 $sql_extra2 order by $order_extra2 xchan_name asc");
					if($r2)
						$r = array_merge($r,$r2);
	
					// Sort accoring to match position, then alphabetically. This could be avoided if the above two SQL queries could be combined into one, and the sorting could be done on the SQl server (like in the case of a local user)
					$matchpos = function($x) use($search) {
						$namepos = strpos($x['name'],$search);
						$nickpos = strpos($x['nick'],$search);
						// Use a large position if not found
						return min($namepos === false ? 9999 : $namepos, $nickpos === false ? 9999 : $nickpos);
					};
					// This could be made simpler if PHP supported stable sorting
					usort($r,function($a,$b) use($matchpos) {
						$pos1 = $matchpos($a);
						$pos2 = $matchpos($b);
						if($pos1 == $pos2) { // Order alphabetically if match position is the same
							if($a['name'] == $b['name'])
								return 0;
							else
								return ($a['name'] < $b['name']) ? -1 : 1;
						}
						return ($pos1 < $pos2) ? -1 : 1;
					});
				}
			}
			if(intval(get_config('system','taganyone')) || intval(get_pconfig(local_channel(),'system','taganyone'))) {
				if((count($r) < 100) && $type == 'c') {
					$r2 = q("SELECT substr(xchan_hash,1,18) as id, xchan_hash as hash, xchan_name as name, xchan_photo_s as micro, xchan_url as url, xchan_addr as nick, 0 as abook_their_perms, 0 as abook_flags, 0 as abook_self 
						FROM xchan 
						WHERE xchan_deleted = 0 $sql_extra2 order by $order_extra2 xchan_name asc" 
					);
					if($r2)
						$r = array_merge($r,$r2);
				}
			}
		}
		elseif($type == 'm') {
	
			$r = q("SELECT xchan_hash as id, xchan_name as name, xchan_addr as nick, xchan_photo_s as micro, xchan_url as url 
				FROM abook left join xchan on abook_xchan = xchan_hash
				WHERE abook_channel = %d and ( (abook_their_perms = null) or (abook_their_perms & %d )>0)
				and xchan_deleted = 0
				$sql_extra3
				ORDER BY `xchan_name` ASC ",
				intval(local_channel()),
				intval(PERMS_W_MAIL)
			);
		}
		elseif(($type == 'a') || ($type == 'p')) {
	
			$r = q("SELECT abook_id as id, xchan_name as name, xchan_hash as hash, xchan_addr as nick, xchan_photo_s as micro, xchan_network as network, xchan_url as url, xchan_addr as attag , abook_their_perms FROM abook left join xchan on abook_xchan = xchan_hash
				WHERE abook_channel = %d
				and xchan_deleted = 0
				$sql_extra3
				ORDER BY xchan_name ASC ",
				intval(local_channel())
			);
	
		}
		elseif($type == 'x') {
			$r = $this->navbar_complete($a);
			$contacts = array();
			if($r) {
				foreach($r as $g) {
					$contacts[] = array(
						"photo"    => $g['photo'],
						"name"     => $g['name'],
						"nick"     => $g['address'],
					);
				}
			}
	
			$o = array(
				'start' => $start,
				'count'	=> $count,
				'items'	=> $contacts,
			);
			echo json_encode($o);
			killme();
		}
		else
			$r = array();
	
		if($r) {
			foreach($r as $g){
	
				// remove RSS feeds from ACLs - they are inaccessible
				if(strpos($g['hash'],'/') && $type != 'a')
					continue;
	
				if(($g['abook_their_perms'] & PERMS_W_TAGWALL) && $type == 'c' && (! $noforums)) {
					$contacts[] = array(
						"type"     => "c",
						"photo"    => "images/twopeople.png",
						"name"     => $g['name'] . '+',
						"id"	   => $g['id'] . '+',
						"xid"      => $g['hash'],
						"link"     => $g['nick'],
						"nick"     => substr($g['nick'],0,strpos($g['nick'],'@')),
						"self"     => (intval($g['abook_self']) ? 'abook-self' : ''),
						"taggable" => 'taggable',
						"label"    => t('network')
					);
				}
				$contacts[] = array(
					"type"     => "c",
					"photo"    => $g['micro'],
					"name"     => $g['name'],
					"id"	   => $g['id'],
					"xid"      => $g['hash'],
					"link"     => $g['nick'],
					"nick"     => (($g['nick']) ? substr($g['nick'],0,strpos($g['nick'],'@')) : t('RSS')),
					"self"     => (intval($g['abook_self']) ? 'abook-self' : ''),
					"taggable" => '',
					"label"    => '',
				);
			}			
		}
			
		$items = array_merge($groups, $contacts);
		
		$o = array(
			'start' => $start,
			'count'	=> $count,
			'items'	=> $items,
		);
	
	
		
		echo json_encode($o);
	
		killme();
	}
	
	
	function navbar_complete(&$a) {
	
	//	logger('navbar_complete');
	
		if(observer_prohibited()) {
			return;
		}
	
		$dirmode = intval(get_config('system','directory_mode'));
		$search = ((x($_REQUEST,'search')) ? htmlentities($_REQUEST['search'],ENT_COMPAT,'UTF-8',false) : '');
		if(! $search || mb_strlen($search) < 2)
			return array();
	
		$star = false;
		$address = false;
	
		if(substr($search,0,1) === '@')
			$search = substr($search,1);
	
		if(substr($search,0,1) === '*') {
			$star = true;
			$search = substr($search,1);
		}
	
		if(strpos($search,'@') !== false) {
			$address = true;
		}
	
		if(($dirmode == DIRECTORY_MODE_PRIMARY) || ($dirmode == DIRECTORY_MODE_STANDALONE)) {
			$url = z_root() . '/dirsearch';
		}
	
		if(! $url) {
			require_once("include/dir_fns.php");
			$directory = find_upstream_directory($dirmode);
			$url = $directory['url'] . '/dirsearch';
		}
	
		$count = (x($_REQUEST,'count')?$_REQUEST['count']:100);
		if($url) {
			$query = $url . '?f=' ;
			$query .= '&name=' . urlencode($search) . "&limit=$count" . (($address) ? '&address=' . urlencode($search) : '');
	
			$x = z_fetch_url($query);
			if($x['success']) {
				$t = 0;
				$j = json_decode($x['body'],true);
				if($j && $j['results']) {
					return $j['results'];
				}
			}
		}
		return array();
	}
	
}
