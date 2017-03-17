<?php

namespace Zotlabs\Widget;

require_once('include/contact_widgets.php');

class Filer {

	function widget($arr) {
		if(! local_channel())
			return '';


		$selected = ((x($_REQUEST,'file')) ? $_REQUEST['file'] : '');

		$terms = array();
		$r = q("select distinct term from term where uid = %d and ttype = %d order by term asc",
			intval(local_channel()),
			intval(TERM_FILE)
		);
		if(! $r)
			return;

		foreach($r as $rr)
			$terms[] = array('name' => $rr['term'], 'selected' => (($selected == $rr['term']) ? 'selected' : ''));

		return replace_macros(get_markup_template('fileas_widget.tpl'),array(
			'$title' => t('Saved Folders'),
			'$desc' => '',
			'$sel_all' => (($selected == '') ? 'selected' : ''),
			'$all' => t('Everything'),
			'$terms' => $terms,
			'$base' => z_root() . '/' . \App::$cmd
		));
	}
}
