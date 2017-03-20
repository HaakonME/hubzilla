<?php

namespace Zotlabs\Tests\Unit\includes;

use Zotlabs\Tests\Unit\UnitTestCase;

/**
 * @brief Unit Test case for texter.
 *
 * @author ken restivo
 */
class TextTest extends UnitTestCase {

	public function testGoodEmail() {
		$this->assertTrue(valid_email_regex('ken@spaz.org'));
		$this->assertTrue(valid_email_regex('ken@restivo.org'));
		$this->assertTrue(valid_email_regex('nobody@hubzilla.org'));
		$this->assertTrue(valid_email_regex('foo+nobody@hubzilla.org'));
	}

	public function testBadEmail() {
		$this->assertFalse(valid_email_regex('nobody!uses!these!any.more'));
		$this->assertFalse(valid_email_regex('foo@bar@hubzilla.org'));
	}

	public function testPurifyHTML() {
		$html = '<div id="id01"><p class="p01">text<br><b>b</b></p></div>';
		$html_expect = '<div id="id01"><p class="p01">text<br /><b>b</b></p></div>';
		$html5elements = '<section>section<nav>navigation</nav><article>main<a href="http://hubzilla.org/">hubzilla.org</a></article></section><footer>footer</footer>';
		$htmldata = '<div data-title="title">text</div>';

		$this->assertEquals($html_expect, purify_html($html));
		$this->assertEquals($html5elements, purify_html($html5elements));
		$this->assertEquals($htmldata, purify_html($htmldata));
	}
}

