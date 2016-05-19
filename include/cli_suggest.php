<?php /** @file */

require_once('boot.php');
require_once('include/cli_startup.php');
require_once('include/socgraph.php');


function cli_suggest_run($argc,$argv){

	cli_startup();
	update_suggestions();

}

if (array_search(__file__,get_included_files())===0){
  cli_suggest_run($argc,$argv);
  killme();
}

