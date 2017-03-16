<?php

namespace Zotlabs\Widget;

class Appcategories {

	function widget($arr) {

		if(! local_channel())
			return '';

		$selected = ((x($_REQUEST,'cat')) ? htmlspecialchars($_REQUEST['cat'],ENT_COMPAT,'UTF-8') : '');

		// @FIXME ??? $srchurl undefined here - commented out until is reviewed
		//$srchurl =  rtrim(preg_replace('/cat\=[^\&].*?(\&|$)/is','',$srchurl),'&');
		//$srchurl = str_replace(array('?f=','&f='),array('',''),$srchurl);

		// Leaving this line which negates the effect of the two invalid lines prior
		$srchurl = z_root() . '/apps';

		$terms = array();

		$r = q("select distinct(term.term)
	        from term join app on term.oid = app.id
    	    where app_channel = %d
        	and term.uid = app_channel
	        and term.otype = %d
    	    and term.term != 'nav_featured_app'
        	order by term.term asc",
			intval(local_channel()),
		    intval(TERM_OBJ_APP)
		);

		if($r) {
			foreach($r as $rr)
				$terms[] = array('name' => $rr['term'], 'selected' => (($selected == $rr['term']) ? 'selected' : ''));

			return replace_macros(get_markup_template('categories_widget.tpl'),array(
				'$title' => t('Categories'),
				'$desc' => '',
				'$sel_all' => (($selected == '') ? 'selected' : ''),
				'$all' => t('Everything'),
				'$terms' => $terms,
				'$base' => $srchurl,

			));
		}
	}
}
