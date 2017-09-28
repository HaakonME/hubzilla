<?php


/**
 * @brief Return an Atom feed for channel.
 *
 * @see get_feed_for()
 *
 * @param array $channel
 * @param array $params associative array which configures the feed
 * @return string with an atom feed
 */
function get_public_feed($channel, $params) {

/*	$type      = 'xml';
	$begin     = NULL_DATE;
	$end       = '';
	$start     = 0;
	$records   = 40;
	$direction = 'desc';
	$pages     = 0;
*/

	if(! $params)
		$params = [];

	$params['type']        = ((x($params,'type'))     ? $params['type']           : 'xml');
	$params['begin']       = ((x($params,'begin'))    ? $params['begin']          : NULL_DATE);
	$params['end']         = ((x($params,'end'))      ? $params['end']            : datetime_convert('UTC','UTC','now'));
	$params['start']       = ((x($params,'start'))    ? $params['start']          : 0);
	$params['records']     = ((x($params,'records'))  ? $params['records']        : 40);
	$params['direction']   = ((x($params,'direction'))? $params['direction']      : 'desc');
	$params['pages']       = ((x($params,'pages'))    ? intval($params['pages'])  : 0);
	$params['top']         = ((x($params,'top'))      ? intval($params['top'])    : 0);
	$params['cat']         = ((x($params,'cat'))      ? $params['cat']            : '');
	$params['compat']      = ((x($params,'compat'))   ? intval($params['compat']) : 0);


	switch($params['type']) {
		case 'json':
			header("Content-type: application/atom+json");
			break;
		case 'xml':
		default:
			header("Content-type: application/atom+xml");
			break;
	}

	return get_feed_for($channel, get_observer_hash(), $params);
}

/**
 * @brief Create an atom feed for $channel from template.
 *
 * @param array $channel
 * @param string $observer_hash xchan_hash from observer
 * @param array $params
 * @return string with an atom feed
 */

function get_feed_for($channel, $observer_hash, $params) {

	if(! $channel)
		http_status_exit(401);

	if($params['pages']) {
		if(! perm_is_allowed($channel['channel_id'],$observer_hash,'view_pages'))
			http_status_exit(403);
	} else {
		if(! perm_is_allowed($channel['channel_id'],$observer_hash,'view_stream'))
			http_status_exit(403);
	}

	// logger('params: ' . print_r($params,true));

	$feed_template = get_markup_template('atom_feed.tpl');

	$atom = '';

	$feed_author = '';
	if(intval($params['compat']) === 1) {
		$feed_author = atom_render_author('author',$channel);
	}

	$owner = atom_render_author('zot:owner',$channel);

	$atom .= replace_macros($feed_template, array(
		'$version'      => xmlify(Zotlabs\Lib\System::get_project_version()),
		'$red'          => xmlify(Zotlabs\Lib\System::get_platform_name()),
		'$feed_id'      => xmlify($channel['xchan_url']),
		'$feed_title'   => xmlify($channel['channel_name']),
		'$feed_updated' => xmlify(datetime_convert('UTC', 'UTC', 'now', ATOM_TIME)),
		'$author'       => $feed_author,
		'$owner'        => $owner,
		'$name'         => xmlify($channel['channel_name']),
		'$profile_page' => xmlify($channel['xchan_url']),
		'$mimephoto'    => xmlify($channel['xchan_photo_mimetype']),
		'$photo'        => xmlify($channel['xchan_photo_l']),
		'$thumb'        => xmlify($channel['xchan_photo_m']),
		'$picdate'      => '',
		'$uridate'      => '',
		'$namdate'      => '',
		'$birthday'     => '',
		'$community'    => '',
	));


	$x = [ 'xml' => $atom, 'channel' => $channel, 'observer_hash' => $observer_hash, 'params' => $params ];
	call_hooks('atom_feed_top',$x);

	$atom = $x['xml'];

	// a much simpler interface
	call_hooks('atom_feed', $atom);

	$items = items_fetch(
		[
			'wall'       => '1',
			'datequery'  => $params['end'],
			'datequery2' => $params['begin'],
			'start'      => intval($params['start']),
			'records'    => intval($params['records']),
			'direction'  => dbesc($params['direction']),
			'pages'      => $params['pages'],
			'order'      => dbesc('post'),
			'top'        => $params['top'],
			'cat'        => $params['cat'],
			'compat'     => $params['compat']
		], $channel, $observer_hash, CLIENT_MODE_NORMAL, App::$module
	);

	if($items) {
		$type = 'html';
		foreach($items as $item) {
			if($item['item_private'])
				continue;

			/** @BUG $owner is undefined in this call */
			$atom .= atom_entry($item, $type, null, $owner, true, '', $params['compat']);
		}
	}

	call_hooks('atom_feed_end', $atom);

	$atom .= '</feed>' . "\r\n";

	return $atom;
}

/**
 * @brief Return the verb for an item, or fall back to ACTIVITY_POST.
 *
 * @param array $item an associative array with
 *   * \e string \b verb
 * @return string item's verb if set, default ACTIVITY_POST see boot.php
 */
function construct_verb($item) {
	if ($item['verb'])
		return $item['verb'];

	return ACTIVITY_POST;
}

function construct_activity_object($item) {

	if($item['obj']) {
		$o = '<as:object>' . "\r\n";
		$r = json_decode($item['obj'],false);

		if(! $r)
			return '';
		if($r->type)
			$o .= '<as:obj_type>' . xmlify($r->type) . '</as:obj_type>' . "\r\n";
		if($r->id)
			$o .= '<id>' . xmlify($r->id) . '</id>' . "\r\n";
		if($r->title)
			$o .= '<title>' . xmlify($r->title) . '</title>' . "\r\n";
		if($r->links) {
			/** @FIXME!! */
			if(substr($r->link,0,1) === '<') {
				$r->link = preg_replace('/\<link(.*?)\"\>/','<link$1"/>',$r->link);
				$o .= $r->link;
			}
			else
				$o .= '<link rel="alternate" type="text/html" href="' . xmlify($r->link) . '" />' . "\r\n";
		}
		if($r->content) {
			$o .= '<content type="html" >' . xmlify(bbcode($r->content)) . '</content>' . "\r\n";
		}
		$o .= '</as:object>' . "\r\n";

		return $o;
	}

	return '';
}

function construct_activity_target($item) {

	if($item['target']) {
		$o = '<as:target>' . "\r\n";
		$r = json_decode($item['target'],false);
		if(! $r)
			return '';
		if($r->type)
			$o .= '<as:obj_type>' . xmlify($r->type) . '</as:obj_type>' . "\r\n";
		if($r->id)
			$o .= '<id>' . xmlify($r->id) . '</id>' . "\r\n";
		if($r->title)
			$o .= '<title>' . xmlify($r->title) . '</title>' . "\r\n";
		if($r->links) {
			/** @FIXME !!! */
			if(substr($r->link,0,1) === '<') {
				if(strstr($r->link,'&') && (! strstr($r->link,'&amp;')))
					$r->link = str_replace('&','&amp;', $r->link);
				$r->link = preg_replace('/\<link(.*?)\"\>/','<link$1"/>',$r->link);
				$o .= $r->link;
			}
			else
				$o .= '<link rel="alternate" type="text/html" href="' . xmlify($r->link) . '" />' . "\r\n";
		}
		if($r->content)
			$o .= '<content type="html" >' . xmlify(bbcode($r->content)) . '</content>' . "\r\n";

		$o .= '</as:target>' . "\r\n";

		return $o;
	}

	return '';
}

/**
 * @brief Return an array with a parsed atom item.
 *
 * @param SimplePie $feed
 * @param array $item
 * @param[out] array $author
 * @return array Associative array with the parsed item data
 */
