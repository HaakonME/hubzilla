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

	return print_r($r,true);


}