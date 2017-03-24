<?php

namespace Zotlabs\Lib;


class DB_Upgrade {


	function __construct($db_revision) {

		$build = get_config('system','db_version',0);
		if(! intval($build))
			$build = set_config('system','db_version',$db_revision);

		if($build == $db_revision) {
			// Nothing to be done.
			return;
		}
		else {
			$stored = intval($build);
			if(! $stored) {
				logger('Critical: check_config unable to determine database schema version');
				return;
			}
		
			$current = intval($db_revision);
			if(($stored < $current) && file_exists('install/update.php')) {

				Config::Load('database');

				// We're reporting a different version than what is currently installed.
				// Run any existing update scripts to bring the database up to current.
				
				require_once('install/update.php');

				// make sure that boot.php and update.php are the same release, we might be
				// updating from git right this very second and the correct version of the update.php
				// file may not be here yet. This can happen on a very busy site.

				if($db_revision == UPDATE_VERSION) {
					for($x = $stored; $x < $current; $x ++) {
						if(function_exists('update_r' . $x)) {
							// There could be a lot of processes running or about to run.
							// We want exactly one process to run the update command.
							// So store the fact that we're taking responsibility
							// after first checking to see if somebody else already has.

							// If the update fails or times-out completely you may need to
							// delete the config entry to try again.

							if(get_config('database','update_r' . $x))
								break;
							set_config('database','update_r' . $x, '1');
							// call the specific update

							$func = 'update_r' . $x;
							$retval = $func();
							if($retval) {

								// Prevent sending hundreds of thousands of emails by creating
								// a lockfile.  

								$lockfile = 'store/[data]/mailsent';

								if ((file_exists($lockfile)) && (filemtime($lockfile) > (time() - 86400)))
									return;
								@unlink($lockfile);
								//send the administrator an e-mail
								file_put_contents($lockfile, $x);
							
								$r = q("select account_language from account where account_email = '%s' limit 1",
									dbesc(App::$config['system']['admin_email'])
								);
								push_lang(($r) ? $r[0]['account_language'] : 'en');

								z_mail(
									[
										'toEmail'        => \App::$config['system']['admin_email'],
										'messageSubject' => sprintf( t('Update Error at %s'), z_root()),
										'textVersion'    => replace_macros(get_intltext_template('update_fail_eml.tpl'), 
											[
												'$sitename' => \App::$config['system']['sitename'],
												'$siteurl' =>  z_root(),
												'$update' => $x,
												'$error' => sprintf( t('Update %s failed. See error logs.'), $x)
											]
										)
									]
								);

								//try the logger
								logger('CRITICAL: Update Failed: ' . $x);
								pop_lang();
							}
							else {
								set_config('database','update_r' . $x, 'success');
							}
						}
					}
					set_config('system','db_version', $db_revision);
				}
			}
		}
	}
}