<?php
/**
 * @file include/widgets.php
 *
 * @brief This file contains some widgets.
 */

require_once('include/dir_fns.php');
require_once('include/contact_widgets.php');
require_once('include/attach.php');





















function widget_website_portation_tools($arr) {

	// mod menu doesn't load a profile. For any modules which load a profile, check it.
	// otherwise local_channel() is sufficient for permissions.

	if(App::$profile['profile_uid'])
		if((App::$profile['profile_uid'] != local_channel()) && (! App::$is_sys))
			return '';

	if(! local_channel())
		return '';

	return website_portation_tools();
}

function widget_findpeople($arr) {
	return findpeople_widget();
}



function widget_vcard($arr) {
	return vcard_from_xchan('', App::get_observer());
}


/*
 * The following directory widgets are only useful on the directory page
 */


function widget_dirsort($arr) {
	return dir_sort_links();
}

function widget_dirtags($arr) {
	return dir_tagblock(z_root() . '/directory', null);
}

function widget_menu_preview($arr) {
	if(! App::$data['menu_item'])
		return;
	require_once('include/menu.php');

	return menu_render(App::$data['menu_item']);
}

function widget_chatroom_list($arr) {


	$r = Zotlabs\Lib\Chatroom::roomlist(App::$profile['profile_uid']);

	if($r) {
		return replace_macros(get_markup_template('chatroomlist.tpl'), array(
			'$header' => t('Chatrooms'),
			'$baseurl' => z_root(),
			'$nickname' => App::$profile['channel_address'],
			'$items' => $r,
			'$overview' => t('Overview')
		));
	}
}

function widget_chatroom_members() {
	$o = replace_macros(get_markup_template('chatroom_members.tpl'), array(
		'$header' => t('Chat Members')
	));

	return $o;
}

function widget_wiki_list($arr) {

	$channel = channelx_by_n(App::$profile_uid);

	$wikis = Zotlabs\Lib\NativeWiki::listwikis($channel,get_observer_hash());

	if($wikis) {
		return replace_macros(get_markup_template('wikilist_widget.tpl'), array(
			'$header' => t('Wiki List'),
			'$channel' => $channel['channel_address'],
			'$wikis' => $wikis['wikis']
		));
	}
	return '';
}

function widget_wiki_pages($arr) {

	$channelname = ((array_key_exists('channel',$arr)) ? $arr['channel'] : '');
	$c = channelx_by_nick($channelname);

	$wikiname = '';
	if (array_key_exists('refresh', $arr)) {
		$not_refresh = (($arr['refresh']=== true) ? false : true);
	} else {
		$not_refresh = true;
	}
	$pages = array();
	if (! array_key_exists('resource_id', $arr)) {
		$hide = true;
	} else {
		$p = Zotlabs\Lib\NativeWikiPage::page_list($c['channel_id'],get_observer_hash(),$arr['resource_id']);

		if($p['pages']) {
			$pages = $p['pages'];
			$w = $p['wiki'];
			// Wiki item record is $w['wiki']
			$wikiname = $w['urlName'];
			if (!$wikiname) {
				$wikiname = '';
			}
		}
	}
	$can_create = perm_is_allowed(\App::$profile['uid'],get_observer_hash(),'write_wiki');

	$can_delete = ((local_channel() && (local_channel() == \App::$profile['uid'])) ? true : false);

	return replace_macros(get_markup_template('wiki_page_list.tpl'), array(
			'$hide' => $hide,
			'$resource_id' => $arr['resource_id'],
			'$not_refresh' => $not_refresh,
			'$header' => t('Wiki Pages'),
			'$channel' => $channelname,
			'$wikiname' => $wikiname,
			'$pages' => $pages,
			'$canadd' => $can_create,
			'$candel' => $can_delete,
			'$addnew' => t('Add new page'),
			'$pageName' => array('pageName', t('Page name')),
	));
}

