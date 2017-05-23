<?php
/**
 * @file include/markdown.php
 * @brief Some functions for BB conversions for Diaspora protocol.
 */

use Michelf\MarkdownExtra;
use League\HTMLToMarkdown\HtmlConverter;

require_once("include/oembed.php");
require_once("include/event.php");
require_once("include/html2bbcode.php");
require_once("include/bbcode.php");


function get_bb_tag_pos($s, $name, $occurance = 1) {

	if($occurance < 1)
		$occurance = 1;

	$start_open = -1;
	for($i = 1; $i <= $occurance; $i++) {
		if( $start_open !== false)
			$start_open = strpos($s, '[' . $name, $start_open + 1); // allow [name= type tags
	}

	if( $start_open === false)
		return false;

	$start_equal = strpos($s, '=', $start_open);
	$start_close = strpos($s, ']', $start_open);

	if( $start_close === false)
		return false;

	$start_close++;

	$end_open = strpos($s, '[/' . $name . ']', $start_close);

	if( $end_open === false)
		return false;

	$res = array( 'start' => array('open' => $start_open, 'close' => $start_close),
	              'end' => array('open' => $end_open, 'close' => $end_open + strlen('[/' . $name . ']')) );
	if( $start_equal !== false)
		$res['start']['equal'] = $start_equal + 1;

	return $res;
}

function bb_tag_preg_replace($pattern, $replace, $name, $s) {

	$string = $s;

	$occurance = 1;
	$pos = get_bb_tag_pos($string, $name, $occurance);
	while($pos !== false && $occurance < 1000) {

		$start = substr($string, 0, $pos['start']['open']);
		$subject = substr($string, $pos['start']['open'], $pos['end']['close'] - $pos['start']['open']);
		$end = substr($string, $pos['end']['close']);
		if($end === false)
			$end = '';

		$subject = preg_replace($pattern, $replace, $subject);
		$string = $start . $subject . $end;

		$occurance++;
		$pos = get_bb_tag_pos($string, $name, $occurance);
	}

	return $string;
}

function share_shield($m) {
	return str_replace($m[1],'!=+=+=!' . base64url_encode($m[1]) . '=+!=+!=',$m[0]);
}

function share_unshield($m) {
	$x = str_replace(array('!=+=+=!','=+!=+!='),array('',''),$m[1]);
	return str_replace($m[1], base64url_decode($x), $m[0]);
}

/**
 * @brief
 *
 * We don't want to support a bbcode specific markdown interpreter
 * and the markdown library we have is pretty good, but provides HTML output.
 * So we'll use that to convert to HTML, then convert the HTML back to bbcode,
 * and then clean up a few Diaspora specific constructs.
 *
 * @param string $s
 * @param boolean $use_zrl default false
 * @return string
 */

function markdown_to_bb($s, $use_zrl = false, $options = []) {


	if(is_array($s)) {
		btlogger('markdown_to_bb called with array. ' . print_r($s,true), LOGGER_NORMAL, LOG_WARNING);
		return '';
	}


	$s = str_replace("&#xD;","\r",$s);
	$s = str_replace("&#xD;\n&gt;","",$s);

	$s = html_entity_decode($s,ENT_COMPAT,'UTF-8');

	// if empty link text replace with the url
	$s = preg_replace("/\[\]\((.*?)\)/ism",'[$1]($1)',$s);

	$x = [ 'text' => $s , 'zrl' => $use_zrl, 'options' => $options ];	

	call_hooks('markdown_to_bb_init',$x);

	$s = $x['text'];

	// Escaping the hash tags - doesn't always seem to work
	// $s = preg_replace('/\#([^\s\#])/','\\#$1',$s);
	// This seems to work
	$s = preg_replace('/\#([^\s\#])/','&#35;$1',$s);

	$s = MarkdownExtra::defaultTransform($s);

	$s = str_replace("\r","",$s);

	$s = str_replace('&#35;','#',$s);

	$s = html2bbcode($s);

	// Convert everything that looks like a link to a link
	if($use_zrl) {
		$s = str_replace(array('[img','/img]'),array('[zmg','/zmg]'),$s);
		$s = preg_replace("/([^\]\=]|^)(https?\:\/\/)([a-zA-Z0-9\:\/\-\?\&\;\.\=\_\~\#\%\$\!\+\,\@\(\)]+)/ism", '$1[zrl=$2$3]$2$3[/zrl]',$s);
	}
	else {
		$s = preg_replace("/([^\]\=]|^)(https?\:\/\/)([a-zA-Z0-9\:\/\-\?\&\;\.\=\_\~\#\%\$\!\+\,\@\(\)]+)/ism", '$1[url=$2$3]$2$3[/url]',$s);
	}

	// remove duplicate adjacent code tags
	$s = preg_replace("/(\[code\])+(.*?)(\[\/code\])+/ism","[code]$2[/code]", $s);

	// Don't show link to full picture (until it is fixed)
	$s = scale_external_images($s, false);

	call_hooks('markdown_to_bb',$s);

	return $s;
}



function bb_to_markdown($Text) {

	/*
	 * Transform #tags, strip off the [url] and replace spaces with underscore
	 */

	$Text = preg_replace_callback('/#\[([zu])rl\=(\w+.*?)\](\w+.*?)\[\/[(zu)]rl\]/i', 
		create_function('$match', 'return \'#\'. str_replace(\' \', \'_\', $match[3]);'), $Text);

	$Text = preg_replace('/#\^\[([zu])rl\=(\w+.*?)\](\w+.*?)\[\/([zu])rl\]/i', '[$1rl=$2]$3[/$4rl]', $Text);

	// Converting images with size parameters to simple images. Markdown doesn't know it.
	$Text = preg_replace("/\[img\=([0-9]*)x([0-9]*)\](.*?)\[\/img\]/ism", '[img]$3[/img]', $Text);


	call_hooks('bb_to_markdown_bb',$Text);

	// Convert it to HTML - don't try oembed
	$Text = bbcode($Text, $preserve_nl, false);

	// Markdownify does not preserve previously escaped html entities such as <> and &.

	$Text = str_replace(array('&lt;','&gt;','&amp;'),array('&_lt_;','&_gt_;','&_amp_;'),$Text);

	// Now convert HTML to Markdown

	$md = new HtmlConverter();
	$Text = $md->convert($Text);

	// It also adds backslashes to our attempt at getting around the html entity preservation for some weird reason.


	$Text = str_replace(array('&\\_lt\\_;','&\\_gt\\_;','&\\_amp\\_;'),array('&lt;','&gt;','&amp;'),$Text);

	// If the text going into bbcode() has a plain URL in it, i.e.
	// with no [url] tags around it, it will come out of parseString()
	// looking like: <http://url.com>, which gets removed by strip_tags().
	// So take off the angle brackets of any such URL
	$Text = preg_replace("/<http(.*?)>/is", "http$1", $Text);

	// Remove empty zrl links
	$Text = preg_replace("/\[zrl\=\].*?\[\/zrl\]/is", "", $Text);

	// escape all unconverted tags
	$Text = escape_tags($Text);

	$Text = trim($Text);

	call_hooks('bb_to_markdown', $Text);

	return $Text;

}