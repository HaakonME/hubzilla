<?php

namespace Zotlabs\Widget;

class Settings_menu {

	function widget($arr) {

		if(! local_channel())
			return;


		$channel = \App::get_channel();

		$abook_self_id = 0;

		// Retrieve the 'self' address book entry for use in the auto-permissions link

		$role = get_pconfig(local_channel(),'system','permissions_role');

		$abk = q("select abook_id from abook where abook_channel = %d and abook_self = 1 limit 1",
			intval(local_channel())
		);
		if($abk)
			$abook_self_id = $abk[0]['abook_id'];

		$x = q("select count(*) as total from hubloc where hubloc_hash = '%s' and hubloc_deleted = 0 ",
			dbesc($channel['channel_hash'])
		);

		$hublocs = (($x && $x[0]['total'] > 1) ? true : false);

		$tabs = array(
			array(
				'label'	=> t('Account settings'),
				'url' 	=> z_root().'/settings/account',
				'selected'	=> ((argv(1) === 'account') ? 'active' : ''),
			),

			array(
				'label'	=> t('Channel settings'),
				'url' 	=> z_root().'/settings/channel',
				'selected'	=> ((argv(1) === 'channel') ? 'active' : ''),
			),

		);

		if(get_account_techlevel() > 0 && get_features()) {
			$tabs[] = 	array(
					'label'	=> t('Additional features'),
					'url' 	=> z_root().'/settings/features',
					'selected'	=> ((argv(1) === 'features') ? 'active' : ''),
			);
		}

		$tabs[] =	array(
			'label'	=> t('Feature/Addon settings'),
			'url' 	=> z_root().'/settings/featured',
			'selected'	=> ((argv(1) === 'featured') ? 'active' : ''),
		);

		$tabs[] =	array(
			'label'	=> t('Display settings'),
			'url' 	=> z_root().'/settings/display',
			'selected'	=> ((argv(1) === 'display') ? 'active' : ''),
		);

		if($hublocs) {
			$tabs[] = array(
				'label' => t('Manage locations'),
				'url' => z_root() . '/locs',
				'selected' => ((argv(1) === 'locs') ? 'active' : ''),
			);
		}

		$tabs[] =	array(
			'label' => t('Export channel'),
			'url' => z_root() . '/uexport',
			'selected' => ''
		);

		if(get_account_techlevel() > 0) {
			$tabs[] =	array(
				'label' => t('Connected apps'),
				'url' => z_root() . '/settings/oauth',
				'selected' => ((argv(1) === 'oauth') ? 'active' : ''),
			);
		}

		if(get_account_techlevel() > 2) {
			$tabs[] =	array(
				'label' => t('Guest Access Tokens'),
				'url' => z_root() . '/settings/tokens',
				'selected' => ((argv(1) === 'tokens') ? 'active' : ''),
			);
		}

		if(feature_enabled(local_channel(),'permcats')) {
			$tabs[] = array(
				'label' => t('Permission Groups'),
				'url' => z_root() . '/settings/permcats',
				'selected' => ((argv(1) === 'permcats') ? 'active' : ''),
			);
		}


		if($role === false || $role === 'custom') {
			$tabs[] = array(
				'label' => t('Connection Default Permissions'),
				'url' => z_root() . '/connedit/' . $abook_self_id,
				'selected' => ''
			);
		}

		if(feature_enabled(local_channel(),'premium_channel')) {
			$tabs[] = array(
				'label' => t('Premium Channel Settings'),
				'url' => z_root() . '/connect/' . $channel['channel_address'],
				'selected' => ''
			);
		}

		if(feature_enabled(local_channel(),'channel_sources')) {
			$tabs[] = array(
				'label' => t('Channel Sources'),
				'url' => z_root() . '/sources',
				'selected' => ''
			);
		}

		$tabtpl = get_markup_template("generic_links_widget.tpl");
		return replace_macros($tabtpl, array(
			'$title' => t('Settings'),
			'$class' => 'settings-widget',
			'$items' => $tabs,
		));
	}

}