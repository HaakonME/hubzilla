<?php

namespace Zotlabs\Daemon;


class Importdoc {

	static public function run($argc,$argv) {

		require_once('include/help.php');

		self::update_docs_dir('doc/*');

	}

	static public function update_docs_dir($s) {
		$f = basename($s);
		$d = dirname($s);
		if($s === 'doc/html')
			return;
		$files = glob("$d/$f");
		if($files) {
			foreach($files as $fi) {
				if($fi === 'doc/html') {
					continue;
				}
				if(is_dir($fi)) {
					self::update_docs_dir("$fi/*");
				}
				else {
					// don't update media content
					if(strpos(z_mime_content_type($fi),'text') === 0) {
						store_doc_file($fi);
					}
				}
			}
		}
	}
}


