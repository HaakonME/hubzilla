<?php

namespace Zotlabs\Tests\Unit\includes;

use Zotlabs\Tests\Unit\UnitTestCase;

/**
 * @brief Unit Test case for include/texter.php file.
 *
 * @author ken restivo
 */
class TextTest extends UnitTestCase {


	public function testPurifyHTML() {
		// linebreaks
		$htmlbr = 'first line<br />
	one tab preserved

empty line above';
		$this->assertEquals($htmlbr, purify_html($htmlbr));

		// HTML5 is not supported by HTMLPurifier yet, test our own configuration
		$html5elements = '<section>section<nav>navigation</nav><article>main<a href="http://hubzilla.org/">hubzilla.org</a></article></section><footer>footer</footer>';
		$this->assertEquals($html5elements, purify_html($html5elements));
		$this->assertEquals('<button>button label</button>', purify_html('<button>button label</button>'));

		// unsupported HTML5 elements
		$this->assertEquals('Your HTML parser does not support HTML5 video.', purify_html('<video controls><source src="movie.ogg" type="video/ogg">Your HTML parser does not support HTML5 video.</video>'));
		$this->assertEquals('Your HTML parser does not support HTML5 audio.', purify_html('<audio controls><source src="movie.ogg" "type="audio/ogg">Your HTML parser does not support HTML5 audio.</audio>'));

		// preserve f6 and bootstrap additional data attributes from our own configuration
		$this->assertEquals('<div data-title="title">text</div>', purify_html('<div data-title="title">text</div>'));
		$this->assertEquals('<ul data-accordion-menu=""><li>item1</li></ul>', purify_html('<ul data-accordion-menu><li>item1</li></ul>'));
		$this->assertEquals('<ul><li>item1</li></ul>', purify_html('<ul data-accordion-menu-unknown><li>item1</li></ul>'));
	}

	/**
	 * @covers ::purify_html
	 */
	public function testPurifyHTML_html() {
		$this->assertEquals('<div id="id01"><p class="class01">ids und classes</p></div>', purify_html('<div id="id01"><p class="class01">ids und classes</p></div>'));
		$this->assertEquals('<div><p>close missing tags</p></div>', purify_html('<div><p>close missing tags'));
		$this->assertEquals('<center>deprecated tag</center>', purify_html('<center>deprecated tag</center>'));
		$this->assertEquals('<span></span><div>illegal nesting</div>', purify_html('<span><div>illegal nesting</div></span>'));
		$this->assertEquals('<a href="#">link with target</a>', purify_html('<a href="#" target="_blank">link with target</a>'));
		$this->assertEquals('<a href="#">link with rel="nofollow"</a>', purify_html('<a href="#" rel="nofollow">link with rel="nofollow"</a>'));
		$this->assertEquals('a b', purify_html('a&nbsp;b'));
		$this->assertEquals('ä ä € €', purify_html('ä &auml; &euro; &#8364;'));
		$this->assertEquals('<img src="picture.png" alt="text" />', purify_html('<img src="picture.png" alt="text">'));
		$this->assertEquals('', purify_html('<iframe width="560" height="315" src="https://www.youtube.com/embed/kiNGx5oL7hk" frameborder="0" allowfullscreen></iframe>'));
	}

	/**
	 * @covers ::purify_html
	 */
	public function testPurifyHTML_js() {
		$this->assertEquals('<div></div>', purify_html('<div><img src="javascript:evil();" onload="evil();"></div>'));
		$this->assertEquals('<a href="#">link</a>', purify_html('<a href="#" onclick="alert(\'xss\')">link</a>'));
		$this->assertEquals('', purify_html('<IMG SRC="javascript:alert(&#039;XSS&#039;);">'));
		$this->assertEquals('', purify_html('<script>alter("42")</script>'));
	}

	/**
	 * @covers ::purify_html
	 */
	public function testPurifyHTML_css() {
		$this->assertEquals('<p style="color:#FF0000;background-color:#fff;">red</p>', purify_html('<p style="color:red; background-color:#fff">red</p>'));
		$this->assertEquals('<p>invalid color</p>', purify_html('<p style="color:invalid; background-color:#jjkkmm">invalid color</p>'));
		$this->assertEquals('<p>invalid style</p>', purify_html('<p style="foo:bar">invalid style</p>'));

		// test our own CSS configuration
		$this->assertEquals('<div>position removed</div>', purify_html('<div style="position:absolut">position removed</div>'));
		$this->assertEquals('<div style="position:fixed;">position preserved</div>', purify_html('<div style="position:fixed">position preserved</div>', true));
		$this->assertEquals('<div>invalid position removed</div>', purify_html('<div style="position:invalid">invalid position removed</div>', true));

		$this->assertEquals('<div>position removed</div>', purify_html('<div style="top:10px; left:3em;">position removed</div>'));
		$this->assertEquals('<div style="top:10px;left:3em;right:50%;">position preserved</div>', purify_html('<div style="top:10px; left:3em; right:50%;">position preserved</div>', true));
		$this->assertEquals('<div>invalid position removed</div>', purify_html('<div style="top:10p">invalid position removed</div>', true));
	}

}
