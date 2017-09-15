<?php

namespace Zotlabs\Lib;

use \Zotlabs\Lib as Zlib;

class NativeWikiPage {

	static public function page_list($channel_id,$observer_hash, $resource_id) {

		// TODO: Create item table records for pages so that metadata like title can be applied
		$w = Zlib\NativeWiki::get_wiki($channel_id,$observer_hash,$resource_id);

		$pages[] = [
			'resource_id' => '',
			'title'       => 'Home',
			'url'         => 'Home',
			'link_id'     => 'id_wiki_home_0'
		];

		$sql_extra = item_permissions_sql($channel_id,$observer_hash);

		$r = q("select * from item where resource_type = 'nwikipage' and resource_id = '%s' and uid = %d and item_deleted = 0 
			$sql_extra order by created asc",
			dbesc($resource_id),
			intval($channel_id)
		);
		if($r) {
			$x = [];
			$y = [];

			foreach($r as $rv) {
				if(! in_array($rv['mid'],$x)) {
					$y[] = $rv;
					$x[] = $rv['mid'];
				}
			}

			$items = fetch_post_tags($y,true);

			foreach($items as $page_item) {
				$title = get_iconfig($page_item['id'],'nwikipage','pagetitle',t('(No Title)'));
				if(urldecode($title) !== 'Home') {
					$pages[] = [
						'resource_id' => $resource_id,
						'title'       => escape_tags($title),
						'url'         => str_replace('%2F','/',urlencode(str_replace('%2F','/',urlencode($title)))),
						'link_id'     => 'id_' . substr($resource_id, 0, 10) . '_' . $page_item['id']
					];
				}
			}
		}

		return array('pages' => $pages, 'wiki' => $w);
	}


	static public function create_page($channel_id, $observer_hash, $name, $resource_id, $mimetype = 'text/bbcode') {

		logger('mimetype: ' . $mimetype);

		if(! in_array($mimetype,[ 'text/markdown','text/bbcode','text/plain','text/html' ]))
			$mimetype = 'text/markdown';

		$w = Zlib\NativeWiki::get_wiki($channel_id, $observer_hash, $resource_id);

		if (! $w['wiki']) {
			return array('content' => null, 'message' => 'Error reading wiki', 'success' => false);
		}

		// create an empty activity

		$arr = [];
		$arr['uid']           = $channel_id;
		$arr['author_xchan']  = $observer_hash;
		$arr['mimetype']      = $mimetype;
		$arr['resource_type'] = 'nwikipage';
		$arr['resource_id']   = $resource_id;
		$arr['allow_cid']     = $w['wiki']['allow_cid'];
		$arr['allow_gid']     = $w['wiki']['allow_gid'];
		$arr['deny_cid']      = $w['wiki']['deny_cid'];
		$arr['deny_gid']      = $w['wiki']['deny_gid'];

		$arr['public_policy'] = map_scope(\Zotlabs\Access\PermissionLimits::Get($channel_id,'view_wiki'),true);

		// We may wish to change this some day.
		$arr['item_unpublished'] = 1;

		set_iconfig($arr,'nwikipage','pagetitle',(($name) ? $name : t('(No Title)')),true);

		$p = post_activity_item($arr, false, false);

		if($p['item_id']) {
			$page = [ 
				'rawName'  => $name,
				'htmlName' => escape_tags($name),
				'urlName'  => urlencode($name), 

			];

			return array('page' => $page, 'item_id' => $p['item_id'], 'item' => $p['activity'], 'wiki' => $w, 'message' => '', 'success' => true);
		}
		return [ 'success' => false, 'message' => t('Wiki page create failed.') ];
	}

