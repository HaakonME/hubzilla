<?php

function uexport_init(&$a) {
	if(! local_channel())
		killme();

	if(argc() > 1) {
		$channel = $a->get_channel();

		require_once('include/identity.php');

		if(argc() > 1 && intval(argv(1)) > 1900) {
			$year = intval(argv(1));
		}

		if(argc() > 2 && intval(argv(2)) > 0 && intval(argv(2)) <= 12) {
			$month = intval(argv(2));
		}

		header('content-type: application/octet_stream');
		header('content-disposition: attachment; filename="' . $channel['channel_address'] . (($year) ? '-' . $year : '') . (($month) ? '-' . $month : '') . '.json"' );

		if($year) {
			echo json_encode(identity_export_year(local_channel(),$year,$month));
			killme();
		}

		if(argc() > 1 && argv(1) === 'basic') {
			echo json_encode(identity_basic_export(local_channel()));
			killme();
		}

		// FIXME - this basically doesn't work in the wild with a channel more than a few months old due to memory and execution time limits.  
		// It probably needs to be built at the CLI and offered to download as a tarball.  Maybe stored in the members dav.

		if(argc() > 1 && argv(1) === 'complete') {
			echo json_encode(identity_basic_export(local_channel(),true));
			killme();
		}
	}
}
	
function uexport_content(&$a) {
    
	$y = datetime_convert('UTC',date_default_timezone_get(),'now','Y');
	$ly = $y-1;

	$expurl = z_root() . '/uexport';
	$o = replace_macros(get_markup_template('uexport.tpl'), array(
		'$title' => t('Export Channel'),
		'$basictitle' => t('Export Channel'),
		'$basic' => t('Export your basic channel information to a file.  This acts as a backup of your connections, permissions, profile and basic data, which can be used to import your data to a new server hub, but does not contain your content.'),
		'$fulltitle' => t('Export Content'),
		'$full' => t('Export your channel information and recent content to a JSON backup that can be restored or imported to another server hub. This backs up all of your connections, permissions, profile data and several months of posts. This file may be VERY large.  Please be patient - it may take several minutes for this download to begin.'),
		'$by_year' => t('Export your posts from a given year or month:'),

		'$extra' => t('You may also export your posts and conversations for a particular year or month. Click on one of the recent years or months below.'),
		'$extra2' => sprintf( '<a href="%1$s/%2$s">%2$s</a>: <a href="%1$s/%2$s/1">' . t('Jan') . '</a> <a href="%1$s/%2$s/2">' . t('Feb') . '</a> <a href="%1$s/%2$s/3">' . t('Mar') . '</a> <a href="%1$s/%2$s/4">' . t('Apr') . '</a> <a href="%1$s/%2$s/5">' . t('May') . '</a> <a href="%1$s/%2$s/6">' . t('Jun') . '</a> <a href="%1$s/%2$s/7">' . t('Jul') . '</a> <a href="%1$s/%2$s/8">' . t('Aug') . '</a> <a href="%1$s/%2$s/9">' . t('Sep') . '</a> <a href="%1$s/%2$s/10"> ' . t('Oct') . '</a> <a href="%1$s/%2$s/11">' . t('Nov') . '</a> <a href="%1$s/%2$s/12">' . t('Dec') . '</a>',$expurl,$ly),	
		'$extra3' => sprintf( '<a href="%1$s/%2$s">%2$s</a>: <a href="%1$s/%2$s/1">' . t('Jan') . '</a> <a href="%1$s/%2$s/2">' . t('Feb') . '</a> <a href="%1$s/%2$s/3">' . t('Mar') . '</a> <a href="%1$s/%2$s/4">' . t('Apr') . '</a> <a href="%1$s/%2$s/5">' . t('May') . '</a> <a href="%1$s/%2$s/6">' . t('Jun') . '</a> <a href="%1$s/%2$s/7">' . t('Jul') . '</a> <a href="%1$s/%2$s/8">' . t('Aug') . '</a> <a href="%1$s/%2$s/9">' . t('Sep') . '</a> <a href="%1$s/%2$s/10"> ' . t('Oct') . '</a> <a href="%1$s/%2$s/11">' . t('Nov') . '</a> <a href="%1$s/%2$s/12">' . t('Dec') . '</a>',$expurl,$y),		
		'$extra4' => t('If the export fails (possibly due to memory exhaustion on your server hub), please try again selecting a more limited date range.'),
		'$extra5' => sprintf( t('Or adjust the date in your browser location bar to select other dates. For example the year 2013; <a href="%1$s/2013">%1$s/2013</a> or the month September 2013; <a href="%1$s/2013/9">%1$s/2013/9</a>'),$expurl),
		'$extra6' => t('Please visit') . ' https://hub.tld/import_items ' . t('on another hub to import the backup files(s).'),
		'$extra7' => t('We advise you to clone the channel on the new hub first and than to import the backup file(s) (from the same channel) in chronological order. Importing the backup files into another channel will certainly give permission issues.')
		
	));
return $o;
}
