<?php

namespace Zotlabs\Admin;


class Plugins extends \Zotlabs\Web\Controller {

	function get() {

		/*
		 * Single plugin
		 */

		if (\App::$argc == 3){
			$plugin = \App::$argv[2];
			if (!is_file("addon/$plugin/$plugin.php")){
				notice( t("Item not found.") );
				return '';
			}
	
			$enabled = in_array($plugin,\App::$plugins);
			$info = get_plugin_info($plugin);
			$x = check_plugin_versions($info);
	
			// disable plugins which are installed but incompatible versions
	
			if($enabled && ! $x) {
				$enabled = false;
				$idz = array_search($plugin, \App::$plugins);
				if ($idz !== false) {
					unset(\App::$plugins[$idz]);
					uninstall_plugin($plugin);
					set_config("system","addon", implode(", ",\App::$plugins));
				}
			}
			$info['disabled'] = 1-intval($x);
	
			if (x($_GET,"a") && $_GET['a']=="t"){
				check_form_security_token_redirectOnErr('/admin/plugins', 'admin_plugins', 't');
				$pinstalled = false;
				// Toggle plugin status
				$idx = array_search($plugin, \App::$plugins);
				if ($idx !== false){
					unset(\App::$plugins[$idx]);
					uninstall_plugin($plugin);
					$pinstalled = false;
					info( sprintf( t("Plugin %s disabled."), $plugin ) );
				} else {
					\App::$plugins[] = $plugin;
					install_plugin($plugin);
					$pinstalled = true;
					info( sprintf( t("Plugin %s enabled."), $plugin ) );
				}
				set_config("system","addon", implode(", ",\App::$plugins));

				if($pinstalled) {
					@require_once("addon/$plugin/$plugin.php");
					if(function_exists($plugin.'_plugin_admin'))
						goaway(z_root() . '/admin/plugins/' . $plugin);
				}
				goaway(z_root() . '/admin/plugins' );
			}
			// display plugin details
			require_once('library/markdown.php');
	
			if (in_array($plugin, \App::$plugins)){
				$status = 'on';
				$action = t('Disable');
			} else {
				$status = 'off';
				$action =  t('Enable');
			}
	
			$readme = null;
			if (is_file("addon/$plugin/README.md")){
				$readme = file_get_contents("addon/$plugin/README.md");
				$readme = Markdown($readme);
			} else if (is_file("addon/$plugin/README")){
				$readme = "<pre>". file_get_contents("addon/$plugin/README") ."</pre>";
			}
	
			$admin_form = '';
	
			$r = q("select * from addon where plugin_admin = 1 and aname = '%s' limit 1",
				dbesc($plugin)
			);
	
			if($r) {
				@require_once("addon/$plugin/$plugin.php");
				if(function_exists($plugin.'_plugin_admin')) {
					$func = $plugin.'_plugin_admin';
					$func($a, $admin_form);
				}
			}
	
	
			$t = get_markup_template('admin_plugins_details.tpl');
			return replace_macros($t, array(
				'$title' => t('Administration'),
				'$page' => t('Plugins'),
				'$toggle' => t('Toggle'),
				'$settings' => t('Settings'),
				'$baseurl' => z_root(),
	
				'$plugin' => $plugin,
				'$status' => $status,
				'$action' => $action,
				'$info' => $info,
				'$str_author' => t('Author: '),
				'$str_maintainer' => t('Maintainer: '),
				'$str_minversion' => t('Minimum project version: '),
				'$str_maxversion' => t('Maximum project version: '),
				'$str_minphpversion' => t('Minimum PHP version: '),
				'$str_serverroles' => t('Compatible Server Roles: '),
				'$str_requires' => t('Requires: '),
				'$disabled' => t('Disabled - version incompatibility'),
	
				'$admin_form' => $admin_form,
				'$function' => 'plugins',
				'$screenshot' => '',
				'$readme' => $readme,
	
				'$form_security_token' => get_form_security_token('admin_plugins'),
			));
		}
	
	
		/*
		 * List plugins
		 */
		$plugins = array();
		$files = glob('addon/*/');
		if($files) {
			foreach($files as $file) {
				if (is_dir($file)){
					list($tmp, $id) = array_map('trim', explode('/', $file));
					$info = get_plugin_info($id);
					$enabled = in_array($id,\App::$plugins);
					$x = check_plugin_versions($info);
	
					// disable plugins which are installed but incompatible versions
	
					if($enabled && ! $x) {
						$enabled = false;
						$idz = array_search($id, \App::$plugins);
						if ($idz !== false) {
							unset(\App::$plugins[$idz]);
							uninstall_plugin($id);
							set_config("system","addon", implode(", ",\App::$plugins));
						}
					}
					$info['disabled'] = 1-intval($x);
	
					$plugins[] = array( $id, (($enabled)?"on":"off") , $info);
				}
			}
		}
	
		usort($plugins,'self::plugin_sort');

		
		$admin_plugins_add_repo_form= replace_macros(
			get_markup_template('admin_plugins_addrepo.tpl'), array(
				'$post' => 'admin/plugins/addrepo',
				'$desc' => t('Enter the public git repository URL of the plugin repo.'),
				'$repoURL' => array('repoURL', t('Plugin repo git URL'), '', ''),
				'$repoName' => array('repoName', t('Custom repo name'), '', '', t('(optional)')),
				'$submit' => t('Download Plugin Repo')
			)
		);
		$newRepoModalID = random_string(3);
		$newRepoModal = replace_macros(
			get_markup_template('generic_modal.tpl'), array(
				'$id' => $newRepoModalID,
				'$title' => t('Install new repo'),
				'$ok' => t('Install'),
				'$cancel' => t('Cancel')
			)
		);
			
		$reponames = $this->listAddonRepos();
		$addonrepos = [];
		foreach($reponames as $repo) {
			$addonrepos[] = array('name' => $repo, 'description' => '');
			// TODO: Parse repo info to provide more information about repos
		}
		
		$t = get_markup_template('admin_plugins.tpl');
		return replace_macros($t, array(
			'$title' => t('Administration'),
			'$page' => t('Plugins'),
			'$submit' => t('Submit'),
			'$baseurl' => z_root(),
			'$function' => 'plugins',
			'$plugins' => $plugins,
			'$disabled' => t('Disabled - version incompatibility'),
			'$form_security_token' => get_form_security_token('admin_plugins'),
			'$managerepos' => t('Manage Repos'),
			'$installedtitle' => t('Installed Plugin Repositories'),
			'$addnewrepotitle' =>	t('Install a New Plugin Repository'),
			'$expandform' => false,
			'$form' => $admin_plugins_add_repo_form,
			'$newRepoModal' => $newRepoModal,
			'$newRepoModalID' => $newRepoModalID,
			'$addonrepos' => $addonrepos,
			'$repoUpdateButton' => t('Update'),
			'$repoBranchButton' => t('Switch branch'),
			'$repoRemoveButton' => t('Remove')
		));
	}

	function listAddonRepos() {
		$addonrepos = [];
		$addonDir = __DIR__ . '/../../extend/addon/';
		if(is_dir($addonDir)) {
			if ($handle = opendir($addonDir)) {
				while (false !== ($entry = readdir($handle))) {
					if ($entry != "." && $entry != "..") {
						$addonrepos[] = $entry;
					}
				}
				closedir($handle);
			}
		}
		return $addonrepos;
	}

	static public function plugin_sort($a,$b) {
		return(strcmp(strtolower($a[2]['name']),strtolower($b[2]['name'])));
	}


}