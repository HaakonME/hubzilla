<?php

function opensearch_init(&$a) {
    	
	$tpl = get_markup_template('opensearch.tpl');
	
	header("Content-type: application/opensearchdescription+xml");
	
	$o = replace_macros($tpl, array(
		'$baseurl'  => z_root(),
		'$nodename' => $a->get_hostname(),
	));
		
	echo $o;
		
	killme();
		
}
