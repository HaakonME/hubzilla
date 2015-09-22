<?php

function dreport_content(&$a) {

	if(! local_channel()) {
		notice( t('Permission denied') . EOL);
		return;
	}

	$channel = $a->get_channel();
	
	$mid = ((argc() > 1) ? argv(1) : '');

	if(! $mid) {
		notice( t('Invalid message') . EOL);
		return;
	}
	
	$r = q("select * from dreport where dreport_xchan = '%s' and dreport_mid = '%s'",
		dbesc($channel['channel_hash']),
		dbesc($mid)
	);

	if(! $r) {
		notice( t('no results') . EOL);
		return;
	}


	$o .= '<h2>' . sprintf( t('Delivery report for %1$s'),substr($mid,0,32)) . '...' . '</h2>';
	$o .= '<table>';

	foreach($r as $rr) {
		$name = escape_tags(substr($rr['dreport_recip'],strpos($rr['dreport_recip'],' ')));
		$o .= '<tr><td>' . $name . '</td><td>' . $rr['dreport_result'] . '</td><td>' . $rr['dreport_time'] . '</td></tr>';
	}
	$o .= '</table>';

	return $o;



}