	static public function rename_page($arr) {

		$pageUrlName   = ((array_key_exists('pageUrlName',$arr))   ? $arr['pageUrlName']   : '');
		$pageNewName   = ((array_key_exists('pageNewName',$arr))   ? $arr['pageNewName']   : '');
		$resource_id   = ((array_key_exists('resource_id',$arr))   ? $arr['resource_id']   : '');
		$observer_hash = ((array_key_exists('observer_hash',$arr)) ? $arr['observer_hash'] : '');
		$channel_id    = ((array_key_exists('channel_id',$arr))    ? $arr['channel_id']    : 0);

		$w = Zlib\NativeWiki::get_wiki($channel_id, $observer_hash, $resource_id);
		if(! $w['wiki']) {
			return array('message' => t('Wiki not found.'), 'success' => false);
		}


		$ic = q("select * from iconfig left join item on iconfig.iid = item.id 
			where uid = %d and cat = 'nwikipage' and k = 'pagetitle' and v = '%s'",
			intval($channel_id),
			dbesc($pageNewName)
		);

		if($ic) {
			return [ 'success' => false, 'message' => t('Destination name already exists') ];
		}


		$ids = [];

		$ic = q("select *, item.id as item_id from iconfig left join item on iconfig.iid = item.id 
			where uid = %d and cat = 'nwikipage' and k = 'pagetitle' and v = '%s'",
			intval($channel_id),
			dbesc($pageUrlName)
		);

		if($ic) {
			foreach($ic as $c) {
				set_iconfig($c['item_id'],'nwikipage','pagetitle',$pageNewName);
			}

			$page = [ 
				'rawName'  => $pageNewName, 
				'htmlName' => escape_tags($pageNewName), 
				'urlName'  => urlencode(escape_tags($pageNewName))
			];

			return [ 'success' => true, 'page' => $page ];
		}

		return [ 'success' => false, 'item_id' => $c['item_id'], 'message' => t('Page not found') ];
	
	}

	static public function get_page_content($arr) {
		$pageUrlName   = ((array_key_exists('pageUrlName',$arr))   ? $arr['pageUrlName']        : '');
		$resource_id   = ((array_key_exists('resource_id',$arr))   ? $arr['resource_id']        : '');
		$observer_hash = ((array_key_exists('observer_hash',$arr)) ? $arr['observer_hash']      : '');
		$channel_id    = ((array_key_exists('channel_id',$arr))    ? intval($arr['channel_id']) : 0);
		$revision      = ((array_key_exists('revision',$arr))      ? intval($arr['revision'])   : (-1));


		$w = Zlib\NativeWiki::get_wiki($channel_id, $observer_hash, $resource_id);
		if (! $w['wiki']) {
			return array('content' => null, 'message' => 'Error reading wiki', 'success' => false);
		}

		$item = self::load_page($arr);

		if($item) {
			$content = $item['body'];

			return [ 
				'content'      => $content,
				'mimeType'     => $w['mimeType'],
				'pageMimeType' => $item['mimetype'], 
				'message'      => '', 
				'success'      => true
			];
		}
	
		return array('content' => null, 'message' => t('Error reading page content'), 'success' => false);

	}

	static public function page_history($arr) {
		$pageUrlName = ((array_key_exists('pageUrlName',$arr)) ? $arr['pageUrlName'] : '');
		$resource_id = ((array_key_exists('resource_id',$arr)) ? $arr['resource_id'] : '');
		$observer_hash = ((array_key_exists('observer_hash',$arr)) ? $arr['observer_hash'] : '');
		$channel_id    = ((array_key_exists('channel_id',$arr))    ? $arr['channel_id']    : 0);

		$w = Zlib\NativeWiki::get_wiki($channel_id, $observer_hash, $resource_id);
		if (!$w['wiki']) {
			return array('history' => null, 'message' => 'Error reading wiki', 'success' => false);
		}

		$items = self::load_page_history($arr);

		$history = [];

		if($items) {
			$processed = 0;
			foreach($items as $item) {
				if($processed > 1000)
					break;
				$processed ++;
				$history[] = [ 
					'revision' => $item['revision'],
					'date' => datetime_convert('UTC',date_default_timezone_get(),$item['edited']),
					'name' => $item['author']['xchan_name'],
					'title' => get_iconfig($item,'nwikipage','commit_msg') 
				];

			}

			return [ 'success' => true, 'history' => $history ];
		}

		return [ 'success' => false ];

	}
	

