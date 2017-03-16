<?php

namespace Zotlabs\Widget;

class Eventstools {

	function widget($arr) {

		if(! local_channel())
			return;

		return replace_macros(get_markup_template('events_tools_side.tpl'), array(
			'$title' => t('Events Tools'),
			'$export' => t('Export Calendar'),
			'$import' => t('Import Calendar'),
			'$submit' => t('Submit')
		));
	}
}