function widget_wiki_page_history($arr) {

	$pageUrlName = ((array_key_exists('pageUrlName', $arr)) ? $arr['pageUrlName'] : '');
	$resource_id = ((array_key_exists('resource_id', $arr)) ? $arr['resource_id'] : '');

	$pageHistory = Zotlabs\Lib\NativeWikiPage::page_history(array('channel_id' => App::$profile_uid, 'observer_hash' => get_observer_hash(), 'resource_id' => $resource_id, 'pageUrlName' => $pageUrlName));
	return replace_macros(get_markup_template('nwiki_page_history.tpl'), array(
		'$pageHistory' => $pageHistory['history'],
		'$permsWrite' => $arr['permsWrite'],
		'$name_lbl' => t('Name'),
		'$msg_label' => t('Message','wiki_history')
	));

}

function widget_bookmarkedchats($arr) {

	if(! feature_enabled(App::$profile['profile_uid'],'ajaxchat'))
		return '';

	$h = get_observer_hash();
	if(! $h)
		return;
	$r = q("select xchat_url, xchat_desc from xchat where xchat_xchan = '%s' order by xchat_desc",
		dbesc($h)
	);
	if($r) {
		for($x = 0; $x < count($r); $x ++) {
			$r[$x]['xchat_url'] = zid($r[$x]['xchat_url']);
		}
	}
	return replace_macros(get_markup_template('bookmarkedchats.tpl'),array(
		'$header' => t('Bookmarked Chatrooms'),
		'$rooms' => $r
	));
}

function widget_suggestedchats($arr) {

	if(! feature_enabled(App::$profile['profile_uid'],'ajaxchat'))
		return '';

	// There are reports that this tool does not ever remove chatrooms on dead sites,
	// and also will happily link to private chats which you cannot enter.
	// For those reasons, it will be disabled until somebody decides it's worth
	// fixing and comes up with a plan for doing so.

	return '';


	// probably should restrict this to your friends, but then the widget will only work
	// if you are logged in locally.

	$h = get_observer_hash();
	if(! $h)
		return;
	$r = q("select xchat_url, xchat_desc, count(xchat_xchan) as total from xchat group by xchat_url, xchat_desc order by total desc, xchat_desc limit 24");
	if($r) {
		for($x = 0; $x < count($r); $x ++) {
			$r[$x]['xchat_url'] = zid($r[$x]['xchat_url']);
		}
	}
	return replace_macros(get_markup_template('bookmarkedchats.tpl'),array(
		'$header' => t('Suggested Chatrooms'),
		'$rooms' => $r
	));
}

function widget_item($arr) {

	$channel_id = 0;
	if(array_key_exists('channel_id',$arr) && intval($arr['channel_id']))
		$channel_id = intval($arr['channel_id']);
	if(! $channel_id)
		$channel_id = App::$profile_uid;
	if(! $channel_id)
		return '';


	if((! $arr['mid']) && (! $arr['title']))
		return '';

	if(! perm_is_allowed($channel_id, get_observer_hash(), 'view_pages'))
		return '';

	require_once('include/security.php');
	$sql_extra = item_permissions_sql($channel_id);

	if($arr['title']) {
		$r = q("select item.* from item left join iconfig on item.id = iconfig.iid
			where item.uid = %d and iconfig.cat = 'system' and iconfig.v = '%s'
			and iconfig.k = 'WEBPAGE' and item_type = %d $sql_options $revision limit 1",
			intval($channel_id),
			dbesc($arr['title']),
			intval(ITEM_TYPE_WEBPAGE)
		);
	}
	else {
		$r = q("select * from item where mid = '%s' and uid = %d and item_type = " . intval(ITEM_TYPE_WEBPAGE) . " $sql_extra limit 1",
			dbesc($arr['mid']),
			intval($channel_id)
		);
	}

	if(! $r)
		return '';

	xchan_query($r);
	$r = fetch_post_tags($r, true);

	$o = prepare_page($r[0]);
	return $o;
}

function widget_clock($arr) {

	$miltime = 0;
	if(isset($arr['military']) && $arr['military'])
		$miltime = 1;

$o = <<< EOT
<div class="widget">
<h3 class="clockface"></h3>
<script>

var timerID = null
var timerRunning = false

function stopclock(){
    if(timerRunning)
        clearTimeout(timerID)
    timerRunning = false
}

function startclock(){
    stopclock()
    showtime()
}

function showtime(){
    var now = new Date()
    var hours = now.getHours()
    var minutes = now.getMinutes()
    var seconds = now.getSeconds()
	var military = $miltime
    var timeValue = ""
	if(military)
		timeValue = hours
	else
		timeValue = ((hours > 12) ? hours - 12 : hours)
    timeValue  += ((minutes < 10) ? ":0" : ":") + minutes
//    timeValue  += ((seconds < 10) ? ":0" : ":") + seconds
	if(! military)
	    timeValue  += (hours >= 12) ? " P.M." : " A.M."
    $('.clockface').html(timeValue)
    timerID = setTimeout("showtime()",1000)
    timerRunning = true
}

$(document).ready(function() {
	startclock();
});

</script>
</div>
EOT;
return $o;

}

