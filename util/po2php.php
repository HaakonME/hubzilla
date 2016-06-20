<?php


function po2php_run($argc,$argv) {

	if ($argc!=2) {
		print "Usage: ".$argv[0]." <file.po>\n\n";
		return;
	}
	
	$pofile = $argv[1];
	$outfile = dirname($pofile)."/hstrings.php";

	if(strstr($outfile,'util'))
		$lang = 'en';
	else
		$lang = str_replace('-','_',basename(dirname($pofile)));



	if (!file_exists($pofile)){
		print "Unable to find '$pofile'\n";
		return;
	}
	
	print "Out to '$outfile'\n";
	
	$out="<?php\n\n";
	
	$infile = file($pofile);
	$k="";
	$v="";
	$ctx="";
	$arr = False;
	$ink = False;
	$inv = False;
	$escape_s_exp = '|[^\\\\]\$[a-z]|';

	function escape_s($match){
		return str_replace('$','\$',$match[0]);
	}

	foreach ($infile as $l) {
		$l = str_replace(array('$projectname','$Projectname'),array('\$projectname','\$Projectname'),$l);
		$len = strlen($l);
		if ($l[0]=="#") $l="";
		if (substr($l,0,15)=='"Plural-Forms: '){
			$match=Array();
			preg_match("|nplurals=([0-9]*); *plural=(.*)[;\\\\]|", $l, $match);
			$cond = str_replace('n','$n',$match[2]);
			$out .= 'if(! function_exists("' . 'string_plural_select_' . $lang .'")) {' . "\n";
			$out .= 'function string_plural_select_' . $lang . '($n){'."\n";
			$out .= '	return '.$cond.';'."\n";
			$out .= '}}'."\n";
		}
		
		if ($k!="" && substr($l,0,7)=="msgstr "){
			if ($ink) { $ink = False; $out .= 'App::$strings["'.$k.'"] = '; }
			if ($inv) {	$inv = False; $out .= '"'.$v.'"'; }
			
			$v = substr($l,8,$len-10);
			$v = preg_replace_callback($escape_s_exp,'escape_s',$v);
			$inv = True;
			//$out .= $v;
		}
		if ($k!="" && substr($l,0,7)=="msgstr["){
			if ($ink) { 
				$ink = False; 
				$out .= 'App::$strings["'.$k.'"] = '; 
			}
			if ($inv) {	
				$inv = False; 
				$out .= '"'.$v.'"';
			}			
			if (!$arr) {
				$arr=True;
				$out .= "array(\n";
			}
			$match=Array();
			preg_match("|\[([0-9]*)\] (.*)|", $l, $match);
			$out .= "\t".
				preg_replace_callback($escape_s_exp,'escape_s',$match[1])
				." => "
				.preg_replace_callback($escape_s_exp,'escape_s',$match[2]) .",\n";
		}
	
		if (substr($l,0,6)=="msgid_") { 
			$ink = False; 
			$out .= 'App::$strings["'.$k.'"] = '; 
		}


		if ($ink) {
			$k .= trim_message($l);
			$k = preg_replace_callback($escape_s_exp,'escape_s',$k);
			//$out .= 'App::$strings['.$k.'] = ';
		}
		
		if (substr($l,0,6)=="msgid "){
			if ($inv) {	$inv = False; $out .= '"'.$v.'"'; }
			if ($k!="") $out .= $arr?");\n":";\n";
			$arr=False;
			$k = str_replace("msgid ","",$l);
			$k = trim_message($k);
			$k = $ctx.$k;
		//	echo $ctx ? $ctx."\nX\n":"";
			$k = preg_replace_callback($escape_s_exp,'escape_s',$k);
			$ctx = "";
			$ink = True;
		}
		
		if ($inv && substr($l,0,6)!="msgstr" && substr($l,0,7)!="msgctxt") {
			$v .= trim_message($l);
			$v = preg_replace_callback($escape_s_exp,'escape_s',$v);
			//$out .= 'App::$strings['.$k.'] = ';
		}

		if (substr($l,0,7)=="msgctxt") {
			$ctx = str_replace("msgctxt ","",$l);
			$ctx = trim_message($ctx);
			$ctx = "__ctx:".$ctx."__ ";
			$ctx = preg_replace_callback($escape_s_exp,'escape_s',$ctx);
		}

	}

	if ($inv) {	$inv = False; $out .= '"'.$v.'"'; }
	if ($k!="") $out .= $arr?");\n":";\n";
	
	file_put_contents($outfile, $out);
	
}

function trim_message($str) {
	// Almost same as trim("\"\r\n") except that escaped quotes are preserved
	$str = trim($str, "\r\n");
	$str = ltrim($str, "\"");
	$str = preg_replace('/(?<!\\\)"+$/', '', $str);
	return $str;
}

if (array_search(__file__,get_included_files())===0){
  po2php_run($argc,$argv);
}
