<?php

namespace Zotlabs\Widget;

class Notes {

	function widget($arr) {
		if(! local_channel())
			return '';
		if(! feature_enabled(local_channel(),'private_notes'))
			return '';

		$text = get_pconfig(local_channel(),'notes','text');

		$o = replace_macros(get_markup_template('notes.tpl'), array(
			'$banner' => t('Notes'),
			'$text' => $text,
			'$save' => t('Save'),
		));

		return $o;
	}
}