/**
 * @brief Widget to display a single photo.
 *
 * @param array $arr associative array with
 *    * \e string \b src URL of photo; URL must be an http or https URL
 *    * \e boolean \b zrl use zid in URL
 *    * \e string \b style CSS string
 *
 * @return string with parsed HTML
 */
function widget_photo($arr) {

	$style = $zrl = false;

	if(array_key_exists('src', $arr) && isset($arr['src']))
		$url = $arr['src'];

	if(strpos($url, 'http') !== 0)
		return '';

	if(array_key_exists('style', $arr) && isset($arr['style']))
		$style = $arr['style'];

	// ensure they can't sneak in an eval(js) function

	if(strpbrk($style, '(\'"<>') !== false)
		$style = '';

	if(array_key_exists('zrl', $arr) && isset($arr['zrl']))
		$zrl = (($arr['zrl']) ? true : false);

	if($zrl)
		$url = zid($url);

	$o = '<div class="widget">';

	$o .= '<img ' . (($zrl) ? ' class="zrl" ' : '')
				  . (($style) ? ' style="' . $style . '"' : '')
				  . ' src="' . $url . '" alt="' . t('photo/image') . '">';

	$o .= '</div>';

	return $o;
}


function widget_cover_photo($arr) {

	require_once('include/channel.php');
	$o = '';

	if(App::$module == 'channel' && $_REQUEST['mid'])
		return '';

	$channel_id = 0;
	if(array_key_exists('channel_id', $arr) && intval($arr['channel_id']))
		$channel_id = intval($arr['channel_id']);
	if(! $channel_id)
		$channel_id = App::$profile_uid;
	if(! $channel_id)
		return '';

	$channel = channelx_by_n($channel_id);

	if(array_key_exists('style', $arr) && isset($arr['style']))
		$style = $arr['style'];
	else
		$style = 'width:100%; height: auto;';

	// ensure they can't sneak in an eval(js) function

	if(strpbrk($style,'(\'"<>') !== false)
		$style = '';

	if(array_key_exists('title', $arr) && isset($arr['title']))
		$title = $arr['title'];
	else
		$title = $channel['channel_name'];

	if(array_key_exists('subtitle', $arr) && isset($arr['subtitle']))
		$subtitle = $arr['subtitle'];
	else
		$subtitle = str_replace('@','&#x40;',$channel['xchan_addr']);

	$c = get_cover_photo($channel_id,'html');

	if($c) {
		$photo_html = (($style) ? str_replace('alt=',' style="' . $style . '" alt=',$c) : $c);

		$o = replace_macros(get_markup_template('cover_photo_widget.tpl'),array(
			'$photo_html'	=> $photo_html,
			'$title'	=> $title,
			'$subtitle'	=> $subtitle,
			'$hovertitle' => t('Click to show more'),
		));
	}
	return $o;
}


function widget_photo_rand($arr) {

	require_once('include/photos.php');
	$style = false;

	if(array_key_exists('album', $arr) && isset($arr['album']))
		$album = $arr['album'];
	else
		$album = '';

	$channel_id = 0;
	if(array_key_exists('channel_id', $arr) && intval($arr['channel_id']))
		$channel_id = intval($arr['channel_id']);
	if(! $channel_id)
		$channel_id = App::$profile_uid;
	if(! $channel_id)
		return '';

	$scale = ((array_key_exists('scale',$arr)) ? intval($arr['scale']) : 0);

	$ret = photos_list_photos(array('channel_id' => $channel_id),App::get_observer(),$album);

	$filtered = array();
	if($ret['success'] && $ret['photos'])
	foreach($ret['photos'] as $p)
		if($p['imgscale'] == $scale)
			$filtered[] = $p['src'];

	if($filtered) {
		$e = mt_rand(0, count($filtered) - 1);
		$url = $filtered[$e];
	}

	if(strpos($url, 'http') !== 0)
		return '';

	if(array_key_exists('style', $arr) && isset($arr['style']))
		$style = $arr['style'];

	// ensure they can't sneak in an eval(js) function

	if(strpos($style,'(') !== false)
		return '';

	$url = zid($url);

	$o = '<div class="widget">';

	$o .= '<img class="zrl" '
		. (($style) ? ' style="' . $style . '"' : '')
		. ' src="' . $url . '" alt="' . t('photo/image') . '">';

	$o .= '</div>';

	return $o;
}


