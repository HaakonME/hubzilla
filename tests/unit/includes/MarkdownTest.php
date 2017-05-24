<?php
/*
 * Copyright (c) 2017 Hubzilla
*
* Permission is hereby granted, free of charge, to any person obtaining a copy
* of this software and associated documentation files (the "Software"), to deal
* in the Software without restriction, including without limitation the rights
* to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
* copies of the Software, and to permit persons to whom the Software is
* furnished to do so, subject to the following conditions:
*
* The above copyright notice and this permission notice shall be included in
* all copies or substantial portions of the Software.
*
* THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
* IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
* FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
* AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
* LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
* OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
* SOFTWARE.
*/

namespace Zotlabs\Tests\Unit\includes;

use Zotlabs\Tests\Unit\UnitTestCase;
use phpmock\phpunit\PHPMock;

require_once 'include/markdown.php';

/**
 * @brief Unit Test case for markdown functions.
 */
class MarkdownTest extends UnitTestCase {
	use PHPMock;

	/**
	 * @covers ::html2markdown
	 * @dataProvider html2markdownProvider
	 */
	public function testHtml2markdown($html, $markdown) {
		$this->assertEquals($markdown, html2markdown($html));
	}

	public function html2markdownProvider() {
		return [
				'empty text' => [
						'',
						''
				],
				'space and nbsp only' => [
						' &nbsp;',
						''
				],
				'strong, b, em, i, bib' => [
						'<strong>strong</strong> <b>bold</b> <em>em</em> <i>italic</i>  <b>bo<i>italic</i>ld</b>',
						'**strong** **bold** _em_ _italic_ **bo_italic_ld**'
				],
				'empty tags' => [
						'text1 <b></b> text2 <i></i>',
						'text1  text2'
				],
				'HTML entities, lt does not work' => [
						'& gt > lt <',
						'& gt > lt'
				],
				'escaped HTML entities' => [
						'&amp; lt &lt; gt &gt;',
						'& lt < gt >'
				],
				'our escaped HTML entities' => [
						'&_lt_; &_gt_; &_amp_;',
						'&\_lt\_; &\_gt\_; &\_amp\_;'
				],
				'linebreak' => [
						"line1<br>line2\nline3",
						"line1  \nline2 line3"
				],
				'headlines' => [
						'<h1>header1</h1><h3>Header 3</h3>',
						"header1\n=======\n\n### Header 3"
				],
				'unordered list' => [
						'<ul><li>Item 1</li><li>Item 2</li><li>Item <b>3</b></li></ul>',
						"- Item 1\n- Item 2\n- Item **3**"
				],
				'ordered list' => [
						'<ol><li>Item 1</li><li>Item 2</li><li>Item <b>3</b></li></ol>',
						"1. Item 1\n2. Item 2\n3. Item **3**"
				],
				'nested lists' => [
						'<ul><li>Item 1<ol><li>Item 1a</li><li>Item <b>1b</b></ol></li><li>Item 2</li></ul>',
						"- Item 1\n  1. Item 1a\n  2. Item **1b**\n- Item 2"
				],
				'img' => [
						'<img src="/path/to/img.png" alt="alt text" title="title text">',
						'![alt text](/path/to/img.png "title text")'
				],
				'link' => [
						'<a href="http://hubzilla.org" title="Hubzilla">link</a>',
						'[link](http://hubzilla.org "Hubzilla")'
				],
				'img link' => [
						'<a href="http://hubzilla.org" title="Hubzilla"><img src="/img/hubzilla.png" alt="alt img text" title="img title"></a>',
						'[![alt img text](/img/hubzilla.png "img title")](http://hubzilla.org "Hubzilla")'
				],
				'script' => [
						"<script>alert('test');</script>",
						"<script>alert('test');</script>"
				],
				'blockquote, issue #793' => [
						'<blockquote>something</blockquote>blah',
						"> something\n\nblah"
				],
				'code' => [
						'<code>&lt;p&gt;HTML text&lt;/p&gt;</code>',
						'`<p>HTML text</p>`'
				],
				'pre' => [
						'<pre>   line with  spaces   </pre>',
						'`   line with  spaces   `'
				],
				'div p' => [
						'<div>div</div><div><p>p</p></div>',
						"<div>div</div><div>p\n\n</div>"
				]
		];
	}

	/*public function testHtml2markdownException() {
		//$this->expectException(\InvalidArgumentException::class);
		// need to stub logger() for this to work
		$this->assertEquals('', html2markdown('<<invalid'));
	}*/

/*	public function testBB2diasporaMardown() {
		//stub bbcode() and return our HTML, we just need to test the HTML2Markdown library.
		$html1 = 'test<b>bold</b><br><i>i</i><ul><li>li1</li><li>li2</li></ul><br>';
		$bb1 = 'test';

		// php-mock can not mock global functions which is called by a global function.
		// If the calling function is in a namespace it does work.
		$bbc = $this->getFunctionMock(__NAMESPACE__, "bbcode");
		$bbc->expects($this->once())->willReturn('test<b>bold</b><br><i>i</i><ul><li>li1</li><li>li2</li></ul><br>');

		$this->assertEquals($bb1, bb2diaspora($html1));
	}
*/
}