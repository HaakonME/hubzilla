<?php

namespace Zotlabs\Widget;

class Savedsearch {

	function widget($arr) {

		if((! local_channel()) || (! feature_enabled(local_channel(),'savedsearch')))
			return '';

		$search = ((x($_GET,'netsearch')) ? $_GET['netsearch'] : '');
		if(! $search)
			$search = ((x($_GET,'search')) ? $_GET['search'] : '');

		if(x($_GET,'searchsave') && $search) {
			$r = q("select * from term where uid = %d and ttype = %d and term = '%s' limit 1",
				intval(local_channel()),
				intval(TERM_SAVEDSEARCH),
				dbesc($search)
			);
			if(! $r) {
				q("insert into term ( uid,ttype,term ) values ( %d, %d, '%s') ",
					intval(local_channel()),
					intval(TERM_SAVEDSEARCH),
					dbesc($search)
				);
			}
		}

		if(x($_GET,'searchremove') && $search) {
			q("delete from term where uid = %d and ttype = %d and term = '%s'",
				intval(local_channel()),
				intval(TERM_SAVEDSEARCH),
				dbesc($search)
			);
			$search = '';
		}

		$srchurl = \App::$query_string;

		$srchurl =  rtrim(preg_replace('/searchsave\=[^\&].*?(\&|$)/is','',$srchurl),'&');
		$hasq = ((strpos($srchurl,'?') !== false) ? true : false);
		$srchurl =  rtrim(preg_replace('/searchremove\=[^\&].*?(\&|$)/is','',$srchurl),'&');

		$srchurl =  rtrim(preg_replace('/search\=[^\&].*?(\&|$)/is','',$srchurl),'&');
		$srchurl =  rtrim(preg_replace('/submit\=[^\&].*?(\&|$)/is','',$srchurl),'&');
		$srchurl = str_replace(array('?f=','&f='),array('',''),$srchurl);


		$hasq = ((strpos($srchurl,'?') !== false) ? true : false);
		$hasamp = ((strpos($srchurl,'&') !== false) ? true : false);

		if(($hasamp) && (! $hasq))
			$srchurl = substr($srchurl,0,strpos($srchurl,'&')) . '?f=&' . substr($srchurl,strpos($srchurl,'&')+1);

		$o = '';

		$r = q("select tid,term from term WHERE uid = %d and ttype = %d ",
			intval(local_channel()),
			intval(TERM_SAVEDSEARCH)
		);

		$saved = array();

		if(count($r)) {
			foreach($r as $rr) {
				$saved[] = array(
					'id'            => $rr['tid'],
					'term'          => $rr['term'],
					'dellink'       => z_root() . '/' . $srchurl . (($hasq || $hasamp) ? '' : '?f=') . '&amp;searchremove=1&amp;search=' . urlencode($rr['term']),
					'srchlink'      => z_root() . '/' . $srchurl . (($hasq || $hasamp) ? '' : '?f=') . '&amp;search=' . urlencode($rr['term']),
					'displayterm'   => htmlspecialchars($rr['term'], ENT_COMPAT,'UTF-8'),
					'encodedterm'   => urlencode($rr['term']),
					'delete'        => t('Remove term'),
					'selected'      => ($search==$rr['term']),
				);
			}
		}

		$tpl = get_markup_template("saved_searches.tpl");
		$o = replace_macros($tpl, array(
			'$title'	 => t('Saved Searches'),
			'$add'		 => t('add'),
			'$searchbox' => searchbox($search, 'netsearch-box', $srchurl . (($hasq) ? '' : '?f='), true),
			'$saved' 	 => $saved,
		));

		return $o;
	}
}
