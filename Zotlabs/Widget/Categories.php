<?php

namespace Zotlabs\Widget;

require_once('include/contact_widgets.php');

class Categories {

	function widget($arr) {

		if((! \App::$profile['profile_uid']) 
			|| (! perm_is_allowed(\App::$profile['profile_uid'],get_observer_hash(),'view_stream'))) {
			return '';
		}

		$cat = ((x($_REQUEST,'cat')) ? htmlspecialchars($_REQUEST['cat'],ENT_COMPAT,'UTF-8') : '');
		$srchurl = \App::$query_string;
		$srchurl =  rtrim(preg_replace('/cat\=[^\&].*?(\&|$)/is','',$srchurl),'&');
		$srchurl = str_replace(array('?f=','&f='),array('',''),$srchurl);

		return categories_widget($srchurl, $cat);
	}
}
