<?php

use \Michelf\MarkdownExtra;

/**
 * @brief
 *
 * @param string $tocpath
 * @return string|unknown
 */
function get_help_content($tocpath = false) {

	global $lang;

	$doctype = 'markdown';

	$text = '';

	$path = (($tocpath !== false) ? $tocpath : '');

	if($tocpath === false && argc() > 1) {
		$path = '';
		for($x = 1; $x < argc(); $x ++) {
			if(strlen($path))
				$path .= '/';
			$path .= argv($x);
		}
	}

	if($path) {
		
		$title = basename($path);
		if(! $tocpath)
			\App::$page['title'] = t('Help:') . ' ' . ucwords(str_replace('-',' ',notags($title)));

		// Check that there is a "toc" or "sitetoc" located at the specified path.
		// If there is not, then there was not a translation of the table of contents
		// available and so default back to the English TOC at /doc/toc.{html,bb,md}
		// TODO: This is incompatible with the hierarchical TOC construction
		// defined in /Zotlabs/Widget/Helpindex.php.
		if($tocpath !== false && 
			load_doc_file('doc/' . $path . '.md') === '' && 
			load_doc_file('doc/' . $path . '.bb') === '' && 
			load_doc_file('doc/' . $path . '.html') === '' 
		  ) {
			$path = $title;
		}
		$text = load_doc_file('doc/' . $path . '.md');

		if(! $text) {
			$text = load_doc_file('doc/' . $path . '.bb');
			if($text)
				$doctype = 'bbcode';
		}
		if(! $text) {
			$text = load_doc_file('doc/' . $path . '.html');
			if($text)
				$doctype = 'html';
		}
	}

	if(($tocpath) && (! $text))
		return '';

	if($tocpath === false) {
		if(! $text) {
			$text = load_doc_file('doc/Site.md');
			\App::$page['title'] = t('Help');
		}
		if(! $text) {
			$doctype = 'bbcode';
			$text = load_doc_file('doc/main.bb');
			goaway('/help/about/about');
			\App::$page['title'] = t('Help');
		}

		if(! $text) {
			header($_SERVER["SERVER_PROTOCOL"] . ' 404 ' . t('Not Found'));
			$tpl = get_markup_template("404.tpl");
			return replace_macros($tpl, array(
				'$message' => t('Page not found.')
			));
		}
	}

	if($doctype === 'html')
		$content = parseIdentityAwareHTML($text);
	if($doctype === 'markdown') {
		# escape #include tags
		$text = preg_replace('/#include/ism', '%%include', $text);
		$content = MarkdownExtra::defaultTransform($text);
		$content = preg_replace('/%%include/ism', '#include', $content);
	}
	if($doctype === 'bbcode') {
		require_once('include/bbcode.php');
		$content = zidify_links(bbcode($text));
		// bbcode retargets external content to new windows. This content is internal.
		$content = str_replace(' target="_blank"', '', $content);
	}

	$content = preg_replace_callback("/#include (.*?)\;/ism", 'preg_callback_help_include', $content);

	return translate_projectname($content);
}

function preg_callback_help_include($matches) {

	if($matches[1]) {
		$include = str_replace($matches[0],load_doc_file($matches[1]),$matches[0]);
		if(preg_match('/\.bb$/', $matches[1]) || preg_match('/\.txt$/', $matches[1])) {
			require_once('include/bbcode.php');
			$include = zidify_links(bbcode($include));
			$include = str_replace(' target="_blank"','',$include);
		}
		elseif(preg_match('/\.md$/', $matches[1])) {
			$include = MarkdownExtra::defaultTransform($include);
		}
		return $include;
	}

}

function determine_help_language() {
	require_once('Text/LanguageDetect.php');
	$lang_detect = new Text_LanguageDetect();
	// Set this mode to recognize language by the short code like "en", "ru", etc.
	$lang_detect->setNameMode(2);
	// If the language was specified in the URL, override the language preference 
	// of the browser. Default to English if both of these are absent.
	if($lang_detect->languageExists(argv(1))) {
		$lang = argv(1);
		$from_url = true;
	} else {
		$lang = \App::$language;
		if(! isset($lang)) 
			$lang = 'en';
		$from_url = false;
	}
	return array('language' => $lang, 'from_url' => $from_url);
}

function load_doc_file($s) {
	$path = 'doc';
	// Determine the language and modify the path accordingly
	$x = determine_help_language();
	$lang = $x['language'];
	$url_idx = ($x['from_url'] ? 1 : 0);
	// The English translation is at the root of /doc/. Other languages are in 
	// subfolders named by the language code such as "de", "es", etc.
	if($lang !== 'en') {
		$path .= '/' . $lang;	
	}

	$b = basename($s);
	
	for($i=1+$url_idx; $i<argc()-1; $i++) {
		$path .= '/' . argv($i);
	}
	$c = find_doc_file($path . '/' . $b);
	if($c)
		return $c;
	// Possibly a translation was requested that has not been translated, so fall
	// back to the English version
	$path = 'doc';
	for($i=1+$url_idx; $i<argc()-1; $i++) {
		$path .= '/' . argv($i);
	}
	$c = find_doc_file($path . '/' . $b);
	if($c)
		return $c;
	// Try one last time to find the file at the explicit path input to the function
	$c = find_doc_file($s);
	if($c)
		return $c;
	return '';
}

