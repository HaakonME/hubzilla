<?php

namespace Zotlabs\Widget;


class Sitesearch {

	function widget($arr) {

		$search = ((x($_GET,'search')) ? $_GET['search'] : '');

		$srchurl = \App::$query_string;

		$srchurl =  rtrim(preg_replace('/search\=[^\&].*?(\&|$)/is','',$srchurl),'&');
		$srchurl =  rtrim(preg_replace('/submit\=[^\&].*?(\&|$)/is','',$srchurl),'&');
		$srchurl = str_replace(array('?f=','&f='),array('',''),$srchurl);


		$hasq = ((strpos($srchurl,'?') !== false) ? true : false);
		$hasamp = ((strpos($srchurl,'&') !== false) ? true : false);

		if(($hasamp) && (! $hasq))
			$srchurl = substr($srchurl,0,strpos($srchurl,'&')) . '?f=&' . substr($srchurl,strpos($srchurl,'&')+1);

		$o = '';

		$saved = array();

		$tpl = get_markup_template("sitesearch.tpl");
		$o = replace_macros($tpl, array(
			'$title'	 => t('Search'),
			'$searchbox' => searchbox($search, 'netsearch-box', $srchurl . (($hasq) ? '' : '?f='), false),
			'$saved' 	 => $saved,
		));

		return $o;
	}
}
