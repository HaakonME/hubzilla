<?php
namespace Zotlabs\Module;





class Linkinfo extends \Zotlabs\Web\Controller {

	function get() {
	
		logger('linkinfo: ' . print_r($_REQUEST,true));
	
		$text = null;
		$str_tags = '';
	
	
		$br = "\n";
	
		if(x($_GET,'binurl'))
			$url = trim(hex2bin($_GET['binurl']));
		else
			$url = trim($_GET['url']);
	
		$url = strip_zids($url);
	
		if((substr($url,0,1) != '/') && (substr($url,0,4) != 'http'))
			$url = 'http://' . $url;
	
	
		if($_GET['title'])
			$title = strip_tags(trim($_GET['title']));
	
		if($_GET['description'])
			$text = strip_tags(trim($_GET['description']));
	
		if($_GET['tags']) {
			$arr_tags = str_getcsv($_GET['tags']);
			if(count($arr_tags)) {
				array_walk($arr_tags,'self::arr_add_hashes');
				$str_tags = $br . implode(' ',$arr_tags) . $br;
			}
		}
	
		logger('linkinfo: ' . $url);
	
		$result = z_fetch_url($url,false,0,array('novalidate' => true, 'nobody' => true));
		if($result['success']) {
			$hdrs=array();
			$h = explode("\n",$result['header']);
			foreach ($h as $l) {
				list($k,$v) = array_map("trim", explode(":", trim($l), 2));
				$hdrs[$k] = $v;
			}
			if (array_key_exists('Content-Type', $hdrs))
				$type = $hdrs['Content-Type'];
			if($type) {
				$zrl = is_matrix_url($url);
				if(stripos($type,'image/') !== false) {
					if($zrl)
						echo $br . '[zmg]' . $url . '[/zmg]' . $br;
					else
						echo $br . '[img]' . $url . '[/img]' . $br;
					killme();
				}
				if(stripos($type,'video/') !== false) {
					if($zrl)
						echo $br . '[zvideo]' . $url . '[/zvideo]' . $br;
					else
						echo $br . '[video]' . $url . '[/video]' . $br;
					killme();
				}
				if(stripos($type,'audio/') !== false) {
					if($zrl)
						echo $br . '[zaudio]' . $url . '[/zaudio]' . $br;
					else
						echo $br . '[audio]' . $url . '[/audio]' . $br;
					killme();
				}
			}
		}
	
		$template = $br . '#^[url=%s]%s[/url]%s' . $br;
	
		$arr = array('url' => $url, 'text' => '');
	
		call_hooks('parse_link', $arr);
	
		if(strlen($arr['text'])) {
			echo $arr['text'];
			killme();
		}
	
		$x = oembed_process($url);
		if($x) {
			echo $x;
			killme();
		}
	
		if($url && $title && $text) {
	
			$text = $br . '[quote]' . trim($text) . '[/quote]' . $br;
	
			$title = str_replace(array("\r","\n"),array('',''),$title);
	
			$result = sprintf($template,$url,($title) ? $title : $url,$text) . $str_tags;
	
			logger('linkinfo (unparsed): returns: ' . $result);
	
			echo $result;
			killme();
		}
	
		$siteinfo = self::parseurl_getsiteinfo($url);
	
		// If this is a Red site, use zrl rather than url so they get zids sent to them by default
	
		if( x($siteinfo,'generator') && (strpos($siteinfo['generator'], \Zotlabs\Lib\System::get_platform_name() . ' ') === 0))
			$template = str_replace('url','zrl',$template);
	
		if($siteinfo["title"] == "") {
			echo sprintf($template,$url,$url,'') . $str_tags;
			killme();
		} else {
			$text = $siteinfo["text"];
			$title = $siteinfo["title"];
		}
	
		$image = "";
	
		if(sizeof($siteinfo["images"]) > 0){
			/* Execute below code only if image is present in siteinfo */
	
			$total_images = 0;
			$max_images = get_config('system','max_bookmark_images');
			if($max_images === false)
				$max_images = 2;
			else
				$max_images = intval($max_images);
	
			foreach ($siteinfo["images"] as $imagedata) {
	                        if ($url) {
	                            $image .= sprintf('[url=%s]', $url);
	                        }
				$image .= '[img='.$imagedata["width"].'x'.$imagedata["height"].']'.$imagedata["src"].'[/img]';
	                        if ($url) {
	                            $image .= '[/url]';
	                        }
	                        $image .= "\n";
				$total_images ++;
				if($max_images && $max_images >= $total_images)
					break;
	        }
		}
	
		if(strlen($text)) {
			$text = $br.'[quote]'.trim($text).'[/quote]'.$br ;
		}
	
		if($image) {
			$text = $br.$br.$image.$text;
		}
		$title = str_replace(array("\r","\n"),array('',''),$title);
	
		$result = sprintf($template,$url,($title) ? $title : $url,$text) . $str_tags;
	
		logger('linkinfo: returns: ' . $result, LOGGER_DEBUG);
	
		echo trim($result);
		killme();
	
	}
	
	
	public static function deletexnode(&$doc, $node) {
		$xpath = new \DomXPath($doc);
		$list = $xpath->query("//".$node);
		foreach ($list as $child)
			$child->parentNode->removeChild($child);
	}
	