function find_doc_file($s) {
	if(file_exists($s)) {
		return file_get_contents($s);
	}
	return '';
}

/**
 * @brief
 *
 * @param string $s
 * @return number|mixed|unknown|boolean
 */
function search_doc_files($s) {


	\App::set_pager_itemspage(60);
	$pager_sql = sprintf(" LIMIT %d OFFSET %d ", intval(\App::$pager['itemspage']), intval(\App::$pager['start']));

	$regexop = db_getfunc('REGEXP');

	$r = q("select iconfig.v, item.* from item left join iconfig on item.id = iconfig.iid
		where iconfig.cat = 'system' and iconfig.k = 'docfile' and
		body $regexop '%s' and item_type = %d $pager_sql",
		dbesc($s),
		intval(ITEM_TYPE_DOC)
	);

	$r = fetch_post_tags($r, true);

	for($x = 0; $x < count($r); $x ++) {
		$position =	stripos($r[$x]['body'], $s);
		$dislen = 300;
		$start = $position-floor($dislen/2);
		if ( $start < 0) {
			$start = 0;
		}
		$r[$x]['text'] = substr($r[$x]['body'], $start, $dislen);

		$r[$x]['rank'] = 0;
		if($r[$x]['term']) {
			foreach($r[$x]['term'] as $t) {
				if(stristr($t['term'],$s)) {
					$r[$x]['rank'] ++;
				}
			}
		}
		if(stristr($r[$x]['v'], $s))
			$r[$x]['rank'] ++;
		$r[$x]['rank'] += substr_count(strtolower($r[$x]['text']), strtolower($s));
		// bias the results to the observer's native language
		if($r[$x]['lang'] === \App::$language)
			$r[$x]['rank'] = $r[$x]['rank'] + 10;

	}
	usort($r,'doc_rank_sort');

	return $r;
}


function doc_rank_sort($s1, $s2) {
	if($s1['rank'] == $s2['rank'])
		return 0;

	return (($s1['rank'] < $s2['rank']) ? 1 : (-1));
}

/**
 * @brief
 *
 * @return string
 */

function load_context_help() {

	$path = App::$cmd;
	$args = App::$argv;
	$lang = App::$language;

	if(! isset($lang) || !is_dir('doc/context/' . $lang . '/')) {
		$lang = 'en';
	}
	while($path) {
		$context_help = load_doc_file('doc/context/' . $lang . '/' . $path . '/help.html');
		if(!$context_help) {
			// Fallback to English if the translation is absent
			$context_help = load_doc_file('doc/context/en/' . $path . '/help.html');
		}
		if($context_help)
			break;

		array_pop($args);
		$path = implode($args,'/');
	}

	return $context_help;
}

/**
 * @brief
 *
 * @param string $s
 * @return void|boolean[]|number[]|string[]|unknown[]
 */
function store_doc_file($s) {

	if(is_dir($s))
		return;

	$item = array();
	$sys = get_sys_channel();

	$item['aid'] = 0;
	$item['uid'] = $sys['channel_id'];

	if(strpos($s, '.md'))
		$mimetype = 'text/markdown';
	elseif(strpos($s, '.html'))
		$mimetype = 'text/html';
	else
		$mimetype = 'text/bbcode';

	require_once('include/html2plain.php');

	$item['body'] = html2plain(prepare_text(file_get_contents($s),$mimetype, true));
	$item['mimetype'] = 'text/plain';

	$item['plink'] = z_root() . '/' . str_replace('doc','help',$s);
	$item['owner_xchan'] = $item['author_xchan'] = $sys['channel_hash'];
	$item['item_type'] = ITEM_TYPE_DOC;

	$r = q("select item.* from item left join iconfig on item.id = iconfig.iid
		where iconfig.cat = 'system' and iconfig.k = 'docfile' and
		iconfig.v = '%s' and item_type = %d limit 1",
		dbesc($s),
		intval(ITEM_TYPE_DOC)
	);

	\Zotlabs\Lib\IConfig::Set($item,'system','docfile',$s);

	if($r) {
		$item['id'] = $r[0]['id'];
		$item['mid'] = $item['parent_mid'] = $r[0]['mid'];
		$x = item_store_update($item);
	}
	else {
		$item['mid'] = $item['parent_mid'] = item_message_id();
		$x = item_store($item);
	}

	return $x;
}