	static public function load_page($arr) {

		$pageUrlName   = ((array_key_exists('pageUrlName',$arr))   ? $arr['pageUrlName']     : '');
		$resource_id   = ((array_key_exists('resource_id',$arr))   ? $arr['resource_id']     : '');
		$observer_hash = ((array_key_exists('observer_hash',$arr)) ? $arr['observer_hash']   : '');
		$channel_id    = ((array_key_exists('channel_id',$arr))    ? $arr['channel_id']      : 0);
		$revision      = ((array_key_exists('revision',$arr))      ? $arr['revision']        : (-1));

		$w = Zlib\NativeWiki::get_wiki($channel_id, $observer_hash, $resource_id);

		if (! $w['wiki']) {
			return array('content' => null, 'message' => 'Error reading wiki', 'success' => false);
		}

		$ids = '';

		$ic = q("select * from iconfig left join item on iconfig.iid = item.id where uid = %d and cat = 'nwikipage' and k = 'pagetitle' and v = '%s'",
			intval($channel_id),
			dbesc($pageUrlName)
		);

		if($ic) {
			foreach($ic as $c) {
				if($ids)
					$ids .= ',';
				$ids .= intval($c['iid']);
			}
		}

		$sql_extra = item_permissions_sql($channel_id,$observer_hash);

		if($revision == (-1))
			$sql_extra .= " order by revision desc ";
		elseif($revision)
			$sql_extra .= " and revision = " . intval($revision) . " ";

		$r = null;


		if($ids) {
			$r = q("select * from item where resource_type = 'nwikipage' and resource_id = '%s' and uid = %d and id in ( $ids ) $sql_extra limit 1",
				dbesc($resource_id),
				intval($channel_id)
			);

			if($r) {
				$items = fetch_post_tags($r,true);
				return $items[0];
			}
		}

		return null;
	}

	static public function load_page_history($arr) {

		$pageUrlName   = ((array_key_exists('pageUrlName',$arr))   ? $arr['pageUrlName']     : '');
		$resource_id   = ((array_key_exists('resource_id',$arr))   ? $arr['resource_id']     : '');
		$observer_hash = ((array_key_exists('observer_hash',$arr)) ? $arr['observer_hash']   : '');
		$channel_id    = ((array_key_exists('channel_id',$arr))    ? $arr['channel_id']      : 0);
		$revision      = ((array_key_exists('revision',$arr))      ? $arr['revision']        : (-1));

		$w = Zlib\NativeWiki::get_wiki($channel_id, $observer_hash, $resource_id);
		if (! $w['wiki']) {
			return array('content' => null, 'message' => 'Error reading wiki', 'success' => false);
		}

		$ids = '';

		$ic = q("select * from iconfig left join item on iconfig.iid = item.id where uid = %d and cat = 'nwikipage' and k = 'pagetitle' and v = '%s'",
			intval($channel_id),
			dbesc($pageUrlName)
		);
	
		if($ic) {
			foreach($ic as $c) {
				if($ids)
					$ids .= ',';
				$ids .= intval($c['iid']);
			}
		}

		$sql_extra = item_permissions_sql($channel_id,$observer_hash);

		$sql_extra .= " order by revision desc ";

		$r = null;
		if($ids) {
			$r = q("select * from item where resource_type = 'nwikipage' and resource_id = '%s' and uid = %d and id in ( $ids ) and item_deleted = 0 $sql_extra",
				dbesc($resource_id),
				intval($channel_id)
			);
			if($r) {
				xchan_query($r);
				$items = fetch_post_tags($r,true);
				return $items;
			}
		}

		return null;
	}

	static public function save_page($arr) {

		$pageUrlName   = ((array_key_exists('pageUrlName',$arr))   ? $arr['pageUrlName']   : '');
		$content       = ((array_key_exists('content',$arr))       ? $arr['content']       : '');
		$resource_id   = ((array_key_exists('resource_id',$arr))   ? $arr['resource_id']   : '');
		$observer_hash = ((array_key_exists('observer_hash',$arr)) ? $arr['observer_hash'] : '');
		$channel_id    = ((array_key_exists('channel_id',$arr))    ? $arr['channel_id']    : 0);
		$revision      = ((array_key_exists('revision',$arr))      ? $arr['revision']      : 0);

		$w = Zlib\NativeWiki::get_wiki($channel_id, $observer_hash, $resource_id);

		if (!$w['wiki']) {
			return array('message' => t('Error reading wiki'), 'success' => false);
		}

	
		// fetch the most recently saved revision. 

		$item = self::load_page($arr);
		if(! $item) {
			return array('message' => t('Page not found'), 'success' => false);
		}

		$mimetype = $item['mimetype'];

		// change just the fields we need to change to create a revision; 

		unset($item['id']);
		unset($item['author']);

		$item['parent']       = 0;
		$item['body']         = $content;
		$item['author_xchan'] = $observer_hash;
		$item['revision']     = (($arr['revision']) ? intval($arr['revision']) + 1 : intval($item['revision']) + 1);
		$item['edited']       = datetime_convert();
		$item['mimetype']     = $mimetype;

		if($item['iconfig'] && is_array($item['iconfig']) && count($item['iconfig'])) {
			for($x = 0; $x < count($item['iconfig']); $x ++) {
				unset($item['iconfig'][$x]['id']);
				unset($item['iconfig'][$x]['iid']);
			}
		}

		$ret = item_store($item, false, false);

		if($ret['item_id'])
			return array('message' => '', 'item_id' => $ret['item_id'], 'filename' => $filename, 'success' => true);
		else
			return array('message' => t('Page update failed.'), 'success' => false);
	}	