function get_atom_elements($feed, $item, &$author) {

	require_once('include/html2bbcode.php');

	$res = array();

	$found_author = $item->get_author();
	if($found_author) {
		if($rawauthor) {
			if($rawauthor[0]['child'][NAMESPACE_POCO]['displayName'][0]['data'])
				$author['full_name'] = unxmlify($rawauthor[0]['child'][NAMESPACE_POCO]['displayName'][0]['data']);
		}
		$author['author_name'] = unxmlify($found_author->get_name());
		$author['author_link'] = unxmlify($found_author->get_link());
		$author['author_is_feed'] = false;

		$rawauthor = $feed->get_feed_tags(SIMPLEPIE_NAMESPACE_ATOM_10, 'author');
		//logger('rawauthor: ' . print_r($rawauthor, true));

	}
	else {
		$author['author_name'] = unxmlify($feed->get_title());
		$author['author_link'] = unxmlify($feed->get_permalink());
		$author['author_is_feed'] = true;
	}

	if(substr($author['author_link'],-1,1) == '/')
		$author['author_link'] = substr($author['author_link'],0,-1);

	$res['mid'] = normalise_id(unxmlify($item->get_id()));
	$res['title'] = unxmlify($item->get_title());
	$res['body'] = unxmlify($item->get_content());
	$res['plink'] = unxmlify($item->get_link(0));
	$res['item_rss'] = 1;


	$summary = unxmlify($item->get_description(true));

	// removing the content of the title if its identically to the body
	// This helps with auto generated titles e.g. from tumblr

	if (title_is_body($res['title'], $res['body']))
		$res['title'] = "";

	if($res['plink'])
		$base_url = implode('/', array_slice(explode('/',$res['plink']),0,3));
	else
		$base_url = '';


	$rawcreated = $item->get_item_tags(SIMPLEPIE_NAMESPACE_ATOM_10, 'published');
	if($rawcreated)
		$res['created'] = unxmlify($rawcreated[0]['data']);

	$rawedited = $item->get_item_tags(SIMPLEPIE_NAMESPACE_ATOM_10, 'updated');
	if($rawedited)
		$res['edited'] = unxmlify($rawedited[0]['data']);

	if((x($res,'edited')) && (! (x($res,'created'))))
		$res['created'] = $res['edited'];

	if(! $res['created'])
		$res['created'] = $item->get_date('c');

	if(! $res['edited'])
		$res['edited'] = $item->get_date('c');

	$rawverb = $item->get_item_tags(NAMESPACE_ACTIVITY, 'verb');

	// select between supported verbs

	if($rawverb) {
		$res['verb'] = unxmlify($rawverb[0]['data']);
	}


	// look for a photo. We should check media size and find the best one,
	// but for now let's just find any author photo

	$rawauthor = $item->get_item_tags(SIMPLEPIE_NAMESPACE_ATOM_10,'author');

	if($rawauthor && $rawauthor[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['link']) {
		$base = $rawauthor[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['link'];
		foreach($base as $link) {
			if(!x($author, 'author_photo') || ! $author['author_photo']) {
				if($link['attribs']['']['rel'] === 'photo' || $link['attribs']['']['rel'] === 'avatar')
					$author['author_photo'] = unxmlify($link['attribs']['']['href']);
			}
		}
	}
	$rawactor = $item->get_item_tags(NAMESPACE_ACTIVITY, 'actor');

	if($rawactor && activity_match($rawactor[0]['child'][NAMESPACE_ACTIVITY]['obj_type'][0]['data'], ACTIVITY_OBJ_PERSON)) {
		$base = $rawactor[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['link'];
		if($base && count($base)) {
			foreach($base as $link) {
				if($link['attribs']['']['rel'] === 'alternate' && (! $res['author_link']))
					$author['author_link'] = unxmlify($link['attribs']['']['href']);
				if(!x($author, 'author_photo') || ! $author['author_photo']) {
					if($link['attribs']['']['rel'] === 'avatar' || $link['attribs']['']['rel'] === 'photo')
						$author['author_photo'] = unxmlify($link['attribs']['']['href']);
				}
			}
		}
	}

	// check for a yahoo media element (github etc.)

	if(! $author['author_photo']) {
		$rawmedia = $item->get_item_tags(NAMESPACE_YMEDIA,'thumbnail');
		if($rawmedia && $rawmedia[0]['attribs']['']['url']) {
			$author['author_photo'] = strip_tags(unxmlify($rawmedia[0]['attribs']['']['url']));
		}
	}


	// No photo/profile-link on the item - look at the feed level


	if((! (x($author,'author_link'))) || (! (x($author,'author_photo')))) {
		$rawauthor = $feed->get_feed_tags(SIMPLEPIE_NAMESPACE_ATOM_10,'author');
		if($rawauthor && $rawauthor[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['link']) {
			$base = $rawauthor[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['link'];
			foreach($base as $link) {
				if($link['attribs']['']['rel'] === 'alternate' && (! $author['author_link'])) {
					$author['author_link'] = unxmlify($link['attribs']['']['href']);
					$author['author_is_feed'] = true;
				}
				if(! $author['author_photo']) {
					if($link['attribs']['']['rel'] === 'photo' || $link['attribs']['']['rel'] === 'avatar')
						$author['author_photo'] = unxmlify($link['attribs']['']['href']);
				}
			}
		}

		$rawactor = $feed->get_feed_tags(NAMESPACE_ACTIVITY, 'subject');

		if($rawactor && activity_match($rawactor[0]['child'][NAMESPACE_ACTIVITY]['obj_type'][0]['data'],ACTIVITY_OBJ_PERSON)) {
			$base = $rawactor[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['link'];

			if($base && count($base)) {
				foreach($base as $link) {
					if($link['attribs']['']['rel'] === 'alternate' && (! $res['author_link']))
						$author['author_link'] = unxmlify($link['attribs']['']['href']);
					if(! (x($author,'author_photo'))) {
						if($link['attribs']['']['rel'] === 'avatar' || $link['attribs']['']['rel'] === 'photo')
							$author['author_photo'] = unxmlify($link['attribs']['']['href']);
					}
				}
			}
		}
	}

	$rawcnv = $item->get_item_tags(NAMESPACE_OSTATUS, 'conversation');
	if($rawcnv) {
		// new style
		$ostatus_conversation = normalise_id(unxmlify($rawcnv[0]['attribs']['']['ref']));
		if(! $ostatus_conversation) {
			// old style 
			$ostatus_conversation = normalise_id(unxmlify($rawcnv[0]['data']));
		}
		if($ostatus_conversation) {
			set_iconfig($res,'ostatus','conversation',$ostatus_conversation,true);
			logger('ostatus_conversation: ' . $ostatus_conversation, LOGGER_DATA, LOG_INFO);
		}
	}

	$ostatus_protocol = (($ostatus_conversation) ? true : false);
	
	$mastodon = (($item->get_item_tags('http://mastodon.social/schema/1.0','scope')) ? true : false);
	if($mastodon) {
		$ostatus_protocol = true;
		if(($mastodon[0]['data']) && ($mastodon[0]['data'] !== 'public'))
			$res['item_private'] = 1;
	}

	$apps = $item->get_item_tags(NAMESPACE_STATUSNET, 'notice_info');
	if($apps && $apps[0]['attribs']['']['source']) {
		$res['app'] = strip_tags(unxmlify($apps[0]['attribs']['']['source']));
	}

	if($ostatus_protocol) {

		// translate OStatus unfollow to activity streams if it happened to get selected

		if((x($res,'verb')) && ($res['verb'] === 'http://ostatus.org/schema/1.0/unfollow')) {
			$res['verb'] = ACTIVITY_UNFOLLOW;
		}

		// And OStatus 'favorite' is pretty much what we call 'like' on other networks

		if((x($res,'verb')) && ($res['verb'] === ACTIVITY_FAVORITE)) {
			$res['verb'] = ACTIVITY_LIKE;
		}
	}

	/*
	 * If there's a copy of the body content which is guaranteed to have survived mangling in transit, use it.
	 */

	$have_real_body = false;

	$rawenv = $item->get_item_tags(NAMESPACE_DFRN, 'env');
	if(! $rawenv)
		$rawenv = $item->get_item_tags(NAMESPACE_ZOT, 'source');
	if($rawenv) {
		$have_real_body = true;
		$res['body'] = $rawenv[0]['data'];
		$res['body'] = str_replace(array(' ',"\t","\r","\n"), array('','','',''),$res['body']);
		// make sure nobody is trying to sneak some html tags by us
		$res['body'] = notags(base64url_decode($res['body']));

		// We could probably turn these old Friendica bbcode bookmarks into bookmark tags but we'd have to
		// create a term table item for them. For now just make sure they stay as links.

		$res['body'] = preg_replace('/\[bookmark(.*?)\](.*?)\[\/bookmark\]/','[url$1]$2[/url]',$res['body']);
	}

	$res['body'] = limit_body_size($res['body']);

	// It isn't certain at this point whether our content is plaintext or html and we'd be foolish to trust
	// the content type. Our own network only emits text normally, though it might have been converted to
	// html if we used a pubsubhubbub transport. But if we see even one html tag in our text, we will
	// have to assume it is all html and needs to be purified.

	// It doesn't matter all that much security wise - because before this content is used anywhere, we are
	// going to escape any tags we find regardless, but this lets us import a limited subset of html from
	// the wild, by sanitising it and converting supported tags to bbcode before we rip out any remaining
	// html.

	if((strpos($res['body'],'<') !== false) && (strpos($res['body'],'>') !== false)) {

		$res['body'] = reltoabs($res['body'],$base_url);

		$res['body'] = html2bb_video($res['body']);

		$res['body'] = oembed_html2bbcode($res['body']);

		$res['body'] = purify_html($res['body']);

		$res['body'] = @html2bbcode($res['body']);
	}
	elseif(! $have_real_body) {

		// it's not one of our messages and it has no tags
		// so it's probably just text. We'll escape it just to be safe.

		$res['body'] = escape_tags($res['body']);
	}


	// strip title and don't apply "title-in-body" if the feed involved
	// uses the OStatus stack. We need a more generalised way for the calling
	// function to specify this behaviour or for plugins to alter it.

	if($ostatus_protocol) {
		$res['title'] = '';
	}
	elseif($res['plink'] && $res['title']) {
		$res['body'] = '#^[url=' . $res['plink'] . ']' . $res['title'] . '[/url]' . "\n\n" . $res['body'];
		$terms = array();
		$terms[] = array(
			'otype' => TERM_OBJ_POST,
			'ttype' => TERM_BOOKMARK,
			'url'   => $res['plink'],
			'term'  => $res['title'],
		);
	}
	elseif($res['plink']) {
		$res['body'] = '#^[url]' . $res['plink'] . '[/url]' . "\n\n" . $res['body'];
		$terms = array();
		$terms[] = array(
			'otype' => TERM_OBJ_POST,
			'ttype' => TERM_BOOKMARK,
			'url'   => $res['plink'],
			'term'  => $res['plink'],
		);
	}

	// turn Mastodon content warning into a #nsfw hashtag
	if($mastodon && $summary) {
		$res['body'] = $summary . "\n\n" . $res['body'] . "\n\n#ContentWarning\n";
	}


	$private = $item->get_item_tags(NAMESPACE_DFRN, 'private');
	if($private && intval($private[0]['data']) > 0)
		$res['item_private'] = ((intval($private[0]['data'])) ? 1 : 0);
	else
		$res['item_private'] = 0;

	$rawlocation = $item->get_item_tags(NAMESPACE_DFRN, 'location');
	if($rawlocation)
		$res['location'] = unxmlify($rawlocation[0]['data']);


	// Disallow time travelling posts

	$d1 = strtotime($res['created']);
	$d2 = strtotime($res['edited']);
	$d3 = strtotime('now');

	if($d1 > $d3)
		$res['created'] = datetime_convert();
	if($d2 > $d3)
		$res['edited'] = datetime_convert();

	$res['created'] = datetime_convert('UTC','UTC',$res['created']);
	$res['edited'] = datetime_convert('UTC','UTC',$res['edited']);

	$rawowner = $item->get_item_tags(NAMESPACE_DFRN, 'owner');
	if(! $rawowner)
		$rawowner = $item->get_item_tags(NAMESPACE_ZOT, 'owner');

	if($rawowner[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['name'][0]['data'])
		$author['owner_name'] = unxmlify($rawowner[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['name'][0]['data']);
	elseif($rawowner[0]['child'][NAMESPACE_DFRN]['name'][0]['data'])
		$author['owner_name'] = unxmlify($rawowner[0]['child'][NAMESPACE_DFRN]['name'][0]['data']);
	if($rawowner[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['uri'][0]['data'])
		$author['owner_link'] = unxmlify($rawowner[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['uri'][0]['data']);
	elseif($rawowner[0]['child'][NAMESPACE_DFRN]['uri'][0]['data'])
		$author['owner_link'] = unxmlify($rawowner[0]['child'][NAMESPACE_DFRN]['uri'][0]['data']);

	if($rawowner[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['link']) {
		$base = $rawowner[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['link'];

		foreach($base as $link) {
			if(!x($author, 'owner_photo') || ! $author['owner_photo']) {
				if($link['attribs']['']['rel'] === 'photo' || $link['attribs']['']['rel'] === 'avatar')
					$author['owner_photo'] = unxmlify($link['attribs']['']['href']);
			}
		}
	}

	$rawgeo = $item->get_item_tags(NAMESPACE_GEORSS, 'point');
	if($rawgeo)
		$res['coord'] = unxmlify($rawgeo[0]['data']);


	$cats = $item->get_categories();
	if($cats) {
		if(is_null($terms))
			$terms = array();

		foreach($cats as $cat) {
			$term = $cat->get_term();
			if(! $term)
				$term = $cat->get_label();

			$scheme = $cat->get_scheme();
			$termurl = '';
			if($scheme && $term && stristr($scheme,'X-DFRN:')) {
				$termtype = ((substr($scheme,7,1) === '#') ? TERM_HASHTAG : TERM_MENTION);
				$termurl = unxmlify(substr($scheme,9));
			}
			else {
				$termtype = TERM_CATEGORY;
			}
			$termterm = notags(trim(unxmlify($term)));

			if($termterm) {
				$terms[] = array(
					'otype' => TERM_OBJ_POST,
					'ttype' => $termtype,
					'url'   => $termurl,
					'term'  => $termterm,
				);
			}
		}
	}

	if(! is_null($terms))
		$res['term'] =  $terms;

	$attach = $item->get_enclosures();
	if($attach) {

		$res['attach'] = array();
		foreach($attach as $att) {
			$len   = intval($att->get_length());
			$link  = str_replace(array(',','"'),array('%2D','%22'),notags(trim(unxmlify($att->get_link()))));
			$title = str_replace(array(',','"'),array('%2D','%22'),notags(trim(unxmlify($att->get_title()))));
			$type  = str_replace(array(',','"'),array('%2D','%22'),notags(trim(unxmlify($att->get_type()))));
			if(strpos($type,';'))
				$type = substr($type,0,strpos($type,';'));
			if((! $link) || (strpos($link,'http') !== 0))
				continue;

			if(! $title)
				$title = ' ';
			if(! $type)
				$type = 'application/octet-stream';

			if($ostatus_protocol)  {
				if((strpos($type,'image') === 0) && (strpos($res['body'], ']' . $link . '[/img]') === false) && (strpos($link,'http') === 0)) {
					$res['body'] .= "\n\n" . '[img]' . $link . '[/img]';
				}
				if((strpos($type,'video') === 0) && (strpos($res['body'], ']' . $link . '[/video]') === false) && (strpos($link,'http') === 0)) {
					$res['body'] .= "\n\n" . '[video]' . $link . '[/video]';
				}
				if((strpos($type,'audio') === 0) && (strpos($res['body'], ']' . $link . '[/audio]') === false) && (strpos($link,'http') === 0)) {
					$res['body'] .= "\n\n" . '[audio]' . $link . '[/audio]';
				}
			}
			$res['attach'][] = array('href' => $link, 'length' => $len, 'type' => $type, 'title' => $title );
		}
	}
	

	$rawobj = $item->get_item_tags(NAMESPACE_ACTIVITY, 'object');

	if($rawobj) {
		$obj = array();

		$child = $rawobj[0]['child'];
		if($child[NAMESPACE_ACTIVITY]['obj_type'][0]['data']) {
			$res['obj_type'] = $child[NAMESPACE_ACTIVITY]['obj_type'][0]['data'];
			$obj['type'] = $child[NAMESPACE_ACTIVITY]['obj_type'][0]['data'];
		}
		if($child[NAMESPACE_ACTIVITY]['object-type'][0]['data']) {
			$res['obj_type'] = $child[NAMESPACE_ACTIVITY]['object-type'][0]['data'];
			$obj['type'] = $child[NAMESPACE_ACTIVITY]['object-type'][0]['data'];
		}
		if(x($child[SIMPLEPIE_NAMESPACE_ATOM_10], 'id') && $child[SIMPLEPIE_NAMESPACE_ATOM_10]['id'][0]['data'])
			$obj['id'] = $child[SIMPLEPIE_NAMESPACE_ATOM_10]['id'][0]['data'];
		if(x($child[SIMPLEPIE_NAMESPACE_ATOM_10], 'link') && $child[SIMPLEPIE_NAMESPACE_ATOM_10]['link'])
			$obj['link'] = encode_rel_links($child[SIMPLEPIE_NAMESPACE_ATOM_10]['link']);
		if(x($child[SIMPLEPIE_NAMESPACE_ATOM_10], 'title') && $child[SIMPLEPIE_NAMESPACE_ATOM_10]['title'][0]['data'])
			$obj['title'] =  $child[SIMPLEPIE_NAMESPACE_ATOM_10]['title'][0]['data'];
		if(x($child[SIMPLEPIE_NAMESPACE_ATOM_10], 'content') && $child[SIMPLEPIE_NAMESPACE_ATOM_10]['content'][0]['data']) {
			$body = $child[SIMPLEPIE_NAMESPACE_ATOM_10]['content'][0]['data'];
			if(! $body)
				$body = $child[SIMPLEPIE_NAMESPACE_ATOM_10]['summary'][0]['data'];

			// preserve a copy of the original body content in case we later need to parse out any microformat information, e.g. events
			$obj['orig'] = xmlify($body);
			if((strpos($body,'<') !== false) || (strpos($body,'>') !== false)) {
				$body = purify_html($body);
				$body = html2bbcode($body);
			}

			$obj['content'] = $body;
		}

		$res['obj'] = $obj;
	}

	$rawobj = $item->get_item_tags(NAMESPACE_ACTIVITY, 'target');

	if($rawobj) {
		$obj = array();

		$child = $rawobj[0]['child'];
		if($child[NAMESPACE_ACTIVITY]['obj_type'][0]['data']) {
			$res['tgt_type'] = $child[NAMESPACE_ACTIVITY]['obj_type'][0]['data'];
			$obj['type'] = $child[NAMESPACE_ACTIVITY]['obj_type'][0]['data'];
		}
		if($child[NAMESPACE_ACTIVITY]['object-type'][0]['data']) {
			$res['tgt_type'] = $child[NAMESPACE_ACTIVITY]['object-type'][0]['data'];
			$obj['type'] = $child[NAMESPACE_ACTIVITY]['object-type'][0]['data'];
		}
		if(x($child[SIMPLEPIE_NAMESPACE_ATOM_10], 'id') && $child[SIMPLEPIE_NAMESPACE_ATOM_10]['id'][0]['data'])
			$obj['id'] = $child[SIMPLEPIE_NAMESPACE_ATOM_10]['id'][0]['data'];
		if(x($child[SIMPLEPIE_NAMESPACE_ATOM_10], 'link') && $child[SIMPLEPIE_NAMESPACE_ATOM_10]['link'])
			$obj['link'] = encode_rel_links($child[SIMPLEPIE_NAMESPACE_ATOM_10]['link']);
		if(x($child[SIMPLEPIE_NAMESPACE_ATOM_10], 'title') && $child[SIMPLEPIE_NAMESPACE_ATOM_10]['title'][0]['data'])
			$obj['title'] =  $child[SIMPLEPIE_NAMESPACE_ATOM_10]['title'][0]['data'];
		if(x($child[SIMPLEPIE_NAMESPACE_ATOM_10], 'content') && $child[SIMPLEPIE_NAMESPACE_ATOM_10]['content'][0]['data']) {
			$body = $child[SIMPLEPIE_NAMESPACE_ATOM_10]['content'][0]['data'];
			if(! $body)
				$body = $child[SIMPLEPIE_NAMESPACE_ATOM_10]['summary'][0]['data'];

			// preserve a copy of the original body content in case we later need to parse out any microformat information, e.g. events
			$obj['orig'] = xmlify($body);
			if((strpos($body,'<') !== false) || (strpos($body,'>') !== false)) {
				$body = purify_html($body);
				$body = html2bbcode($body);
			}

			$obj['content'] = $body;
		}

		$res['target'] = $obj;
	}

	

	if(array_key_exists('verb',$res) && $res['verb'] === ACTIVITY_SHARE
		&& array_key_exists('obj_type',$res) && in_array($res['obj_type'], [ ACTIVITY_OBJ_NOTE, ACTIVITY_OBJ_COMMENT, ACTIVITY_OBJ_ACTIVITY ] )) {
		feed_get_reshare($res,$item);
	}

	// build array to pass to hook
	$arr = [
			'feed'   => $feed,
			'item'   => $item,
			'author' => $author,
			'result' => $res
	];

	call_hooks('parse_atom', $arr);

	logger('author: ' .print_r($arr['author'], true), LOGGER_DATA);
	logger('result: ' .print_r($arr['result'], true), LOGGER_DATA);

	return $arr['result'];
}

function feed_get_reshare(&$res,$item) {

	$share = [];

	// For Mastodon shares ("boosts"), we need to parse the original author information
	// from the activity:object -> author structure
	$rawobj = $item->get_item_tags(NAMESPACE_ACTIVITY, 'object');

	if($rawobj) {

		$rawauthor = $rawobj[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['author'];

		if($rawauthor && $rawauthor[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['name']) {
			$share['author'] = unxmlify($rawauthor[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['name'][0]['data']);
		}

		if($rawauthor && $rawauthor[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['uri']) {
			$share['profile'] = unxmlify($rawauthor[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['uri'][0]['data']);
		}

		if($rawauthor && $rawauthor[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['link']) {
			$base = $rawauthor[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['link'];
			foreach($base as $link) {
				if(! (array_key_exists('avatar',$share) && $share['avatar'])) {
					if($link['attribs']['']['rel'] === 'photo' || $link['attribs']['']['rel'] === 'avatar')
						$share['avatar'] = unxmlify($link['attribs']['']['href']);
				}
			}
		}

		if(! $share['author'])
			$share['author'] = t('unknown');
		if(! $share['avatar'])
			$share['avatar'] = z_root() . '/' . get_default_profile_photo(80);
		if(! $share['profile'])
			$share['profile'] = z_root();
	
		$child = $rawobj[0]['child'];


		if(x($child[SIMPLEPIE_NAMESPACE_ATOM_10], 'link') && $child[SIMPLEPIE_NAMESPACE_ATOM_10]['link'])
			$share['links'] = encode_rel_links($child[SIMPLEPIE_NAMESPACE_ATOM_10]['link']);

		$rawcreated = $rawobj[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['published'];

		if($rawcreated)
			$share['created'] = unxmlify($rawcreated[0]['data']);
		else
			$share['created'] = $res['created'];

		if(x($child[SIMPLEPIE_NAMESPACE_ATOM_10], 'id') && $child[SIMPLEPIE_NAMESPACE_ATOM_10]['id'][0]['data'])
			$share['message_id'] = unxmlify($child[SIMPLEPIE_NAMESPACE_ATOM_10]['id'][0]['data']);

		if(x($child[SIMPLEPIE_NAMESPACE_ATOM_10], 'content') && $child[SIMPLEPIE_NAMESPACE_ATOM_10]['content'][0]['data']) {
			$body = unxmlify($child[SIMPLEPIE_NAMESPACE_ATOM_10]['content'][0]['data']);
			if(! $body)
				$body = unxmlify($child[SIMPLEPIE_NAMESPACE_ATOM_10]['summary'][0]['data']);

			if((strpos($body,'<') !== false) || (strpos($body,'>') !== false)) {
				$body = purify_html($body);
				$body = html2bbcode($body);
			}
		}
	
		$attach = $share['links'];

		if($attach) {
			foreach($attach as $att) {
				if($att['rel'] === 'alternate') {
					$share['alternate'] = str_replace(array(',','"'),array('%2D','%22'),notags(trim(unxmlify($att['href']))));
					continue;
				}
				if($att['rel'] !== 'enclosure')
					continue;
				$len   = intval($att['length']);
				$link  = str_replace(array(',','"'),array('%2D','%22'),notags(trim(unxmlify($att['href']))));
				$title = str_replace(array(',','"'),array('%2D','%22'),notags(trim(unxmlify($att['title']))));
				$type  = str_replace(array(',','"'),array('%2D','%22'),notags(trim(unxmlify($att['type']))));
				if(strpos($type,';'))
					$type = substr($type,0,strpos($type,';'));
				if((! $link) || (strpos($link,'http') !== 0))
					continue;

				if(! $title)
					$title = ' ';
				if(! $type)
					$type = 'application/octet-stream';

				if((strpos($type,'image') === 0) && (strpos($body, ']' . $link . '[/img]') === false) && (strpos($link,'http') === 0)) {
					$body .= "\n\n" . '[img]' . $link . '[/img]';
				}
				if((strpos($type,'video') === 0) && (strpos($body, ']' . $link . '[/video]') === false) && (strpos($link,'http') === 0)) {
					$body .= "\n\n" . '[video]' . $link . '[/video]';
				}
				if((strpos($type,'audio') === 0) && (strpos($body, ']' . $link . '[/audio]') === false) && (strpos($link,'http') === 0)) {
					$body .= "\n\n" . '[audio]' . $link . '[/audio]';
				}
			}
		}
	
		if((! $body) && ($share['alternate'])) {
			$body = $share['alternate'];
		}			

		$res['body'] = "[share author='" . urlencode($share['author']) . 
			"' profile='"    . $share['profile'] .
			"' avatar='"     . $share['avatar']  .
			"' link='"       . $share['alternate']    .
			"' posted='"     . $share['created'] . 
			"' message_id='" . $share['message_id'] . "']";

		$res['body'] .= $body;
		$res['body'] .= "[/share]";
	}

}



/**
 * @brief Encodes SimplePie_Item link arrays.
 *
 * @param array $links Array with SimplePie_Item link tags
 * @return array
 */
function encode_rel_links($links) {
	$o = array();
	if(! ((is_array($links)) && (count($links))))
		return $o;

	foreach($links as $link) {
		$l = array();
		if($link['attribs']['']['rel'])
			$l['rel'] =  $link['attribs']['']['rel'];
		if($link['attribs']['']['length'])
			$l['length'] =  $link['attribs']['']['length'];
		if($link['attribs']['']['title'])
			$l['title'] =  $link['attribs']['']['title'];
		if($link['attribs']['']['type'])
			$l['type'] =  $link['attribs']['']['type'];
		if($link['attribs']['']['href'])
			$l['href'] = $link['attribs']['']['href'];
		if( (x($link['attribs'], NAMESPACE_MEDIA)) && $link['attribs'][NAMESPACE_MEDIA]['width'])
			$l['width'] = $link['attribs'][NAMESPACE_MEDIA]['width'];
		if( (x($link['attribs'], NAMESPACE_MEDIA)) && $link['attribs'][NAMESPACE_MEDIA]['height'])
			$l['height'] = $link['attribs'][NAMESPACE_MEDIA]['height'];

		if($l)
			$o[] = $l;
	}

	return $o;
}


function process_feed_tombstones($feed,$importer,$contact,$pass) {

	$arr_deleted = [];

	$del_entries = $feed->get_feed_tags(NAMESPACE_TOMB, 'deleted-entry');
	if(is_array($del_entries) && count($del_entries) && $pass != 2) {
		foreach($del_entries as $dentry) {
			if(isset($dentry['attribs']['']['ref'])) {
				$arr_deleted[] = normalise_id($dentry['attribs']['']['ref']);
			}
		}
	}

	if($arr_deleted && is_array($contact)) {
		foreach($arr_deleted as $mid) {
			$r = q("SELECT * from item where mid = '%s' and author_xchan = '%s' and uid = %d limit 1",
				dbesc($mid),
				dbesc($contact['xchan_hash']),
				intval($importer['channel_id'])
			);

			if($r) {
				$item = $r[0];

				if(! intval($item['item_deleted'])) {
					logger('deleting item ' . $item['id'] . ' mid=' . $item['mid'], LOGGER_DEBUG);
					drop_item($item['id'],false);
				}
			}
		}
	}
}


/**
 * @brief Process atom feed and update anything/everything we might need to update.
 *
 * @param string $xml
 *   The (atom) feed to consume - RSS isn't as fully supported but may work for simple feeds.
 * @param $importer
 *   The contact_record (joined to user_record) of the local user who owns this
 *   relationship. It is this person's stuff that is going to be updated.
 * @param[in,out] array $contact
 *   The person who is sending us stuff. If not set, we MAY be processing a "follow" activity
 *   from an external network and MAY create an appropriate contact record. Otherwise, we MUST
 *   have a contact record.
 * @param int $pass by default ($pass = 0) we cannot guarantee that a parent item has been
 *   imported prior to its children being seen in the stream unless we are certain
 *   of how the feed is arranged/ordered.
 *  * With $pass = 1, we only pull parent items out of the stream.
 *  * With $pass = 2, we only pull children (comments/likes).
 *
 * So running this twice, first with pass 1 and then with pass 2 will do the right
 * thing regardless of feed ordering. This won't be adequate in a fully-threaded
 * model where comments can have sub-threads. That would require some massive sorting
 * to get all the feed items into a mostly linear ordering, and might still require
 * recursion.
 */
function consume_feed($xml, $importer, &$contact, $pass = 0) {

	if(! strlen($xml)) {
		logger('Empty input');
		return;
	}

	$sys_expire = intval(get_config('system', 'default_expire_days'));
	$chn_expire = intval($importer['channel_expire_days']);

	$expire_days = $sys_expire;

	if(($chn_expire != 0) && ($chn_expire < $sys_expire))
		$expire_days = $chn_expire;

	$feed = new SimplePie();
	$feed->set_raw_data($xml);

	// We can preserve iframes because we will strip them in the purifier after
	// checking for supported video sources.
	$strip_htmltags = $feed->strip_htmltags;
	array_splice($strip_htmltags, array_search('iframe', $strip_htmltags), 1);
	$feed->strip_htmltags($strip_htmltags);

	$feed->init();

	if($feed->error())
		logger('Error parsing XML: ' . $feed->error());

	$permalink = $feed->get_permalink();


	// Check at the feed level for tombstones

	process_feed_tombstones($feed,$importer,$contact,$pass);


	// Now process the feed

	if($feed->get_item_quantity()) {

		logger('feed item count = ' . $feed->get_item_quantity(), LOGGER_DEBUG);

		$items = $feed->get_items();

		foreach($items as $item) {

			$is_reply = false;
			$send_downstream = false;
			$parent_link = '';

			logger('processing ' . $item->get_id(), LOGGER_DEBUG);

			$rawthread = $item->get_item_tags( NAMESPACE_THREAD,'in-reply-to');
			if(isset($rawthread[0]['attribs']['']['ref'])) {
				$is_reply = true;
				$parent_mid = normalise_id($rawthread[0]['attribs']['']['ref']);
			}
			if(isset($rawthread[0]['attribs']['']['href'])) {
				$parent_link = $rawthread[0]['attribs']['']['href'];
			}

			logger('in-reply-to: ' . $parent_mid, LOGGER_DEBUG);

			if($is_reply) {

				if($pass == 1)
					continue;

				// Have we seen it? If not, import it.

				$author = array();
				$datarray = get_atom_elements($feed,$item,$author);

				if(! $datarray['mid'])
					continue;


				$item_parent_mid = q("select parent_mid from item where mid = '%s' and uid = %d limit 1",
					dbesc($parent_mid),
					intval($importer['channel_id'])
				);


				// This probably isn't an appropriate default but we're about to change it
				// if it's wrong.

				$datarray['comment_policy'] = 'authenticated';

				// A Mastodon privacy tag has been found. We cannot send private comments
				// through the OStatus protocol, so block commenting.

				if(array_key_exists('item_private',$datarray) && intval($datarray['item_private'])) {
					$datarray['public_policy'] = 'specific';
					$datarray['comment_policy'] = 'none';
				}

				if($contact['xchan_network'] === 'rss') {
					$datarray['public_policy'] = 'specific';
					$datarray['comment_policy'] = 'none';
				}

				// if we have everything but a photo, provide the default profile photo

				if($author['author_name'] && $author['author_link'] && (! $author['author_photo']))
					$author['author_photo'] = z_root() . '/' . get_default_profile_photo(80);

				if((! x($author,'author_name')) || ($author['author_is_feed']))
					$author['author_name'] = $contact['xchan_name'];
				if((! x($author,'author_link')) || ($author['author_is_feed']))
					$author['author_link'] = $contact['xchan_url'];
				if((! x($author,'author_photo'))|| ($author['author_is_feed']))
					$author['author_photo'] = $contact['xchan_photo_m'];

				$datarray['author_xchan'] = '';

				if($author['author_link'] != $contact['xchan_url']) {
					$name = '';
					if($author['full_name']) {
						$name = $author['full_name'];
						if($author['author_name'])
							$name .= ' (' . $author['author_name'] . ')';
					}
					else {
						$name = $author['author_name'];
					}
					$x = import_author_unknown(array('name' => $name,'url' => $author['author_link'],'photo' => array('src' => $author['author_photo'])));
					if($x)
						$datarray['author_xchan'] = $x;
				}
				if(! $datarray['author_xchan'])
					$datarray['author_xchan'] = $contact['xchan_hash'];

				$datarray['owner_xchan'] = $contact['xchan_hash'];

				$r = q("SELECT id, edited, author_xchan, item_deleted FROM item WHERE mid = '%s' AND uid = %d LIMIT 1",
					dbesc($datarray['mid']),
					intval($importer['channel_id'])
				);


				// Update content if 'updated' changes

				if($r) {
					if(activity_match($datarray['verb'],ACTIVITY_DELETE) 
						&& $datarray['author_xchan'] === $r[0]['author_xchan']) {
						if(! intval($r[0]['item_deleted'])) {
							logger('deleting item ' . $r[0]['id'] . ' mid=' . $datarray['mid'], LOGGER_DEBUG);
							drop_item($r[0]['id'],false);
						}
						continue;
					}

					if((x($datarray,'edited') !== false)
						&& (datetime_convert('UTC','UTC',$datarray['edited']) !== $r[0]['edited'])) {

						// do not accept (ignore) an earlier edit than one we currently have.
						if(datetime_convert('UTC','UTC',$datarray['edited']) < $r[0]['edited'])
							continue;

						$datarray['uid'] = $importer['channel_id'];
						$datarray['aid'] = $importer['channel_account_id'];
						$datarray['id'] = $r[0]['id'];

						update_feed_item($importer['channel_id'],$datarray);

					}
					continue;
				}

				$pmid = '';
				$conv_id = get_iconfig($datarray,'ostatus','conversation');

				// match conversations - first try ostatus:conversation
				// next try thr:in_reply_to

				if($conv_id) {
					logger('find_parent: conversation_id: ' . $conv_id, LOGGER_DEBUG);
					$c = q("select parent_mid from item left join iconfig on item.id = iconfig.iid where iconfig.cat = 'ostatus' and iconfig.k = 'conversation' and iconfig.v = '%s' and item.uid = %d order by item.id limit 1",
						dbesc($conv_id),
						intval($importer['channel_id'])
					);
					if($c) {
						logger('find_parent: matched conversation: ' . $conv_id, LOGGER_DEBUG);
						$pmid = $c[0]['parent_mid'];
						$datarray['parent_mid'] = $pmid;
					}
				}
				if(($item_parent_mid) && (! $pmid)) {
					logger('find_parent: matched in-reply-to: ' . $parent_mid, LOGGER_DEBUG);
					$pmid = $item_parent_mid[0]['parent_mid'];
					$datarray['parent_mid'] = $pmid;
				}

				if((! $pmid) && $parent_link !== '') {
					$f = feed_conversation_fetch($importer,$contact,$parent_link);
					if($f) {
						// check both potential conversation parents again
						if($conv_id) {
							$c = q("select parent_mid from item left join iconfig on item.id = iconfig.iid where iconfig.cat = 'ostatus' and iconfig.k = 'conversation' and iconfig.v = '%s' and item.uid = %d order by item.id limit 1",
								dbesc($conv_id),
								intval($importer['channel_id'])
							);
							if($c) {
								$pmid = $c[0]['parent_mid'];
								$datarray['parent_mid'] = $pmid;
							}
						}
						if(! $pmid) {
							$x = q("select parent_mid from item where mid = '%s' and uid = %d limit 1",
								dbesc($parent_mid),
								intval($importer['channel_id'])
							);
				
							if($x) {
								$item_parent_mid = $x;
								$pmid = $x[0]['parent_mid'];
								$datarray['parent_mid'] = $pmid;
							}
						}
					}

					// the conversation parent might just be the post we are trying to import.
					// check existence again in case it was just delivered.

					$r = q("SELECT id FROM item WHERE mid = '%s' AND uid = %d LIMIT 1",
						dbesc($datarray['mid']),
						intval($importer['channel_id'])
					);
					if($r) {
						continue;
					}
				}

				if($pmid) {

					// check comment permissions on the parent

					$parent_item = 0;

					$r = q("select * from item where parent_mid = '%s' and parent_mid = mid and uid = %d limit 1",
						dbesc($pmid),
						intval($importer['channel_id'])
					);
					if($r) {
						$parent_item = $r[0];
						if(intval($parent_item['item_nocomment']) || $parent_item['comment_policy'] === 'none' 
							|| ($parent_item['comments_closed'] > NULL_DATE && $parent_item['comments_closed'] < datetime_convert())) {
							logger('comments disabled for post ' . $parent_item['mid']);
							continue;
						}
					}

					$allowed = false;

					if($parent_item) {
						if($parent_item['owner_xchan'] == $importer['channel_hash']) 
							$allowed = perm_is_allowed($importer['channel_id'],$contact['xchan_hash'],'post_comments');
						else
							$allowed = true;

						if(! $allowed) {
							logger('Ignoring this comment author.');
							$status = 202;
							continue;
						}

						// The salmon endpoint sets this to indicate that we should send comments from
						// interactive feeds (such as OStatus) downstream to our followers
						// We do not want to set it for non-interactive feeds or conversations we do not own

						if(array_key_exists('send_downstream',$importer) && intval($importer['send_downstream']) 
							&& ($parent_item['owner_xchan'] == $importer['channel_hash'])) {
							$send_downstream = true;
						}
					}
					else {
						if((! perm_is_allowed($importer['channel_id'],$contact['xchan_hash'],'send_stream')) && (! $importer['system'])) {
							// @fixme check for and process ostatus autofriend
							// otherwise 

							logger('Ignoring this author.');
							continue;
						}
					}
				}
				else {

					// immediate parent wasn't found. Turn into a top-level post if permissions allow
					// but save the thread_parent in case we need to refer to it later.
  
					if(! post_is_importable($datarray, $contact))
						continue;
					$datarray['parent_mid'] = $datarray['mid'];
					set_iconfig($datarray,'system','parent_mid',$parent_mid,true);
				}
				

				// allow likes of comments

				if($item_parent_mid && activity_match($datarray['verb'],ACTVITY_LIKE)) {
					$datarray['thr_parent'] = $item_parent_mid[0]['parent_mid'];
				}

				$datarray['aid'] = $importer['channel_account_id'];
				$datarray['uid'] = $importer['channel_id'];

				logger('data: ' . print_r($datarray, true), LOGGER_DATA);

				$xx = item_store($datarray);
				$r = $xx['item_id'];

				if($send_downstream) {
					\Zotlabs\Daemon\Master::Summon(array('Notifier', 'comment', $r));
				}

				continue;
			}
			else {

				// Head post of a conversation. Have we seen it? If not, import it.

				$author = array();
				$datarray = get_atom_elements($feed,$item,$author);

				if(! $datarray['mid'])
					continue;

				// This probably isn't an appropriate default but we're about to change it
				// if it's wrong.

				$datarray['comment_policy'] = 'authenticated';

				// A Mastodon privacy tag has been found. We cannot send private comments
				// through the OStatus protocol, so block commenting.

				if(array_key_exists('item_private',$datarray) && intval($datarray['item_private'])) {
					$datarray['public_policy'] = 'specific';
					$datarray['comment_policy'] = 'none';
				}

				if($contact['xchan_network'] === 'rss') {
					$datarray['public_policy'] = 'specific';
					$datarray['comment_policy'] = 'none';
				}



				// if we have everything but a photo, provide the default profile photo

				if($author['author_name'] && $author['author_link'] && (! $author['author_photo']))
					$author['author_photo'] = z_root() . '/' . get_default_profile_photo(80);

				if(is_array($contact)) {
					if((! x($author,'author_name')) || ($author['author_is_feed']))
						$author['author_name'] = $contact['xchan_name'];
					if((! x($author,'author_link')) || ($author['author_is_feed']))
						$author['author_link'] = $contact['xchan_url'];
					if((! x($author,'author_photo'))|| ($author['author_is_feed']))
						$author['author_photo'] = $contact['xchan_photo_m'];
				}

				if((! x($author,'author_name')) || (! x($author,'author_link'))) {
					logger('No author information! ' . print_r($author,true));
					continue;
				}

				$datarray['author_xchan'] = '';

				if($author['author_link'] != $contact['xchan_url']) {
					$name = '';
					if($author['full_name']) {
						$name = $author['full_name'];
						if($author['author_name'])
							$name .= ' (' . $author['author_name'] . ')';
					}
					else {
						$name = $author['author_name'];
					}
					$x = import_author_unknown(array('name' => $name,'url' => $author['author_link'],'photo' => array('src' => $author['author_photo'])));
					if($x)
						$datarray['author_xchan'] = $x;
				}
				if(! $datarray['author_xchan'])
					$datarray['author_xchan'] = $contact['xchan_hash'];

				$datarray['owner_xchan'] = $contact['xchan_hash'];

				if(array_key_exists('created',$datarray) && $datarray['created'] > NULL_DATE && $expire_days) {
					$t1 = $datarray['created'];
					$t2 = datetime_convert('UTC','UTC','now - ' . $expire_days . 'days');
					if($t1 < $t2) {
						logger('feed content older than expiration. Ignoring.', LOGGER_DEBUG, LOG_INFO);
						continue;
					}
				}

				$r = q("SELECT id, edited, author_xchan, item_deleted FROM item WHERE mid = '%s' AND uid = %d LIMIT 1",
					dbesc($datarray['mid']),
					intval($importer['channel_id'])
				);

				// Update content if 'updated' changes

				if($r) {
					if(activity_match($datarray['verb'],ACTIVITY_DELETE) 
						&& $datarray['author_xchan'] === $r[0]['author_xchan']) {
						if(! intval($r[0]['item_deleted'])) {
							logger('deleting item ' . $r[0]['id'] . ' mid=' . $datarray['mid'], LOGGER_DEBUG);
							drop_item($r[0]['id'],false);
						}
						continue;
					}

					if((x($datarray,'edited') !== false)
						&& (datetime_convert('UTC','UTC',$datarray['edited']) !== $r[0]['edited'])) {

						// do not accept (ignore) an earlier edit than one we currently have.
						if(datetime_convert('UTC','UTC',$datarray['edited']) < $r[0]['edited'])
							continue;

						$datarray['uid'] = $importer['channel_id'];
						$datarray['aid'] = $importer['channel_account_id'];
						$datarray['id'] = $r[0]['id'];

						update_feed_item($importer['channel_id'],$datarray);
					}

					continue;
				}

				$datarray['parent_mid'] = $datarray['mid'];
				$datarray['uid'] = $importer['channel_id'];
				$datarray['aid'] = $importer['channel_account_id'];

				if(! link_compare($author['owner_link'], $contact['xchan_url'])) {
					logger('Correcting item owner.', LOGGER_DEBUG);
					$author['owner_name']   = $contact['name'];
					$author['owner_link']   = $contact['url'];
					$author['owner_avatar'] = $contact['thumb'];
				}

				if(! post_is_importable($datarray, $contact))
					continue;

				logger('author: ' . print_r($author, true), LOGGER_DEBUG);
				logger('data: ' . print_r($datarray, true), LOGGER_DATA);

				$xx = item_store($datarray);
				$r = $xx['item_id'];

				continue;
			}
		}
	}
}


function feed_conversation_fetch($importer,$contact,$parent_link) {

	logger('parent_link: ' . $parent_link, LOGGER_DEBUG, LOG_INFO);

	$link = '';

	// GNU-Social flavoured feeds
	if(strpos($parent_link,'/notice/')) {
		$link = str_replace('/notice/','/api/statuses/show/',$parent_link) . '.atom';
	} 

	// Mastodon flavoured feeds
	if(strpos($parent_link,'/users/') && strpos($parent_link,'/updates/')) {
		$link = $parent_link . '.atom';
	} 

	if(! $link)
		return false;

	logger('fetching: ' . $link, LOGGER_DEBUG, LOG_INFO);

	$fetch = z_fetch_url($link);

	if(! $fetch['success'])
		return false;

	$data = $fetch['body'];

	// We will probably receive an atom 'entry' and not an atom 'feed'. Unfortunately
	// our parser is a bit strict about compliance so we'll insert just enough of a feed 
	// tag to trick it into believing it's a compliant feed. 

	if(! strstr($data,'<feed')) {
		$data = str_replace('<entry ','<feed xmlns="http://www.w3.org/2005/Atom"><entry ',$data); 
		$data .= '</feed>';
	} 
 
	consume_feed($data,$importer,$contact,1);
	consume_feed($data,$importer,$contact,2);

	return true;
	
}

/**
 * @brief Normalise an id.
 *
 * Strip "X-ZOT:" from $id.
 *
 * @param string $id
 * @return string
 */
function normalise_id($id) {
	return str_replace('X-ZOT:', '', $id);
}


/**
 * @brief Process atom feed and return the first post and structure.
 *
 * @param string $xml
 *   The (atom) feed to consume - RSS isn't as fully supported but may work for simple feeds.
 * @param $importer
 *   The contact_record (joined to user_record) of the local user who owns this
 *   relationship. It is this person's stuff that is going to be updated.
 */
function process_salmon_feed($xml, $importer) {

	$ret = array();

	if(! strlen($xml)) {
		logger('process_feed: empty input');
		return;
	}

	$feed = new SimplePie();
	$feed->set_raw_data($xml);

	// We can preserve iframes because we will strip them in the purifier after
	// checking for supported video sources.
	$strip_htmltags = $feed->strip_htmltags;
	array_splice($strip_htmltags, array_search('iframe', $strip_htmltags), 1);
	$feed->strip_htmltags($strip_htmltags);

	$feed->init();

	if($feed->error())
		logger('Error parsing XML: ' . $feed->error());

	$permalink = $feed->get_permalink();

	if($feed->get_item_quantity()) {

		// this should be exactly one

		logger('feed item count = ' . $feed->get_item_quantity(), LOGGER_DEBUG);

		$items = $feed->get_items();

		foreach($items as $item) {

			$item_id = normalise_id($item->get_id());

			logger('processing ' . $item_id, LOGGER_DEBUG);

			$rawthread = $item->get_item_tags( NAMESPACE_THREAD, 'in-reply-to');
			if(isset($rawthread[0]['attribs']['']['ref'])) {
				$is_reply = true;
				$parent_mid = normalise_id($rawthread[0]['attribs']['']['ref']);
			}

			if($is_reply)
				$ret['parent_mid'] = $parent_mid;

			$ret['author'] = array();

			$datarray = get_atom_elements($feed, $item, $ret['author']);

			// reset policies which are restricted by default for RSS connections
			// This item is likely coming from GNU-social via salmon and allows public interaction
			$datarray['public_policy'] = '';
			$datarray['comment_policy'] = 'authenticated';

			$ret['item'] = $datarray;
		}
	}

	return $ret;
}


/**
 * @brief Given an xml (atom) feed, find author and hub links.
 *
 * @param string $xml
 * @return array
 */
function feed_meta($xml) {

	$ret = array();

	if(! strlen($xml)) {
		logger('empty input');
		return $ret;
	}

	$feed = new SimplePie();
	$feed->set_raw_data($xml);
	$feed->init();

	if($feed->error()) {
		logger('Error parsing XML: ' . $feed->error());
		return $ret;
	}

	$ret['hubs'] = $feed->get_links('hub');

	//logger('hubs: ' . print_r($hubs,true), LOGGER_DATA);

	$author = array();

	$found_author = $feed->get_author();
	if($found_author) {
		$author['author_name'] = unxmlify($found_author->get_name());
		$author['author_link'] = unxmlify($found_author->get_link());

		$rawauthor = $feed->get_feed_tags(SIMPLEPIE_NAMESPACE_ATOM_10, 'author');
		logger('rawauthor: ' . print_r($rawauthor, true));

		if($rawauthor) {
			if($rawauthor[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['link']) {
				$base = $rawauthor[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['link'];
				foreach($base as $link) {
					if(!x($author, 'author_photo') || ! $author['author_photo']) {
						if($link['attribs']['']['rel'] === 'photo' || $link['attribs']['']['rel'] === 'avatar') {
							$author['author_photo'] = unxmlify($link['attribs']['']['href']);
							break;
						}
					}
				}
			}
			if($rawauthor[0]['child'][NAMESPACE_POCO]['displayName'][0]['data'])
				$author['full_name'] = unxmlify($rawauthor[0]['child'][NAMESPACE_POCO]['displayName'][0]['data']);
			if($rawauthor[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['uri'][0]['data'])
				$author['author_uri'] = unxmlify($rawauthor[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['uri'][0]['data']);
		}
	}

	if(! $author['author_photo'])
		$author['author_photo'] = $feed->get_image_url();


	if(substr($author['author_link'],-1,1) == '/')
		$author['author_link'] = substr($author['author_link'],0,-1);

	$ret['author'] = $author;

	return $ret;
}

/**
 * @brief Not yet implemented function to update feed item.
 *
 * @param int $uid
 * @param array $datarray
 */
function update_feed_item($uid, $datarray) {
	item_store_update($datarray);
}

/**
 * @brief Fetch the content of a feed and further consume it.
 *
 * It will first process parent items and in a second run child items.
 * @see consume_feed()
 *
 * @param int $uid
 * @param int $abook_id
 * @param string $url URL of the feed
 */
function handle_feed($uid, $abook_id, $url) {

	$channel = channelx_by_n($uid);
	if(! $channel)
		return;

	$x = q("select * from abook left join xchan on abook_xchan = xchan_hash where abook_id = %d and abook_channel = %d limit 1",
		dbesc($abook_id),
		intval($uid)
	);

	$recurse = 0;
	$z = z_fetch_url($url, false, $recurse, array('novalidate' => true));

	//logger('data:' . print_r($z, true), LOGGER_DATA);

	if($z['success']) {
		consume_feed($z['body'], $channel, $x[0], 1);
		consume_feed($z['body'], $channel, $x[0], 2);
	}
}

/**
 * @brief Return a XML tag with author information.
 *
 * @hooks \b atom_author Possibility to add further tags to returned XML string
 *   * \e string The created XML tag as a string without closing tag
 * @param string $tag The XML tag to create
 * @param string $nick preferred username
 * @param string $name displayed name of the author
 * @param string $uri
 * @param int $h image height
 * @param int $w image width
 * @param string $type profile photo mime type
 * @param string $photo Fully qualified URL to a profile/avator photo
 * @return string
 */
function atom_author($tag, $nick, $name, $uri, $h, $w, $type, $photo) {
	$o = '';
	if(! $tag)
		return $o;

	$nick = xmlify($nick);
	$name = xmlify($name);
	$uri = xmlify($uri);
	$h = intval($h);
	$w = intval($w);
	$photo = xmlify($photo);

	$o .= "<$tag>\r\n";
	$o .= "  <id>$uri</id>\r\n";
	$o .= "  <name>$nick</name>\r\n";
	$o .= "  <uri>$uri</uri>\r\n";
	$o .= '  <link rel="photo"  type="' . $type . '" media:width="' . $w . '" media:height="' . $h . '" href="' . $photo . '" />' . "\r\n";
	$o .= '  <link rel="avatar" type="' . $type . '" media:width="' . $w . '" media:height="' . $h . '" href="' . $photo . '" />' . "\r\n";
	$o .= '  <poco:preferredUsername>' . $nick . '</poco:preferredUsername>' . "\r\n";
	$o .= '  <poco:displayName>' . $name . '</poco:displayName>' . "\r\n";

	call_hooks('atom_author', $o);

	$o .= "</$tag>\r\n";

	return $o;
}


function atom_render_author($tag,$xchan) {

	
	$nick = xmlify(substr($xchan['xchan_addr'],0,strpos($xchan['xchan_addr'],'@')));
	$id   = xmlify($xchan['xchan_url']);
	$name = xmlify($xchan['xchan_name']);
	$photo = xmlify($xchan['xchan_photo_l']);
	$type = xmlify($xchan['xchan_photo_mimetype']);
	$w = $h = 300;

	$o .= "<$tag>\r\n";
	$o .= "  <as:object-type>http://activitystrea.ms/schema/1.0/person</as:object-type>\r\n";
	$o .= "  <id>$id</id>\r\n";
	$o .= "  <name>$nick</name>\r\n";
	$o .= "  <uri>$id</uri>\r\n";
	$o .= '  <link rel="alternate" type="text/html" href="' . $id . '" />' . "\r\n";
	$o .= '  <link rel="photo"  type="' . $type . '" media:width="' . $w . '" media:height="' . $h . '" href="' . $photo . '" />' . "\r\n";
	$o .= '  <link rel="avatar" type="' . $type . '" media:width="' . $w . '" media:height="' . $h . '" href="' . $photo . '" />' . "\r\n";
	$o .= '  <poco:preferredUsername>' . $nick . '</poco:preferredUsername>' . "\r\n";
	$o .= '  <poco:displayName>' . $name . '</poco:displayName>' . "\r\n";

	call_hooks('atom_render_author', $o);

	$o .= "</$tag>\r\n";

	return $o;


}

function compat_photos_list($s) {

	$ret = [];

	$found = preg_match_all('/\[[zi]mg(.*?)\](.*?)\[/ism',$s,$matches,PREG_SET_ORDER);

	if($found) {
		foreach($matches as $match) {			
			$ret[] = [
				'href' => $match[2],
				'length' => 0,
				'type' => guess_image_type($match[2])
			];

		}
	}

	return $ret;
}



/**
 * @brief Create an item for the Atom feed.
 *
 * @see get_feed_for()
 *
 * @param array $item
 * @param string $type
 * @param array $author
 * @param array $owner
 * @param string $comment default false
 * @param number $cid default 0
 * @return void|string
 */
function atom_entry($item, $type, $author, $owner, $comment = false, $cid = 0, $compat = false) {


	if(! $item['parent'])
		return;

	if($item['deleted'])
		return '<at:deleted-entry ref="' . xmlify($item['mid']) . '" when="' . xmlify(datetime_convert('UTC','UTC',$item['edited'] . '+00:00',ATOM_TIME)) . '" />' . "\r\n";

	create_export_photo_body($item);

	if($item['allow_cid'] || $item['allow_gid'] || $item['deny_cid'] || $item['deny_gid'])
		$body = fix_private_photos($item['body'],$owner['uid'],$item,$cid);
	else
		$body = $item['body'];

	if($compat) {
		$compat_photos = compat_photos_list($body);
	}
	else {
		$compat_photos = null;
	}

	$o = "\r\n\r\n<entry>\r\n";

	if(is_array($author)) {
		$o .= atom_render_author('author',$author);
	}
	else {
		$o .= atom_render_author('author',$item['author']);
	}

	$o .= atom_render_author('zot:owner',$item['owner']);

	if(($item['parent'] != $item['id']) || ($item['parent_mid'] !== $item['mid']) || (($item['thr_parent'] !== '') && ($item['thr_parent'] !== $item['mid']))) {
		$parent_item = (($item['thr_parent']) ? $item['thr_parent'] : $item['parent_mid']);
		// ensure it's a legal uri and not just a message-id
		if(! strpos($parent_item,':'))
			$parent_item = 'X-ZOT:' . $parent_item;

		$o .= '<thr:in-reply-to ref="' . xmlify($parent_item) . '" type="text/html" href="' .  xmlify($item['plink']) . '" />' . "\r\n";
	}

	if(activity_match($item['obj_type'],ACTIVITY_OBJ_EVENT) && activity_match($item['verb'],ACTIVITY_POST)) {
		$obj = ((is_array($item['obj'])) ? $item['obj'] : json_decode($item['obj'],true));

		$o .= '<title>' . xmlify($item['title']) . '</title>' . "\r\n";
		$o .= '<summary xmlns="urn:ietf:params:xml:ns:xcal">' . xmlify(bbcode($obj['title'])) . '</summary>' . "\r\n";
		$o .= '<dtstart xmlns="urn:ietf:params:xml:ns:xcal">' . datetime_convert('UTC','UTC', $obj['dtstart'],'Ymd\\THis' . (($obj['adjust']) ? '\\Z' : '')) .  '</dtstart>' . "\r\n";
		$o .= '<dtend xmlns="urn:ietf:params:xml:ns:xcal">' . datetime_convert('UTC','UTC', $obj['dtend'],'Ymd\\THis' . (($obj['adjust']) ? '\\Z' : '')) .  '</dtend>' . "\r\n";
		$o .= '<location xmlns="urn:ietf:params:xml:ns:xcal">' . xmlify(bbcode($obj['location'])) . '</location>' . "\r\n";
		$o .= '<content type="' . $type . '" >' . xmlify(bbcode($obj['description'])) . '</content>' . "\r\n";
	}
	else {
		$o .= '<title>' . xmlify($item['title']) . '</title>' . "\r\n";
		$o .= '<content type="' . $type . '" >' . xmlify(prepare_text($body,$item['mimetype'])) . '</content>' . "\r\n";
	}

	$o .= '<id>' . 'X-ZOT:' . xmlify($item['mid']) . '</id>' . "\r\n";
	$o .= '<published>' . xmlify(datetime_convert('UTC','UTC',$item['created'] . '+00:00',ATOM_TIME)) . '</published>' . "\r\n";
	$o .= '<updated>' . xmlify(datetime_convert('UTC','UTC',$item['edited'] . '+00:00',ATOM_TIME)) . '</updated>' . "\r\n";

	$o .= '<link rel="alternate" type="text/html" href="' . xmlify($item['plink']) . '" />' . "\r\n";

	if($item['location']) {
		$o .= '<zot:location>' . xmlify($item['location']) . '</zot:location>' . "\r\n";
		$o .= '<poco:address><poco:formatted>' . xmlify($item['location']) . '</poco:formatted></poco:address>' . "\r\n";
	}

	if($item['coord'])
		$o .= '<georss:point>' . xmlify($item['coord']) . '</georss:point>' . "\r\n";

	if(($item['item_private']) || strlen($item['allow_cid']) || strlen($item['allow_gid']) || strlen($item['deny_cid']) || strlen($item['deny_gid']))
		$o .= '<zot:private>' . (($item['item_private']) ? $item['item_private'] : 1) . '</zot:private>' . "\r\n";

	if($item['app'])
		$o .= '<statusnet:notice_info local_id="' . $item['id'] . '" source="' . xmlify($item['app']) . '" ></statusnet:notice_info>' . "\r\n";

	$verb = construct_verb($item);
	$o .= '<as:verb>' . xmlify($verb) . '</as:verb>' . "\r\n";
	$actobj = construct_activity_object($item);
	if(strlen($actobj))
		$o .= $actobj;

	$actarg = construct_activity_target($item);
	if(strlen($actarg))
		$o .= $actarg;

	if($item['attach']) {
		$enclosures = json_decode($item['attach'], true);
		if($enclosures) {
			foreach($enclosures as $enc) {
				$o .= '<link rel="enclosure" '
				. (($enc['href']) ? 'href="' . $enc['href'] . '" ' : '')
				. (($enc['length']) ? 'length="' . $enc['length'] . '" ' : '')
				. (($enc['type']) ? 'type="' . $enc['type'] . '" ' : '')
				. ' />' . "\r\n";
			}
		}
	}
	if($compat_photos) {
		foreach($compat_photos as $enc) {
			$o .= '<link rel="enclosure" '
			. (($enc['href']) ? 'href="' . $enc['href'] . '" ' : '')
			. ((array_key_exists('length',$enc)) ? 'length="' . $enc['length'] . '" ' : '')
			. (($enc['type']) ? 'type="' . $enc['type'] . '" ' : '')
			. ' />' . "\r\n";
		}
	}

	if($item['term']) {
		foreach($item['term'] as $term) {
			$scheme = '';
			$label = '';
			switch($term['ttype']) {
				case TERM_UNKNOWN:
					$scheme = NAMESPACE_ZOT . '/term/unknown';
					$label = $term['term'];
					break;
				case TERM_HASHTAG:
				case TERM_COMMUNITYTAG:
					$scheme = NAMESPACE_ZOT . '/term/hashtag';
					$label = '#' . $term['term'];
					break;
				case TERM_MENTION:
					$scheme = NAMESPACE_ZOT . '/term/mention';
					$label = '@' . $term['term'];
					break;
				case TERM_CATEGORY:
					$scheme = NAMESPACE_ZOT . '/term/category';
					$label = $term['term'];
					break;
				default:
					break;
			}
			if(! $scheme)
				continue;

			$o .= '<category scheme="' . $scheme . '" term="' . $term['term'] . '" label="' . $label . '" />' . "\r\n";
		}
	}

	$o .= '</entry>' . "\r\n";

	// build array to pass to hook
	$x = [
		'item'     => $item,
		'type'     => $type,
		'author'   => $author,
		'owner'    => $owner,
		'comment'  => $comment,
		'abook_id' => $cid,
		'entry'    => $o
	];

	call_hooks('atom_entry', $x);

	return $x['entry'];
}

