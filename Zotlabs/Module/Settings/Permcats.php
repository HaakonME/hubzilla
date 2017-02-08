<?php

namespace Zotlabs\Module\Settings;



class Permcats {

	function post() {

		if(! local_channel())
			return;

		$channel = \App::get_channel();

		check_form_security_token_redirectOnErr('/settings/permcats', 'settings_permcats');


		$all_perms = \Zotlabs\Access\Permissions::Perms();

		$name = escape_tags(trim($_POST['name']));

		$pcarr = [];

		if($all_perms) {
			foreach($all_perms as $perm => $desc) {
				if(array_key_exists('perms_' . $perm, $_POST)) {
					$pcarr[] = $perm;
				}
			}
		}
		
		\Zotlabs\Lib\Permcat::update(local_channel(),$name,$pcarr);

		build_sync_packet();

		info( t('Permission category saved.') . EOL);
		
		return;
	}
	

	function get() {

		if(! local_channel())
			return;

		$channel = \App::get_channel();


		if(argc() > 2) 
			$name = argv(2);			


		$desc = t('Use this form to create permission rules for various classes of people or connections.');

		$global_perms = \Zotlabs\Access\Permissions::Perms();

		$their_perms = [];

		$existing = get_all_perms(local_channel(),(($atoken_xchan) ? $atoken_xchan : ''));

		if($atoken_xchan) {
			$theirs = q("select * from abconfig where chan = %d and xchan = '%s' and cat = 'their_perms'",
				intval(local_channel()),
				dbesc($atoken_xchan)
			);
			if($theirs) {
				foreach($theirs as $t) {
					$their_perms[$t['k']] = $t['v'];
				}
			}
		}
		foreach($global_perms as $k => $v) {
			$thisperm = get_abconfig(local_channel(),$contact['abook_xchan'],'my_perms',$k);
//fixme

			$checkinherited = \Zotlabs\Access\PermissionLimits::Get(local_channel(),$k);

			if($existing[$k])
				$thisperm = "1";

			$perms[] = array('perms_' . $k, $v, ((array_key_exists($k,$their_perms)) ? intval($their_perms[$k]) : ''),$thisperm, 1, (($checkinherited & PERMS_SPECIFIC) ? '' : '1'), '', $checkinherited);
		}



		$tpl = get_markup_template("settings_permcats.tpl");
		$o .= replace_macros($tpl, array(
			'$form_security_token' => get_form_security_token("settings_permcats"),
			'$title'	=> t('Permission Categories'),
			'$desc'     => $desc,
			'$desc2' => $desc2,
			'$tokens' => $t,
			'$atoken' => $atoken,
			'$url1' => z_root() . '/channel/' . $channel['channel_address'],
			'$url2' => z_root() . '/photos/' . $channel['channel_address'],
			'$name' => array('name', t('Permission Name') . ' <span class="required">*</span>', (($name) ? $name : ''), ''),
			'$me' => t('My Settings'),
			'$perms' => $perms,
			'$inherited' => t('inherited'),
			'$notself' => 0,
			'$self' => 1,
			'$permlbl' => t('Individual Permissions'),
			'$permnote' => t('Some permissions may be inherited from your channel\'s <a href="settings"><strong>privacy settings</strong></a>, which have higher priority than individual settings. You can <strong>not</strong> change those settings here.'),
			'$submit' 	=> t('Submit')
		));
		return $o;
	}
	
}