	static public function delete_page($arr) {
		$pageUrlName = ((array_key_exists('pageUrlName',$arr)) ? $arr['pageUrlName'] : '');
		$resource_id = ((array_key_exists('resource_id',$arr)) ? $arr['resource_id'] : '');
		$observer_hash = ((array_key_exists('observer_hash',$arr)) ? $arr['observer_hash'] : '');
		$channel_id    = ((array_key_exists('channel_id',$arr))    ? $arr['channel_id']    : 0);

		$w = Zlib\NativeWiki::get_wiki($channel_id, $observer_hash, $resource_id);

		if(! $w['wiki']) {
			return [ 'success' => false, 'message' => t('Error reading wiki') ];
		}

		$ids = [];

		$ic = q("select * from iconfig left join item on iconfig.iid = item.id 
			where uid = %d and cat = 'nwikipage' and k = 'pagetitle' and v = '%s'",
			intval($channel_id),
			dbesc($pageUrlName)
		);

		if($ic) {
			foreach($ic as $c) {
				$ids[] = intval($c['iid']);
			}
		}

		if($ids) {
			drop_items($ids);
			return [ 'success' => true ];
		}

		return [ 'success' => false, 'message' => t('Nothing deleted') ];	
	}
	
	static public function revert_page($arr) {
		$pageUrlName   = ((array_key_exists('pageUrlName',$arr))   ? $arr['pageUrlName']   : '');
		$resource_id   = ((array_key_exists('resource_id',$arr))   ? $arr['resource_id']   : '');
		$commitHash    = ((array_key_exists('commitHash',$arr))    ? $arr['commitHash']    : null);
		$observer_hash = ((array_key_exists('observer_hash',$arr)) ? $arr['observer_hash'] : '');
		$channel_id    = ((array_key_exists('channel_id',$arr))    ? $arr['channel_id']    : 0);

		if (! $commitHash) {
			return array('content' => $content, 'message' => 'No commit was provided', 'success' => false);
		}

		$w = Zlib\NativeWiki::get_wiki($channel_id, $observer_hash, $resource_id);
		if (!$w['wiki']) {
			return array('content' => $content, 'message' => 'Error reading wiki', 'success' => false);
		}

		$x = $arr;

		if(intval($commitHash) > 0) {
			unset($x['commitHash']);
			$x['revision'] = intval($commitHash) - 1;
			$loaded = self::load_page($x);

			if($loaded) {
				$content = $loaded['body'];
				return [ 'content' => $content, 'success' => true ];
			}
			return [ 'content' => $content, 'success' => false ]; 
		}
	}
	
