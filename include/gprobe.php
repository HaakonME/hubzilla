<?php /** @file */

require_once('include/cli_startup.php');
require_once('include/zot.php');

// CLI utility
// performs zot_finger on $argv[1], which is a hex_encoded webbie/reddress

function gprobe_run($argc,$argv){

	cli_startup();

	$a = get_app();

	if($argc != 2)
		return;

	$url = hex2bin($argv[1]);

	if(! strpos($url,'@'))
		return;

	$r = q("select * from xchan where xchan_addr = '%s' limit 1",
		dbesc($url)
	);

	if(! $r) {
		$x = zot_finger($url,null);
		if($x['success']) {
			$j = json_decode($x['body'],true);
			$y = import_xchan($j);
		}
	}

	return;
}

if (array_search(__file__,get_included_files())===0){
  gprobe_run($argc,$argv);
  killme();
}