function widget_random_block($arr) {

	$channel_id = 0;
	if(array_key_exists('channel_id',$arr) && intval($arr['channel_id']))
		$channel_id = intval($arr['channel_id']);
	if(! $channel_id)
		$channel_id = App::$profile_uid;
	if(! $channel_id)
		return '';

	if(array_key_exists('contains',$arr))
		$contains = $arr['contains'];

	$o = '';

	require_once('include/security.php');
	$sql_options = item_permissions_sql($channel_id);

	$randfunc = db_getfunc('RAND');

	$r = q("select item.* from item left join iconfig on item.id = iconfig.iid
		where item.uid = %d and iconfig.cat = 'system' and iconfig.v like '%s' and iconfig.k = 'BUILDBLOCK' and
		item_type = %d $sql_options order by $randfunc limit 1",
		intval($channel_id),
		dbesc('%' . $contains . '%'),
		intval(ITEM_TYPE_BLOCK)
	);

	if($r) {
		$o = '<div class="widget bblock">';
		if($r[0]['title'])
			$o .= '<h3>' . $r[0]['title'] . '</h3>';

		$o .= prepare_text($r[0]['body'],$r[0]['mimetype']);
		$o .= '</div>';
	}

	return $o;
}


function widget_rating($arr) {


	$rating_enabled = get_config('system','rating_enabled');
	if(! $rating_enabled) {
		return;
	}

	if($arr['target'])
		$hash = $arr['target'];
	else
		$hash = App::$poi['xchan_hash'];

	if(! $hash)
		return;

	$url = '';
	$remote = false;

	if(remote_channel() && ! local_channel()) {
		$ob = App::get_observer();
		if($ob && $ob['xchan_url']) {
			$p = parse_url($ob['xchan_url']);
			if($p) {
				$url = $p['scheme'] . '://' . $p['host'] . (($p['port']) ? ':' . $p['port'] : '');
				$url .= '/rate?f=&target=' . urlencode($hash);
			}
			$remote = true;
		}
	}

	$self = false;

	if(local_channel()) {
		$channel = App::get_channel();

		if($hash == $channel['channel_hash'])
			$self = true;

		head_add_js('ratings.js');

	}


	$o = '<div class="widget">';
	$o .= '<h3>' . t('Rating Tools') . '</h3>';

	if((($remote) || (local_channel())) && (! $self)) {
		if($remote)
			$o .= '<a class="btn btn-block btn-primary btn-sm" href="' . $url . '"><i class="fa fa-pencil"></i> ' . t('Rate Me') . '</a>';
		else
			$o .= '<div class="btn btn-block btn-primary btn-sm" onclick="doRatings(\'' . $hash . '\'); return false;"><i class="fa fa-pencil"></i> ' . t('Rate Me') . '</div>';
	}

	$o .= '<a class="btn btn-block btn-default btn-sm" href="ratings/' . $hash . '"><i class="fa fa-eye"></i> ' . t('View Ratings') . '</a>';
	$o .= '</div>';

	return $o;

}

// used by site ratings pages to provide a return link
function widget_pubsites($arr) {
	if(App::$poi)
		return;
	return '<div class="widget"><ul class="nav nav-pills"><li><a href="pubsites">' . t('Public Hubs') . '</a></li></ul></div>';
}


