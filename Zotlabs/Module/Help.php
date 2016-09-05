<?php
namespace Zotlabs\Module;


require_once('include/help.php');

/**
 * You can create local site resources in doc/Site.md and either link to doc/Home.md for the standard resources
 * or use our include mechanism to include it on your local page.
 *
 * #include doc/Home.md;
 *
 * The syntax is somewhat strict. 
 *
 */

class Help extends \Zotlabs\Web\Controller {

	function get() {

		nav_set_selected('help');
	
		if($_REQUEST['search']) {
		
			$o .= '<div id="help-content" class="generic-content-wrapper">';
			$o .= '<div class="section-title-wrapper">';
			$o .= '<h2>' . t('Documentation Search') . ' - ' . htmlspecialchars($_REQUEST['search']) . '</h2>';
			$o .= '</div>';
			$o .= '<div class="section-content-wrapper">';
	
			$r = search_doc_files($_REQUEST['search']);
			if($r) {
				$o .= '<ul class="help-searchlist">';
				foreach($r as $rr) {
					$dirname = dirname($rr['v']);
					$fname = basename($rr['v']);
					$fname = substr($fname,0,strrpos($fname,'.'));
					$path = trim(substr($dirname,4),'/');
	
					$o .= '<li><a href="help/' . (($path) ? $path . '/' : '') . $fname . '" >' . ucwords(str_replace('_',' ',notags($fname))) . '</a><br />'
						. '<b><i>' . 'help/' . (($path) ? $path . '/' : '') . $fname . '</i></b><br />' .
					'...' . str_replace('$Projectname',\Zotlabs\Lib\System::get_platform_name(),$rr['text']) . '...<br /><br /></li>';
	
				}
				$o .= '</ul>';
				$o .= '</div>';
				$o .= '</div>';
			}
			return $o;
		}
	

		$content =  get_help_content();


		return replace_macros(get_markup_template("help.tpl"), array(
			'$title' => t('$Projectname Documentation'),
			'$content' => $content
		));
	
	}
	
	
	
	
}
