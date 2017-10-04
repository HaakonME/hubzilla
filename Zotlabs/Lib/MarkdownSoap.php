<?php

namespace Zotlabs\Lib;

/**
 * MarkdownSoap
 * Purify Markdown for storage
 *   $x = new MarkdownSoap($string_to_be_cleansed);
 *   $text = $x->clean();
 *
 * What this does:
 * 1. extracts code blocks and privately escapes them from processing
 * 2. Run html purifier on the content
 * 3. put back the code blocks
 * 4. run htmlspecialchars on the entire content for safe storage
 *
 * At render time: 
 *    $markdown = \Zotlabs\Lib\MarkdownSoap::unescape($text);
 *    $html = \Michelf\MarkdownExtra::DefaultTransform($markdown);
 */



class MarkdownSoap {

	private $token;

	private $str;

	function __construct($s) {
		$this->str  = $s;
		$this->token = random_string(20);
	}


	function clean() {

		$x = $this->extract_code($this->str);

		$x = $this->purify($x);

		$x = $this->putback_code($x);		

		$x = $this->escape($x);
		
		return $x;
	}

	function extract_code($s) {
			
		$text = preg_replace_callback('{
					(?:\n\n|\A\n?)
					(	            # $1 = the code block -- one or more lines, starting with a space/tab
					  (?>
						[ ]{'.'4'.'}  # Lines must start with a tab or a tab-width of spaces
						.*\n+
					  )+
					)
					((?=^[ ]{0,'.'4'.'}\S)|\Z)	# Lookahead for non-space at line-start, or end of doc
				}xm',
				[ $this , 'encode_code' ], $s);

		return $text;
	}
	
	function encode_code($matches) {
		return $this->token . ';' . base64_encode($matches[0]) . ';' ;
	}

	function decode_code($matches) {
		return base64_decode($matches[1]);
	}

	function putback_code($s) {
		$text = preg_replace_callback('{' . $this->token . '\;(.*?)\;}xm',[ $this, 'decode_code' ], $s);
		return $text;
	}

	function purify($s) {
		$s = $this->protect_autolinks($s);
		$s = purify_html($s);
		$s = $this->unprotect_autolinks($s);
		return $s;
	}

	function protect_autolinks($s) {
		$s = preg_replace('/\<(https?\:\/\/)(.*?)\>/','[$1$2]($1$2)',$s);
		return $s;
	}

	function unprotect_autolinks($s) {
		return $s;

	}

	function escape($s) {
		return htmlspecialchars($s,ENT_QUOTES,'UTF-8',false);
	}

	static public function unescape($s) {
		return htmlspecialchars_decode($s,ENT_QUOTES);
	}
}
