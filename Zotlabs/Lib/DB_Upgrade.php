<?php

namespace Zotlabs\Lib;


class DB_Upgrade {

	public $config_name = '';
	public $func_prefix = '';

	function __construct($db_revision) {

		$platform_name = System::get_platform_name();

		$update_file = 'install/' . $platform_name . '/update.php';
		if(! file_exists($update_file)) {
			$update_file = 'install/update.php';
			$this->config_name = 'db_version';
			$this->func_prefix = 'update_r';
		}
		else {
			$this->config_name = $platform_name . '_db_version';
			$this->func_prefix = $platform_name . '_update_';
		}

		$build = get_config('system', $this->config_name, 0);
		if(! intval($build))
			$build = set_config('system', $this->config_name, $db_revision);

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

			if(($stored < $current) && file_exists($update_file)) {

				Config::Load('database');

				// We're reporting a different version than what is currently installed.
				// Run any existing update scripts to bring the database up to current.
				
				require_once($update_file);

				// make sure that boot.php and update.php are the same release, we might be
				// updating from git right this very second and the correct version of the update.php
				// file may not be here yet. This can happen on a very busy site.

				if($db_revision == UPDATE_VERSION) {
					for($x = $stored; $x < $current; $x ++) {
						$func = $this->func_prefix . $x;
						if(function_exists($func)) {
							// There could be a lot of processes running or about to run.
							// We want exactly one process to run the update command.
							// So store the fact that we're taking responsibility
							// after first checking to see if somebody else already has.

							// If the update fails or times-out completely you may need to
							// delete the config entry to try again.

							if(get_config('database', $func))
								break;
							set_config('database',$func, '1');
							// call the specific update

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
									dbesc(\App::$config['system']['admin_email'])
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
								set_config('database',$func, 'success');
							}
						}
					}
					set_config('system', $this->config_name, $db_revision);
				}
			}
		}
	}
}