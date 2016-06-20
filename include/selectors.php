<?php /** @file */


function contact_profile_assign($current) {

	$o = '';

	$o .= "<select id=\"contact-profile-selector\" name=\"profile_assign\" class=\"form-control\"/>\r\n";

	$r = q("SELECT profile_guid, profile_name FROM `profile` WHERE `uid` = %d",
		intval($_SESSION['uid']));

	if($r) {
		foreach($r as $rr) {
			$selected = (($rr['profile_guid'] == $current) ? " selected=\"selected\" " : "");
			$o .= "<option value=\"{$rr['profile_guid']}\" $selected >{$rr['profile_name']}</option>\r\n";
		}
	}
	$o .= "</select>\r\n";
	return $o;
}

function contact_poll_interval($current, $disabled = false) {

	$dis = (($disabled) ? ' disabled="disabled" ' : '');
	$o = '';
	$o .= "<select id=\"contact-poll-interval\" name=\"poll\" $dis />" . "\r\n";

	$rep = array(
		0 => t('Frequently'),
		1 => t('Hourly'),
		2 => t('Twice daily'),
		3 => t('Daily'),
		4 => t('Weekly'),
		5 => t('Monthly')
	);

	foreach($rep as $k => $v) {
		$selected = (($k == $current) ? " selected=\"selected\" " : "");
		$o .= "<option value=\"$k\" $selected >$v</option>\r\n";
	}
	$o .= "</select>\r\n";
	return $o;
}


function gender_selector($current="",$suffix="") {
	$o = '';
	$select = array('', t('Male'), t('Female'), t('Currently Male'), t('Currently Female'), t('Mostly Male'), t('Mostly Female'), t('Transgender'), t('Intersex'), t('Transsexual'), t('Hermaphrodite'), t('Neuter'), t('Non-specific'), t('Other'), t('Undecided'));

	call_hooks('gender_selector', $select);

	$o .= "<select class=\"form-control\" name=\"gender$suffix\" id=\"gender-select$suffix\" size=\"1\" >";
	foreach($select as $selection) {
		if($selection !== 'NOTRANSLATION') {
			$selected = (($selection == $current) ? ' selected="selected" ' : '');
			$o .= "<option value=\"$selection\" $selected >$selection</option>";
		}
	}
	$o .= '</select>';
	return $o;
}	

function gender_selector_min($current="",$suffix="") {
	$o = '';
	$select = array('', t('Male'), t('Female'), t('Other'));

	call_hooks('gender_selector_min', $select);

	$o .= "<select class=\"form-control\" name=\"gender$suffix\" id=\"gender-select$suffix\" size=\"1\" >";
	foreach($select as $selection) {
		if($selection !== 'NOTRANSLATION') {
			$selected = (($selection == $current) ? ' selected="selected" ' : '');
			$o .= "<option value=\"$selection\" $selected >$selection</option>";
		}
	}
	$o .= '</select>';
	return $o;
}	



function sexpref_selector($current="",$suffix="") {
	$o = '';
	$select = array('', t('Males'), t('Females'), t('Gay'), t('Lesbian'), t('No Preference'), t('Bisexual'), t('Autosexual'), t('Abstinent'), t('Virgin'), t('Deviant'), t('Fetish'), t('Oodles'), t('Nonsexual'));


	call_hooks('sexpref_selector', $select);

	$o .= "<select class=\"form-control\" name=\"sexual$suffix\" id=\"sexual-select$suffix\" size=\"1\" >";
	foreach($select as $selection) {
		if($selection !== 'NOTRANSLATION') {
			$selected = (($selection == $current) ? ' selected="selected" ' : '');
			$o .= "<option value=\"$selection\" $selected >$selection</option>";
		}
	}
	$o .= '</select>';
	return $o;
}	


function sexpref_selector_min($current="",$suffix="") {
	$o = '';
	$select = array('', t('Males'), t('Females'), t('Other'));

	call_hooks('sexpref_selector_min', $select);

	$o .= "<select class=\"form-control\" name=\"sexual$suffix\" id=\"sexual-select$suffix\" size=\"1\" >";
	foreach($select as $selection) {
		if($selection !== 'NOTRANSLATION') {
			$selected = (($selection == $current) ? ' selected="selected" ' : '');
			$o .= "<option value=\"$selection\" $selected >$selection</option>";
		}
	}
	$o .= '</select>';
	return $o;
}	



function marital_selector($current="",$suffix="") {
	$o = '';
	$select = array('', t('Single'), t('Lonely'), t('Available'), t('Unavailable'), t('Has crush'), t('Infatuated'), t('Dating'), t('Unfaithful'), t('Sex Addict'), t('Friends'), t('Friends/Benefits'), t('Casual'), t('Engaged'), t('Married'), t('Imaginarily married'), t('Partners'), t('Cohabiting'), t('Common law'), t('Happy'), t('Not looking'), t('Swinger'), t('Betrayed'), t('Separated'), t('Unstable'), t('Divorced'), t('Imaginarily divorced'), t('Widowed'), t('Uncertain'), t('It\'s complicated'), t('Don\'t care'), t('Ask me') );

	call_hooks('marital_selector', $select);

	$o .= "<select class=\"form-control\" name=\"marital\" id=\"marital-select\" size=\"1\" >";
	foreach($select as $selection) {
		if($selection !== 'NOTRANSLATION') {
			$selected = (($selection == $current) ? ' selected="selected" ' : '');
			$o .= "<option value=\"$selection\" $selected >$selection</option>";
		}
	}
	$o .= '</select>';
	return $o;
}	

function marital_selector_min($current="",$suffix="") {
	$o = '';
	$select = array('', t('Single'), t('Dating'), t('Cohabiting'), t('Married'), t('Separated'), t('Divorced'), t('Widowed'), t('It\'s complicated'), t('Other'));

	call_hooks('marital_selector_min', $select);

	$o .= "<select class=\"form-control\" name=\"marital\" id=\"marital-select\" size=\"1\" >";
	foreach($select as $selection) {
		if($selection !== 'NOTRANSLATION') {
			$selected = (($selection == $current) ? ' selected="selected" ' : '');
			$o .= "<option value=\"$selection\" $selected >$selection</option>";
		}
	}
	$o .= '</select>';
	return $o;
}	