function widget_forums($arr) {

	if(! local_channel())
		return '';

	$o = '';

	if(is_array($arr) && array_key_exists('limit',$arr))
		$limit = " limit " . intval($limit) . " ";
	else
		$limit = '';

	$unseen = 0;
	if(is_array($arr) && array_key_exists('unseen',$arr) && intval($arr['unseen']))
		$unseen = 1;

	$perms_sql = item_permissions_sql(local_channel()) . item_normal();

	$xf = false;

	$x1 = q("select xchan from abconfig where chan = %d and cat = 'their_perms' and k = 'send_stream' and v = '0'",
		intval(local_channel())
	);
	if($x1) {
		$xc = ids_to_querystr($x1,'xchan',true);
		$x2 = q("select xchan from abconfig where chan = %d and cat = 'their_perms' and k = 'tag_deliver' and v = '1' and xchan in (" . $xc . ") ",
			intval(local_channel())
		);
		if($x2)
			$xf = ids_to_querystr($x2,'xchan',true);
	}

	$sql_extra = (($xf) ? " and ( xchan_hash in (" . $xf . ") or xchan_pubforum = 1 ) " : " and xchan_pubforum = 1 "); 

	$r1 = q("select abook_id, xchan_hash, xchan_name, xchan_url, xchan_photo_s from abook left join xchan on abook_xchan = xchan_hash where xchan_deleted = 0 and abook_channel = %d $sql_extra order by xchan_name $limit ",
		intval(local_channel())
	);
	if(! $r1)
		return $o;

	$str = '';

	// Trying to cram all this into a single query with joins and the proper group by's is tough.
	// There also should be a way to update this via ajax.

	for($x = 0; $x < count($r1); $x ++) {
		$r = q("select sum(item_unseen) as unseen from item where owner_xchan = '%s' and uid = %d and item_unseen = 1 $perms_sql ",
			dbesc($r1[$x]['xchan_hash']),
			intval(local_channel())
		);
		if($r)
			$r1[$x]['unseen'] = $r[0]['unseen'];

/**
 * @FIXME
 * This SQL makes the counts correct when you get forum posts arriving from different routes/sources
 * (like personal channels). However the network query for these posts doesn't yet include this
 * correction and it makes the SQL for that query pretty hairy so this is left as a future exercise.
 * It may make more sense in that query to look for the mention in the body rather than another join,
 * but that makes it very inefficient.
 *
		$r = q("select sum(item_unseen) as unseen from item left join term on oid = id where otype = %d and owner_xchan != '%s' and item.uid = %d and url = '%s' and ttype = %d $perms_sql ",
			intval(TERM_OBJ_POST),
			dbesc($r1[$x]['xchan_hash']),
			intval(local_channel()),
			dbesc($r1[$x]['xchan_url']),
			intval(TERM_MENTION)
		);
		if($r)
			$r1[$x]['unseen'] = ((array_key_exists('unseen',$r1[$x])) ? $r1[$x]['unseen'] + $r[0]['unseen'] : $r[0]['unseen']);
 *
 * end @FIXME
 */

	}

	if($r1) {
		$o .= '<div class="widget">';
		$o .= '<h3>' . t('Forums') . '</h3><ul class="nav nav-pills nav-stacked">';

		foreach($r1 as $rr) {
			if($unseen && (! intval($rr['unseen'])))
				continue;
			$o .= '<li><a href="network?f=&pf=1&cid=' . $rr['abook_id'] . '" ><span class="badge pull-right">' . ((intval($rr['unseen'])) ? intval($rr['unseen']) : '') . '</span><img src="' . $rr['xchan_photo_s'] . '" style="width: 16px; height: 16px;" /> ' . $rr['xchan_name'] . '</a></li>';
		}
		$o .= '</ul></div>';
	}
	return $o;

}