	public static function completeurl($url, $scheme) {
	        $urlarr = parse_url($url);
	
	        if (isset($urlarr["scheme"]))
	                return($url);
	
	        $schemearr = parse_url($scheme);
	
	        $complete = $schemearr["scheme"]."://".$schemearr["host"];
	
	        if ($schemearr["port"] != "")
	                $complete .= ":".$schemearr["port"];
	
			if(strpos($urlarr['path'],'/') !== 0)
				$complete .= '/';
	
	        $complete .= $urlarr["path"];
	
	        if ($urlarr["query"] != "")
	                $complete .= "?".$urlarr["query"];
	
	        if ($urlarr["fragment"] != "")
	                $complete .= "#".$urlarr["fragment"];
	
	        return($complete);
	}
	
	
	public static function parseurl_getsiteinfo($url) {
		$siteinfo = array();
	
	
		$result = z_fetch_url($url,false,0,array('novalidate' => true));
		if(! $result['success'])
			return $siteinfo;
	
		$header = $result['header'];
		$body   = $result['body'];
	
		$body   = mb_convert_encoding($body, 'UTF-8', 'UTF-8');
		$body   = mb_convert_encoding($body, 'HTML-ENTITIES', "UTF-8");
	
		$doc    = new \DOMDocument();
		@$doc->loadHTML($body);
	
		self::deletexnode($doc, 'style');
		self::deletexnode($doc, 'script');
		self::deletexnode($doc, 'option');
		self::deletexnode($doc, 'h1');
		self::deletexnode($doc, 'h2');
		self::deletexnode($doc, 'h3');
		self::deletexnode($doc, 'h4');
		self::deletexnode($doc, 'h5');
		self::deletexnode($doc, 'h6');
		self::deletexnode($doc, 'ol');
		self::deletexnode($doc, 'ul');
	
		$xpath = new \DomXPath($doc);
	
		//$list = $xpath->query("head/title");
		$list = $xpath->query("//title");
		foreach ($list as $node)
			$siteinfo["title"] =  html_entity_decode($node->nodeValue, ENT_QUOTES, "UTF-8");
	
		//$list = $xpath->query("head/meta[@name]");
		$list = $xpath->query("//meta[@name]");
		foreach ($list as $node) {
			$attr = array();
			if ($node->attributes->length)
	                        foreach ($node->attributes as $attribute)
	                                $attr[$attribute->name] = $attribute->value;
	
			$attr["content"] = html_entity_decode($attr["content"], ENT_QUOTES, "UTF-8");
	
			switch (strtolower($attr["name"])) {
				case 'generator':
					$siteinfo['generator'] = $attr['content'];
					break;
				case "fulltitle":
					$siteinfo["title"] = $attr["content"];
					break;
				case "description":
					$siteinfo["text"] = $attr["content"];
					break;
				case "dc.title":
					$siteinfo["title"] = $attr["content"];
					break;
				case "dc.description":
					$siteinfo["text"] = $attr["content"];
					break;
			}
		}
	
		//$list = $xpath->query("head/meta[@property]");
		$list = $xpath->query("//meta[@property]");
		foreach ($list as $node) {
			$attr = array();
			if ($node->attributes->length)
	                        foreach ($node->attributes as $attribute)
	                                $attr[$attribute->name] = $attribute->value;
	
			$attr["content"] = html_entity_decode($attr["content"], ENT_QUOTES, "UTF-8");
	
			switch (strtolower($attr["property"])) {
				case "og:image":
					$siteinfo["image"] = $attr["content"];
					break;
				case "og:title":
					$siteinfo["title"] = $attr["content"];
					break;
				case "og:description":
					$siteinfo["text"] = $attr["content"];
					break;
			}
		}
	
		if ($siteinfo["image"] == "") {
	            $list = $xpath->query("//img[@src]");
	            foreach ($list as $node) {
	                $attr = array();
	                if ($node->attributes->length)
	                    foreach ($node->attributes as $attribute)
	                        $attr[$attribute->name] = $attribute->value;
	
				$src = self::completeurl($attr["src"], $url);
				$photodata = @getimagesize($src);
	
				if (($photodata) && ($photodata[0] > 150) and ($photodata[1] > 150)) {
					if ($photodata[0] > 300) {
						$photodata[1] = round($photodata[1] * (300 / $photodata[0]));
						$photodata[0] = 300;
					}
					if ($photodata[1] > 300) {
						$photodata[0] = round($photodata[0] * (300 / $photodata[1]));
						$photodata[1] = 300;
					}
					$siteinfo["images"][] = array("src"=>$src,
									"width"=>$photodata[0],
									"height"=>$photodata[1]);
				}
	
	 		}
	    } else {
			$src = self::completeurl($siteinfo["image"], $url);
	
			unset($siteinfo["image"]);
	
			$photodata = @getimagesize($src);
	
			if (($photodata) && ($photodata[0] > 10) and ($photodata[1] > 10))
				$siteinfo["images"][] = array("src"=>$src,
								"width"=>$photodata[0],
								"height"=>$photodata[1]);
		}
	
		if ($siteinfo["text"] == "") {
			$text = "";
	
			$list = $xpath->query("//div[@class='article']");
			foreach ($list as $node)
				if (strlen($node->nodeValue) > 40)
					$text .= " ".trim($node->nodeValue);
	
			if ($text == "") {
				$list = $xpath->query("//div[@class='content']");
				foreach ($list as $node)
					if (strlen($node->nodeValue) > 40)
						$text .= " ".trim($node->nodeValue);
			}
	
			// If none text was found then take the paragraph content
			if ($text == "") {
				$list = $xpath->query("//p");
				foreach ($list as $node)
					if (strlen($node->nodeValue) > 40)
						$text .= " ".trim($node->nodeValue);
			}
	
			if ($text != "") {
				$text = trim(str_replace(array("\n", "\r"), array(" ", " "), $text));
	
				while (strpos($text, "  "))
					$text = trim(str_replace("  ", " ", $text));
	
				$siteinfo["text"] = html_entity_decode(substr($text,0,350), ENT_QUOTES, "UTF-8").'...';
			}
		}
	
		return($siteinfo);
	}
	

	private static function arr_add_hashes(&$item,$k) {
		$item = '#' . $item;
	}




}
