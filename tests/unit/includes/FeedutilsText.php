<?php

namespace Zotlabs\Tests\Unit\includes;

use Zotlabs\Tests\Unit\UnitTestCase;

/**
 * @brief Unit Test case for include/feedutils.php file.
 */
class FeedutilsTest extends UnitTestCase {

	public function test_normalise_id() {
		$this->assertEquals('id', normalise_id('id'));
		$this->assertEquals('id', normalise_id('X-ZOT:id'));
		$this->assertEquals('id id2', normalise_id('X-ZOT:id X-ZOT:id2'));
		$this->assertEmpty(normalise_id(''));
	}

	public function test_encode_rel_links() {
		// invalid params return empty array
		$this->assertEquals([], encode_rel_links('string'));
		$this->assertEquals([], encode_rel_links([]));

		$b = ['attribs' => ['' => [
				'rel' => 'rel_value',
				'type' => 'type_value',
				'href' => 'href_value'
		]]];
		$blink1 = ['link1' => $b];
		$bresult[] = $b['attribs'][''];
		$this->assertEquals($bresult, encode_rel_links($blink1));
	}

/*	public function test_encode_rel_links_fail() {
		$a = [ 'key' => 'value'];
		$this->assertFalse(encode_rel_links($a));
		//Illegal string offset 'attribs'
	}*/

	public function test_atom_author() {
		$this->assertEquals('', atom_author('', 'nick', 'name', 'uri', 72, 72, 'png', 'photourl'));

		$a = '<tag>
  <id>uri</id>
  <name>nick</name>
  <uri>uri</uri>
  <link rel="photo"  type="png" media:width="72" media:height="72" href="http://photourl" />
  <link rel="avatar" type="png" media:width="72" media:height="72" href="http://photourl" />
  <poco:preferredUsername>nick</poco:preferredUsername>
  <poco:displayName>name<poco:displayName>
</tag>';

		$this->assertXmlStringEqualsXmlString($a, atom_author('tag', 'nick', 'name', 'uri', 72, 72, 'png', 'http://photourl'));
	}
}