function widget_activity($arr) {

	if(! local_channel())
		return '';

	$o = '';

	if(is_array($arr) && array_key_exists('limit',$arr))
		$limit = " limit " . intval($limit) . " ";
	else
		$limit = '';

	$perms_sql = item_permissions_sql(local_channel()) . item_normal();

	$r = q("select author_xchan from item where item_unseen = 1 and uid = %d $perms_sql",
		intval(local_channel())
	);

	$contributors = [];
	$arr = [];

	if($r) {
		foreach($r as $rv) {
			if(array_key_exists($rv['author_xchan'],$contributors)) {
				$contributors[$rv['author_xchan']] ++;
			}
			else {
				$contributors[$rv['author_xchan']] = 1;
			}
		}
		foreach($contributors as $k => $v) {
			$arr[] = [ 'author_xchan' => $k, 'total' => $v	];	
		}
		usort($arr,'total_sort');
		xchan_query($arr);
	}

	$x = [ 'entries' => $arr ];
	call_hooks('activity_widget',$x);
	$arr = $x['entries']; 

	if($arr) {
		$o .= '<div class="widget">';
		$o .= '<h3>' . t('Activity','widget') . '</h3><ul class="nav nav-pills nav-stacked">';

		foreach($arr as $rv) {
			$o .= '<li><a href="network?f=&xchan=' . urlencode($rv['author_xchan']) . '" ><span class="badge pull-right">' . ((intval($rv['total'])) ? intval($rv['total']) : '') . '</span><img src="' . $rv['author']['xchan_photo_s'] . '" style="width: 16px; height: 16px;" /> ' . $rv['author']['xchan_name'] . '</a></li>';
		}
		$o .= '</ul></div>';
	}
	return $o;

}




function widget_tasklist($arr) {

	if (! local_channel())
		return;

	require_once('include/event.php');
	$o .= '<script>var tasksShowAll = 0; $(document).ready(function() { tasksFetch(); $("#tasklist-new-form").submit(function(event) { event.preventDefault(); $.post( "tasks/new", $("#tasklist-new-form").serialize(), function(data) { tasksFetch();  $("#tasklist-new-summary").val(""); } ); return false; } )});</script>';
	$o .= '<script>function taskComplete(id) { $.post("tasks/complete/"+id, function(data) { tasksFetch();}); }
		function tasksFetch() {
			$.get("tasks/fetch" + ((tasksShowAll) ? "/all" : ""), function(data) {
				$(".tasklist-tasks").html(data.html);
			});
		}
		</script>';

	$o .= '<div class="widget">' . '<h3>' . t('Tasks') . '</h3><div class="tasklist-tasks">';
	$o .= '</div><form id="tasklist-new-form" action="" ><input id="tasklist-new-summary" type="text" name="summary" value="" /></form>';
	$o .= '</div>';
	return $o;

}


function widget_helpindex($arr) {

	$o .= '<div class="widget">';

	$level_0 = get_help_content('sitetoc');
	if(! $level_0)
		$level_0 = get_help_content('toc');

	$level_0 = preg_replace('/\<ul(.*?)\>/','<ul class="nav nav-pills nav-stacked">',$level_0);

	$levels = array();


	if(argc() > 2) {
		$path = '';
		for($x = 1; $x < argc(); $x ++) {
			$path .= argv($x) . '/';
			$y = get_help_content($path . 'sitetoc');
			if(! $y)
				$y = get_help_content($path . 'toc');
			if($y)
				$levels[] = preg_replace('/\<ul(.*?)\>/','<ul class="nav nav-pills nav-stacked">',$y);
		}
	}

	if($level_0)
		$o .= $level_0;
	if($levels) {
		foreach($levels as $l) {
			$o .= '<br /><br />';
			$o .= $l;
		}
	}

	$o .= '</div>';

	return $o;

}



function widget_admin($arr) {

	/*
	 * Side bar links
	 */

	if(! is_site_admin()) {
		return '';
	}

	$o = '';

	// array( url, name, extra css classes )

	$aside = array(
		'site'      => array(z_root() . '/admin/site/',     t('Site'),           'site'),
		'accounts'  => array(z_root() . '/admin/accounts/', t('Accounts'),       'accounts', 'pending-update', t('Member registrations waiting for confirmation')),
		'channels'  => array(z_root() . '/admin/channels/', t('Channels'),       'channels'),
		'security'  => array(z_root() . '/admin/security/', t('Security'),       'security'),
		'features'  => array(z_root() . '/admin/features/', t('Features'),       'features'),
		'plugins'   => array(z_root() . '/admin/plugins/',  t('Plugins'),        'plugins'),
		'themes'    => array(z_root() . '/admin/themes/',   t('Themes'),         'themes'),
		'queue'     => array(z_root() . '/admin/queue',     t('Inspect queue'),  'queue'),
		'profs'     => array(z_root() . '/admin/profs',     t('Profile Fields'), 'profs'),
		'dbsync'    => array(z_root() . '/admin/dbsync/',   t('DB updates'),     'dbsync')

	);

	/* get plugins admin page */

	$r = q("SELECT * FROM addon WHERE plugin_admin = 1");

	$plugins = array();
	if($r) {
		foreach ($r as $h){
			$plugin = $h['aname'];
			$plugins[] = array(z_root() . '/admin/plugins/' . $plugin, $plugin, 'plugin');
			// temp plugins with admin
			App::$plugins_admin[] = $plugin;
		}
	}

	$logs = array(z_root() . '/admin/logs/', t('Logs'), 'logs');

	$arr = array('links' => $aside,'plugins' => $plugins,'logs' => $logs);
	call_hooks('admin_aside',$arr);

	$o .= replace_macros(get_markup_template('admin_aside.tpl'), array(
			'$admin' => $aside,
			'$admtxt' => t('Admin'),
			'$plugadmtxt' => t('Plugin Features'),
			'$plugins' => $plugins,
			'$logtxt' => t('Logs'),
			'$logs' => $logs,
			'$h_pending' => t('Member registrations waiting for confirmation'),
			'$admurl'=> z_root() . '/admin/'
	));

	return $o;

}



