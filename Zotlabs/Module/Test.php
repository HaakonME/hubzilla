<?php
namespace Zotlabs\Module;


class Test extends \Zotlabs\Web\Controller {

	function get() {
	
	$s = '<XML>
	            <post><profile>
	  <diaspora_handle>macgirvin@diasp.org</diaspora_handle>
	  <first_name>Mike</first_name>
	  <last_name>Macgirvin</last_name>
	  <image_url>https://diasp.org/uploads/images/thumb_large_d5f9b6384c91f532f280.jpg</image_url>
	  <image_url_small>https://diasp.org/uploads/images/thumb_small_d5f9b6384c91f532f280.jpg</image_url_small>
	  <image_url_medium>https://diasp.org/uploads/images/thumb_medium_d5f9b6384c91f532f280.jpg</image_url_medi\
	um>
	  <birthday>1000-05-14</birthday>
	  <gender/>
	  <bio>Creator of Friendica, Redmatrix, and Hubzilla. </bio>
	  <location>Australia</location>
	  <searchable>true</searchable>
	  <nsfw>false</nsfw>
	  <tag_string>#redmatrix #hubzilla </tag_string>
	</profile></post>
	          </XML>';
	
		$parsed_xml = xml2array($s,false,0,'tag');
	
		$o = print_r($parsed_xml,true);
		return $o;
	
	
	//	fix_system_urls('http://hz.macgirvin.com',z_root());
	
	}
	
}