	static public function compare_page($arr) {
		$pageUrlName = ((array_key_exists('pageUrlName',$arr)) ? $arr['pageUrlName'] : '');
		$resource_id = ((array_key_exists('resource_id',$arr)) ? $arr['resource_id'] : '');
		$currentCommit = ((array_key_exists('currentCommit',$arr)) ? $arr['currentCommit'] : (-1));
		$compareCommit = ((array_key_exists('compareCommit',$arr)) ? $arr['compareCommit'] : 0);
		$observer_hash = ((array_key_exists('observer_hash',$arr)) ? $arr['observer_hash'] : '');
		$channel_id    = ((array_key_exists('channel_id',$arr))    ? $arr['channel_id']    : 0);

		$w = Zlib\NativeWiki::get_wiki($channel_id, $observer_hash, $resource_id);

		if (!$w['wiki']) {
			return array('message' => t('Error reading wiki'), 'success' => false);
		}

		$x = $arr;
		$x['revision'] = (-1);

		$currpage = self::load_page($x);
		if($currpage)
			$currentContent = $currpage['body'];

		$x['revision'] = $compareCommit;
		$comppage = self::load_page($x);
		if($comppage)
			$compareContent = $comppage['body'];

		if($currpage && $comppage) {
			require_once('library/class.Diff.php');
			$diff = \Diff::toTable(\Diff::compare($currentContent, $compareContent));

			return [ 'success' => true, 'diff' => $diff ];
		}
		return [ 'success' => false, 'message' =>  t('Compare: object not found.') ];

	}
	
	static public function commit($arr) {

		$commit_msg    = ((array_key_exists('commit_msg', $arr))   ? $arr['commit_msg']    : t('Page updated'));
		$observer_hash = ((array_key_exists('observer_hash',$arr)) ? $arr['observer_hash'] : '');
		$channel_id    = ((array_key_exists('channel_id',$arr))    ? $arr['channel_id']    : 0);
		$pageUrlName   = ((array_key_exists('pageUrlName',$arr))   ? $arr['pageUrlName']   : t('Untitled'));

		if(array_key_exists('resource_id', $arr)) {
			$resource_id = $arr['resource_id'];
		}
		else {
			return array('message' => t('Wiki resource_id required for git commit'), 'success' => false);
		}

		$w = Zlib\NativeWiki::get_wiki($channel_id, $observer_hash, $resource_id);
		if (! $w['wiki']) {
			return array('message' => t('Error reading wiki'), 'success' => false);
		}


		$page = self::load_page($arr);

		if($page) {
			set_iconfig($page['id'],'nwikipage','commit_msg',escape_tags($commit_msg),true);
			return [ 'success' => true, 'item_id' => $page['id'], 'page' => $page ];
		}

		return [ 'success' => false, 'message' => t('Page not found.') ];

	}
	
	static public function convert_links($s, $wikiURL) {
		
		if (strpos($s,'[[') !== false) {
			preg_match_all("/\[\[(.*?)\]\]/", $s, $match);
			$pages = $pageURLs = array();
			foreach ($match[1] as $m) {
				// TODO: Why do we need to double urlencode for this to work?
				$pageURLs[] = urlencode(urlencode(escape_tags($m)));
				$pages[] = $m;
			}
			$idx = 0;
			while(strpos($s,'[[') !== false) {
			$replace = '<a href="'.$wikiURL.'/'.$pageURLs[$idx].'">'.$pages[$idx].'</a>';
				$s = preg_replace("/\[\[(.*?)\]\]/", $replace, $s, 1);
				$idx++;
			}
		}
		return $s;
	}

	static public function render_page_history($arr) {

		$pageUrlName = ((array_key_exists('pageUrlName', $arr)) ? $arr['pageUrlName'] : '');
		$resource_id = ((array_key_exists('resource_id', $arr)) ? $arr['resource_id'] : '');

		$pageHistory = self::page_history([
			'channel_id'    => \App::$profile_uid, 
			'observer_hash' => get_observer_hash(),
			'resource_id'   => $resource_id,
			'pageUrlName'   => $pageUrlName
		]);

		return replace_macros(get_markup_template('nwiki_page_history.tpl'), array(
			'$pageHistory' => $pageHistory['history'],
			'$permsWrite'  => $arr['permsWrite'],
			'$name_lbl'    => t('Name'),
			'$msg_label'   => t('Message','wiki_history')
		));

	}


	
	/**
	 * Replace the instances of the string [toc] with a list element that will be populated by
	 * a table of contents by the JavaScript library
	 * @param string $s
	 * @return string
	 */
	static public function generate_toc($s) {
		if (strpos($s,'[toc]') !== false) {
			//$toc_md = wiki_toc($s);	// Generate Markdown-formatted list prior to HTML render
			$toc_md = '<ul id="wiki-toc"></ul>'; // use the available jQuery plugin http://ndabas.github.io/toc/
			$s = preg_replace("/\[toc\]/", $toc_md, $s, -1);
		}
		return $s;
	}
	