function widget_album($args) {

	$owner_uid = App::$profile_uid;
	$sql_extra = permissions_sql($owner_uid);


	if(! perm_is_allowed($owner_uid,get_observer_hash(),'view_storage'))
		return '';

	if($args['album'])
		$album = $args['album'];
	if($args['title'])
		$title = $args['title'];

	/**
	 * This may return incorrect permissions if you have multiple directories of the same name.
	 * It is a limitation of the photo table using a name for a photo album instead of a folder hash
	 */

	if($album) {
		$x = q("select hash from attach where filename = '%s' and uid = %d limit 1",
			dbesc($album),
			intval($owner_uid)
		);
		if($x) {
			$y = attach_can_view_folder($owner_uid,get_observer_hash(),$x[0]['hash']);
			if(! $y)
				return '';
		}
	}

	$order = 'DESC';

	$r = q("SELECT p.resource_id, p.id, p.filename, p.mimetype, p.imgscale, p.description, p.created FROM photo p INNER JOIN
		(SELECT resource_id, max(imgscale) imgscale FROM photo WHERE uid = %d AND album = '%s' AND imgscale <= 4 AND photo_usage IN ( %d, %d ) $sql_extra GROUP BY resource_id) ph
		ON (p.resource_id = ph.resource_id AND p.imgscale = ph.imgscale)
		ORDER BY created $order ",
		intval($owner_uid),
		dbesc($album),
		intval(PHOTO_NORMAL),
		intval(PHOTO_PROFILE)
	);

	//edit album name
	$album_edit = null;

	$photos = array();
	if($r) {
		$twist = 'rotright';
		foreach($r as $rr) {

			if($twist == 'rotright')
				$twist = 'rotleft';
			else
				$twist = 'rotright';

			$ext = $phototypes[$rr['mimetype']];

			$imgalt_e = $rr['filename'];
			$desc_e = $rr['description'];

			$imagelink = (z_root() . '/photos/' . App::$profile['channel_address'] . '/image/' . $rr['resource_id']);


			$photos[] = array(
				'id' => $rr['id'],
				'twist' => ' ' . $twist . rand(2,4),
				'link' => $imagelink,
				'title' => t('View Photo'),
				'src' => z_root() . '/photo/' . $rr['resource_id'] . '-' . $rr['imgscale'] . '.' .$ext,
				'alt' => $imgalt_e,
				'desc'=> $desc_e,
				'ext' => $ext,
				'hash'=> $rr['resource_id'],
				'unknown' => t('Unknown')
			);
		}
	}


	$tpl = get_markup_template('photo_album.tpl');
	$o .= replace_macros($tpl, array(
		'$photos' => $photos,
		'$album' => (($title) ? $title : $album),
		'$album_id' => rand(),
		'$album_edit' => array(t('Edit Album'), $album_edit),
		'$can_post' => false,
		'$upload' => array(t('Upload'), z_root() . '/photos/' . App::$profile['channel_address'] . '/upload/' . bin2hex($album)),
		'$order' => false,
		'$upload_form' => $upload_form,
		'$usage' => $usage_message
	));

	return $o;
}

