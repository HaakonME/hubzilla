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

	// Escaping the hash tags
	$s = preg_replace('/\#([^\s\#])/','&#35;$1',$s);

	$s = MarkdownExtra::defaultTransform($s);

	if($options && $options['preserve_lf']) {
		$s = str_replace(["\r","\n"],["",'<br>'],$s);
	}
	else {
		$s = str_replace("\r","",$s);
	}

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



function bb_to_markdown_share($match) {

	$matches = array();
	$attributes = $match[1];

	$author = "";
	preg_match("/author='(.*?)'/ism", $attributes, $matches);
	if ($matches[1] != "")
		$author = urldecode($matches[1]);

	$link = "";
	preg_match("/link='(.*?)'/ism", $attributes, $matches);
	if ($matches[1] != "")
		$link = $matches[1];

	$avatar = "";
	preg_match("/avatar='(.*?)'/ism", $attributes, $matches);
	if ($matches[1] != "")
		$avatar = $matches[1];

	$profile = "";
	preg_match("/profile='(.*?)'/ism", $attributes, $matches);
	if ($matches[1] != "")
		$profile = $matches[1];

	$posted = "";
	preg_match("/posted='(.*?)'/ism", $attributes, $matches);
	if ($matches[1] != "")
		$posted = $matches[1];

	// message_id is never used, do we still need it?
	$message_id = "";
	preg_match("/message_id='(.*?)'/ism", $attributes, $matches);
	if ($matches[1] != "")
		$message_id = $matches[1];

	if(! $message_id) {
		preg_match("/guid='(.*?)'/ism", $attributes, $matches);
		if ($matches[1] != "")
			$message_id = $matches[1];
	}


	$reldate = datetime_convert('UTC', date_default_timezone_get(), $posted, 'r');

	$headline = '';

	if ($avatar != "")
		$headline .= '[url=' . zid($profile) . '][img]' . $avatar . '[/img][/url]';

	// Bob Smith wrote the following post 2 hours ago

	$fmt = sprintf( t('%1$s wrote the following %2$s %3$s'),
		'[url=' . zid($profile) . ']' . $author . '[/url]',
		'[url=' . zid($link) . ']' . t('post') . '[/url]',
		$reldate
	);

	$headline .= $fmt . "\n\n";

	$text = $headline . trim($match[2]);

	return $text;
}



function bb_to_markdown($Text, $options = []) {

	/*
	 * Transform #tags, strip off the [url] and replace spaces with underscore
	 */

	$Text = preg_replace_callback('/#\[([zu])rl\=(.*?)\](.*?)\[\/[(zu)]rl\]/i', 
		create_function('$match', 'return \'#\'. str_replace(\' \', \'_\', $match[3]);'), $Text);


	$Text = preg_replace('/#\^\[([zu])rl\=(.*?)\](.*?)\[\/([zu])rl\]/i', '[$1rl=$2]$3[/$4rl]', $Text);

	// Converting images with size parameters to simple images. Markdown doesn't know it.
	$Text = preg_replace("/\[img\=([0-9]*)x([0-9]*)\](.*?)\[\/img\]/ism", '[img]$3[/img]', $Text);

	$Text = preg_replace_callback("/\[share(.*?)\](.*?)\[\/share\]/ism", 'bb_to_markdown_share', $Text);

	$x = [ 'bbcode' => $Text, 'options' => $options ];

	call_hooks('bb_to_markdown_bb',$x);

	$Text = $x['bbcode'];

	// Convert it to HTML - don't try oembed
	$Text = bbcode($Text, $preserve_nl, false);

	// Markdownify does not preserve previously escaped html entities such as <> and &.
	$Text = str_replace(array('&lt;','&gt;','&amp;'),array('&_lt_;','&_gt_;','&_amp_;'),$Text);

	// Now convert HTML to Markdown

	$Text = html2markdown($Text);

	// It also adds backslashes to our attempt at getting around the html entity preservation for some weird reason.

	$Text = str_replace(array('&\\_lt\\_;','&\\_gt\\_;','&\\_amp\\_;'),array('&lt;','&gt;','&amp;'),$Text);

	// If the text going into bbcode() has a plain URL in it, i.e.
	// with no [url] tags around it, it will come out of parseString()
	// looking like: <http://url.com>, which gets removed by strip_tags().
	// So take off the angle brackets of any such URL
	$Text = preg_replace("/<http(.*?)>/is", "http$1", $Text);

	// Remove empty zrl links
	$Text = preg_replace("/\[zrl\=\].*?\[\/zrl\]/is", "", $Text);

	$Text = trim($Text);

	call_hooks('bb_to_markdown', $Text);

	return $Text;

}



/**
 * @brief Convert a HTML text into Markdown.
 *
 * This function uses the library league/html-to-markdown for this task.
 *
 * If the HTML text can not get parsed it will return an empty string.
 *
 * @see HTMLToMarkdown
 *
 * @param string $html The HTML code to convert
 * @return string Markdown representation of the given HTML text, empty on error
 */
function html2markdown($html) {
	$markdown = '';
	$converter = new HtmlConverter();

	try {
		$markdown = $converter->convert($html);
	} catch (InvalidArgumentException $e) {
		logger("Invalid HTML. HTMLToMarkdown library threw an exception.");
	}

	// The old html 2 markdown library "pixel418/markdownify": "^2.2",
	//$md = new HtmlConverter();
	//$markdown = $md->convert($Text);
	return $markdown;
}
