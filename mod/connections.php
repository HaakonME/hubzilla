<?php

require_once('include/Contact.php');
require_once('include/socgraph.php');
require_once('include/contact_selectors.php');
require_once('include/group.php');
require_once('include/contact_widgets.php');
require_once('include/zot.php');
require_once('include/widgets.php');

function connections_init(&$a) {

	if(! local_channel())
		return;

	$channel = $a->get_channel();
	if($channel)
		head_set_icon($channel['xchan_photo_s']);

}

function connections_content(&$a) {

	$sort_type = 0;
	$o = '';


	if(! local_channel()) {
		notice( t('Permission denied.') . EOL);
		return login();
	}

	$blocked     = false;
	$hidden      = false;
	$ignored     = false;
	$archived    = false;
	$unblocked   = false;
	$pending     = false;
	$unconnected = false;
	$all         = false;

	if(! $_REQUEST['aj'])
		$_SESSION['return_url'] = $a->query_string;

	$search_flags = 0;
	$head = '';

	if(argc() == 2) {
		switch(argv(1)) {
			case 'blocked':
				$search_flags = ABOOK_FLAG_BLOCKED;
				$head = t('Blocked');
				$blocked = true;
				break;
			case 'ignored':
				$search_flags = ABOOK_FLAG_IGNORED;
				$head = t('Ignored');
				$ignored = true;
				break;
			case 'hidden':
				$search_flags = ABOOK_FLAG_HIDDEN;
				$head = t('Hidden');
				$hidden = true;
				break;
			case 'archived':
				$search_flags = ABOOK_FLAG_ARCHIVED;
				$head = t('Archived');
				$archived = true;
				break;
			case 'pending':
				$search_flags = ABOOK_FLAG_PENDING;
				$head = t('New');
				$pending = true;
				nav_set_selected('intros');
				break;
			case 'ifpending':
				$r = q("SELECT COUNT(abook.abook_id) AS total FROM abook left join xchan on abook.abook_xchan = xchan.xchan_hash where abook_channel = %d and (abook_flags & %d)>0 and not ((abook_flags & %d)>0 or (xchan_flags & %d)>0)",
					intval(local_channel()),
					intval(ABOOK_FLAG_PENDING),
					intval(ABOOK_FLAG_SELF|ABOOK_FLAG_IGNORED),
					intval(XCHAN_FLAGS_DELETED|XCHAN_FLAGS_ORPHAN)
				);
				if($r && $r[0]['total']) {
					$search_flags = ABOOK_FLAG_PENDING;
					$head = t('New');
					$pending = true;
					nav_set_selected('intros');
					$a->argv[1] = 'pending';
				}
				else {
					$head = t('All');
					$search_flags = 0;
					$all = true;
					$a->argc = 1;
					unset($a->argv[1]);
				}
				nav_set_selected('intros');
				break;
//			case 'unconnected':
//				$search_flags = ABOOK_FLAG_UNCONNECTED;
//				$head = t('Unconnected');
//				$unconnected = true;
//				break;

			case 'all':
				$head = t('All');
			default:
				$search_flags = 0;
				$all = true;
				break;

		}

		$sql_extra = (($search_flags) ? " and ( abook_flags & " . $search_flags . " )>0 " : "");
		if(argv(1) === 'pending')
			$sql_extra .= " and not ( abook_flags & " . ABOOK_FLAG_IGNORED . " )>0 ";

	}
	else {
		$sql_extra = " and not ( abook_flags & " . ABOOK_FLAG_BLOCKED . " )>0 ";
		$unblocked = true;
	}

	$search = ((x($_REQUEST,'search')) ? notags(trim($_REQUEST['search'])) : '');

	$tabs = array(
		array(
			'label' => t('Suggestions'),
			'url'   => z_root() . '/suggest', 
			'sel'   => '',
			'title' => t('Suggest new connections'),
		),
		array(
			'label' => t('New Connections'),
			'url'   => z_root() . '/connections/pending', 
			'sel'   => ($pending) ? 'active' : '',
			'title' => t('Show pending (new) connections'),
		),
		array(
			'label' => t('All Connections'),
			'url'   => z_root() . '/connections/all', 
			'sel'   => ($all) ? 'active' : '',
			'title' => t('Show all connections'),
		),
		array(
			'label' => t('Unblocked'),
			'url'   => z_root() . '/connections',
			'sel'   => (($unblocked) && (! $search) && (! $nets)) ? 'active' : '',
			'title' => t('Only show unblocked connections'),
		),

		array(
			'label' => t('Blocked'),
			'url'   => z_root() . '/connections/blocked',
			'sel'   => ($blocked) ? 'active' : '',
			'title' => t('Only show blocked connections'),
		),

		array(
			'label' => t('Ignored'),
			'url'   => z_root() . '/connections/ignored',
			'sel'   => ($ignored) ? 'active' : '',
			'title' => t('Only show ignored connections'),
		),

		array(
			'label' => t('Archived'),
			'url'   => z_root() . '/connections/archived',
			'sel'   => ($archived) ? 'active' : '',
			'title' => t('Only show archived connections'),
		),

		array(
			'label' => t('Hidden'),
			'url'   => z_root() . '/connections/hidden',
			'sel'   => ($hidden) ? 'active' : '',
			'title' => t('Only show hidden connections'),
		),

//		array(
//			'label' => t('Unconnected'),
//			'url'   => z_root() . '/connections/unconnected',
//			'sel'   => ($unconnected) ? 'active' : '',
//			'title' => t('Only show one-way connections'),
//		),


	);

	$tab_tpl = get_markup_template('common_tabs.tpl');
	$t = replace_macros($tab_tpl, array('$tabs'=>$tabs));

	$searching = false;
	if($search) {
		$search_hdr = $search;
		$search_txt = dbesc(protect_sprintf(preg_quote($search)));
		$searching = true;
	}
	$sql_extra .= (($searching) ? protect_sprintf(" AND xchan_name like '%$search_txt%' ") : "");

	if($_REQUEST['gid']) {
		$sql_extra .= " and xchan_hash in ( select xchan from group_member where gid = " . intval($_REQUEST['gid']) . " and uid = " . intval(local_channel()) . " ) ";
	}
 	
	$r = q("SELECT COUNT(abook.abook_id) AS total FROM abook left join xchan on abook.abook_xchan = xchan.xchan_hash 
		where abook_channel = %d and not (abook_flags & %d)>0 and not (xchan_flags & %d )>0 $sql_extra $sql_extra2 ",
		intval(local_channel()),
		intval(ABOOK_FLAG_SELF),
		intval(XCHAN_FLAGS_DELETED|XCHAN_FLAGS_ORPHAN)
	);
	if($r) {
		$a->set_pager_total($r[0]['total']);
		$total = $r[0]['total'];
	}

	$r = q("SELECT abook.*, xchan.* FROM abook left join xchan on abook.abook_xchan = xchan.xchan_hash
		WHERE abook_channel = %d and not (abook_flags & %d)>0 and not ( xchan_flags & %d)>0 $sql_extra $sql_extra2 ORDER BY xchan_name LIMIT %d OFFSET %d ",
		intval(local_channel()),
		intval(ABOOK_FLAG_SELF),
		intval(XCHAN_FLAGS_DELETED|XCHAN_FLAGS_ORPHAN),
		intval($a->pager['itemspage']),
		intval($a->pager['start'])
	);

	$contacts = array();

	if(count($r)) {

		foreach($r as $rr) {
			if($rr['xchan_url']) {
				$contacts[] = array(
					'img_hover' => sprintf( t('%1$s [%2$s]'),$rr['xchan_name'],$rr['xchan_url']),
					'edit_hover' => t('Edit connection'),
					'id' => $rr['abook_id'],
					'alt_text' => $alt_text,
					'dir_icon' => $dir_icon,
					'thumb' => $rr['xchan_photo_m'], 
					'name' => $rr['xchan_name'],
					'username' => $rr['xchan_name'],
					'classes' => (($rr['abook_flags'] & ABOOK_FLAG_ARCHIVED) ? 'archived' : ''),
					'link' => z_root() . '/connedit/' . $rr['abook_id'],
					'edit' => t('Edit'),
					'url' => chanlink_url($rr['xchan_url']),
					'network' => network_to_name($rr['network']),
				);
			}
		}
	}
	

	if($_REQUEST['aj']) {
		if($contacts) {
			$o = replace_macros(get_markup_template('contactsajax.tpl'),array(
				'$contacts' => $contacts,
				'$edit' => t('Edit'),
			));
		}
		else {
			$o = '<div id="content-complete"></div>';
		}
		echo $o;
		killme();
	}
	else {
		$o .= "<script> var page_query = '" . $_GET['q'] . "'; var extra_args = '" . extra_query_args() . "' ; </script>";
		$o .= replace_macros(get_markup_template('connections.tpl'),array(
			'$header' => t('Connections') . (($head) ? ' - ' . $head : ''),
			'$tabs' => $t,
			'$total' => $total,
			'$search' => $search_hdr,
			'$desc' => t('Search your connections'),
			'$finding' => (($searching) ? t('Finding: ') . "'" . $search . "'" : ""),
			'$submit' => t('Find'),
			'$edit' => t('Edit'),
			'$cmd' => $a->cmd,
			'$contacts' => $contacts,
			'$paginate' => paginate($a),

		)); 
	}

	if(! $contacts)
		$o .= '<div id="content-complete"></div>';

	return $o;
}
