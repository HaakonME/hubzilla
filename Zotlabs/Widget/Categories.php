<?php

namespace Zotlabs\Widget;

require_once('include/contact_widgets.php');

class Categories {

	function widget($arr) {

		$cards = ((array_key_exists('cards',$arr) && $arr['cards']) ? true : false);

		if(($cards) && (! feature_enabled(\App::$profile['profile_uid'],'cards')))
			return '';

		if((! \App::$profile['profile_uid']) 
			|| (! perm_is_allowed(\App::$profile['profile_uid'],get_observer_hash(),(($cards) ? 'view_pages' : 'view_stream')))) {
			return '';
		}

		$cat = ((x($_REQUEST,'cat')) ? htmlspecialchars($_REQUEST['cat'],ENT_COMPAT,'UTF-8') : '');
		$srchurl = (($cards) ? \App::$argv[0] . '/' . \App::$argv[1] : \App::$query_string);
		$srchurl =  rtrim(preg_replace('/cat\=[^\&].*?(\&|$)/is','',$srchurl),'&');
		$srchurl = str_replace(array('?f=','&f='),array('',''),$srchurl);

		if($cards)
			return cardcategories_widget($srchurl, $cat);
		else
			return categories_widget($srchurl, $cat);

	}
}
