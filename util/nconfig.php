#!/usr/bin/env php
<?php

/**
* switch off channel email notifications utility
* This is a preliminary solution using the existing functions from include/channel.php. 
* More options would be nice.
**/

if(! file_exists('include/cli_startup.php')) {
    echo 'Run from the top level $Projectname web directory, as util/nconfig <args>' . PHP_EOL;
    exit(1);
}

require_once('include/cli_startup.php');
require_once('include/channel.php');

cli_startup();


	if($argc != 2) {
		echo 'Usage: util/nconfig channel_id|channel_address off' . PHP_EOL;
		exit(1);
	}

	if(ctype_digit($argv[1])) {
		$c = channelx_by_n($argv[1]);
	}
	else {
		$c = channelx_by_nick($argv[1]);
	}

	if(! $c) {
		echo t('Source channel not found.');
		exit(1);
	}

	switch ($argv[2]) {
		case 'off':
			$result = notifications_off($c['channel_id']);
			break;
		default:
			echo 'Only on or off in lower cases are allowed' . PHP_EOL;
			exit(1);
	}

	if($result['success'] == false) {
		echo $result['message'];
		exit(1);
	}

	exit(0);
  