	/**
	 *  Converts a select set of bbcode tags. Much of the code is copied from include/bbcode.php
	 * @param string $s
	 * @return string
	 */
	static public function bbcode($s) {
			
		$s = str_replace(array('[baseurl]', '[sitename]'), array(z_root(), get_config('system', 'sitename')), $s);
			
		$s = preg_replace_callback("/\[observer\.language\=(.*?)\](.*?)\[\/observer\]/ism",'oblanguage_callback', $s);

		$s = preg_replace_callback("/\[observer\.language\!\=(.*?)\](.*?)\[\/observer\]/ism",'oblanguage_necallback', $s);


		$observer = \App::get_observer();
		if ($observer) {
			$s1 = '<span class="bb_observer" title="' . t('Different viewers will see this text differently') . '">';
			$s2 = '</span>';
			$obsBaseURL = $observer['xchan_connurl'];
			$obsBaseURL = preg_replace("/\/poco\/.*$/", '', $obsBaseURL);
			$s = str_replace('[observer.baseurl]', $obsBaseURL, $s);
			$s = str_replace('[observer.url]', $observer['xchan_url'], $s);
			$s = str_replace('[observer.name]', $s1 . $observer['xchan_name'] . $s2, $s);
			$s = str_replace('[observer.address]', $s1 . $observer['xchan_addr'] . $s2, $s);
			$s = str_replace('[observer.webname]', substr($observer['xchan_addr'], 0, strpos($observer['xchan_addr'], '@')), $s);
			$s = str_replace('[observer.photo]', '', $s);
		} 
		else {
			$s = str_replace('[observer.baseurl]', '', $s);
			$s = str_replace('[observer.url]', '', $s);
			$s = str_replace('[observer.name]', '', $s);
			$s = str_replace('[observer.address]', '', $s);
			$s = str_replace('[observer.webname]', '', $s);
			$s = str_replace('[observer.photo]', '', $s);
		}
	
		return $s;
	}
	
	static public function get_file_ext($arr) {
		if($arr['mimetype'] === 'text/bbcode')
			return '.bb';
		elseif($arr['mimetype'] === 'text/markdown')
			return '.md';
		elseif($arr['mimetype'] === 'text/plain')
			return '.txt';

	}
	
	// This function is derived from 
	// http://stackoverflow.com/questions/32068537/generate-table-of-contents-from-markdown-in-php
	static public function toc($content) {
	  // ensure using only "\n" as line-break
	  $source = str_replace(["\r\n", "\r"], "\n", $content);
	
	  // look for markdown TOC items
	  preg_match_all(
		'/^(?:=|-|#).*$/m',
		$source,
		$matches,
		PREG_PATTERN_ORDER | PREG_OFFSET_CAPTURE
	  );
	
	  // preprocess: iterate matched lines to create an array of items
	  // where each item is an array(level, text)
	  $file_size = strlen($source);
	  foreach ($matches[0] as $item) {
		$found_mark = substr($item[0], 0, 1);
		if ($found_mark == '#') {
		  // text is the found item
		  $item_text = $item[0];
		  $item_level = strrpos($item_text, '#') + 1;
		  $item_text = substr($item_text, $item_level);
		} else {
		  // text is the previous line (empty if <hr>)
		  $item_offset = $item[1];
		  $prev_line_offset = strrpos($source, "\n", -($file_size - $item_offset + 2));
		  $item_text =
			substr($source, $prev_line_offset, $item_offset - $prev_line_offset - 1);
		  $item_text = trim($item_text);
		  $item_level = $found_mark == '=' ? 1 : 2;
		}
		if (!trim($item_text) OR strpos($item_text, '|') !== FALSE) {
		  // item is an horizontal separator or a table header, don't mind
		  continue;
		}
		$raw_toc[] = ['level' => $item_level, 'text' => trim($item_text)];
	  }
		$o = '';
		foreach($raw_toc as $t) {
			$level = intval($t['level']);
			$text = $t['text'];
			switch ($level) {
				case 1:
					$li = '* ';
					break;
				case 2:
					$li = '  * ';
					break;
				case 3:
					$li = '    * ';
					break;
				case 4:
					$li = '      * ';
					break;
				default:
					$li = '* ';
					break;
			}
			$o .= $li . $text . "\n";
		}
	  return $o;
	}

}
