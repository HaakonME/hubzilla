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

namespace Zotlabs\Tests\Unit\Access;

use Zotlabs\Tests\Unit\UnitTestCase;
use Zotlabs\Access\AccessList;

/**
 * @brief Unit Test case for AccessList class.
 *
 * @covers Zotlabs\Access\AccessList
 */
class AccessListTest extends UnitTestCase {

	/**
	 * @brief Expected result for most tests.
	 * @var array
	 */
	protected $expectedResult = [
			'allow_cid' => '<acid><acid2>',
			'allow_gid' => '<agid>',
			'deny_cid'  => '',
			'deny_gid'  => '<dgid><dgid2>'
	];



	public function testConstructor() {
		$channel = [
				'channel_allow_cid' => '<acid><acid2>',
				'channel_allow_gid' => '<agid>',
				'channel_deny_cid' => '',
				'channel_deny_gid' => '<dgid><dgid2>'
		];

		$accessList = new AccessList($channel);

		$this->assertEquals($this->expectedResult, $accessList->get());
		$this->assertFalse($accessList->get_explicit());
	}

	/**
	 * @expectedException PHPUnit\Framework\Error\Error
	 */
	public function testPHPErrorOnInvalidConstructor() {
		$accessList = new AccessList('invalid');
		// Causes: "Illegal string offset 'channel_allow_cid'"
	}

	public function testDefaultGetExplicit() {
		$accessList = new AccessList([]);

		$this->assertFalse($accessList->get_explicit());
	}

	public function testDefaultGet() {
		$arr = [
				'allow_cid' => '',
				'allow_gid' => '',
				'deny_cid'  => '',
				'deny_gid'  => ''
		];

		$accessList = new AccessList([]);

		$this->assertEquals($arr, $accessList->get());
	}

	public function testSet() {
		$arr = [
				'allow_cid' => '<acid><acid2>',
				'allow_gid' => '<agid>',
				'deny_cid'  => '',
				'deny_gid'  => '<dgid><dgid2>'
		];
		$accessList = new AccessList([]);

		// default explicit true
		$accessList->set($arr);

		$this->assertEquals($this->expectedResult, $accessList->get());
		$this->assertTrue($accessList->get_explicit());

		// set explicit false
		$accessList->set($arr, false);

		$this->assertEquals($this->expectedResult, $accessList->get());
		$this->assertFalse($accessList->get_explicit());
	}

	/**
	 * @expectedException PHPUnit\Framework\Error\Error
	 */
	public function testPHPErrorOnInvalidSet() {
		$accessList = new AccessList([]);

		$accessList->set('invalid');
		// Causes: "Illegal string offset 'allow_cid'"
	}

	/**
	 * set_from_array() calls some other functions, too which are not yet unit tested.
	 * @uses ::perms2str()
	 */
	public function testSetFromArray() {
		// array
		$arraySetFromArray = [
				'contact_allow' => ['acid', 'acid2'],
				'group_allow'   => ['agid'],
				'contact_deny'  => [],
				'group_deny'    => ['dgid', 'dgid2']
		];
		$accessList = new AccessList([]);
		$accessList->set_from_array($arraySetFromArray);

		$this->assertEquals($this->expectedResult, $accessList->get());
		$this->assertTrue($accessList->get_explicit());


		// string
		$stringSetFromArray = [
				'contact_allow' => 'acid,acid2',
				'group_allow'   => 'agid',
				'contact_deny'  => '',
				'group_deny'    => 'dgid, dgid2'
		];
		$accessList2 = new AccessList([]);
		$accessList2->set_from_array($stringSetFromArray, false);

		$this->assertEquals($this->expectedResult, $accessList2->get());
		$this->assertFalse($accessList2->get_explicit());
	}

	/**
	 * @dataProvider isprivateProvider
	 */
	public function testIsPrivate($channel) {
		$accessListPublic = new AccessList([]);
		$this->assertFalse($accessListPublic->is_private());

		$accessListPrivate = new AccessList($channel);
		$this->assertTrue($accessListPrivate->is_private());
	}

	public function isprivateProvider() {
		return [
				'all set' => [[
						'channel_allow_cid' => '<acid>',
						'channel_allow_gid' => '<agid>',
						'channel_deny_cid'  => '<dcid>',
						'channel_deny_gid'  => '<dgid>'
				]],
				'only one set' => [[
						'channel_allow_cid' => '<acid>',
						'channel_allow_gid' => '',
						'channel_deny_cid'  => '',
						'channel_deny_gid'  => ''
				]],
				'acid+null' => [[
						'channel_allow_cid' => '<acid>',
						'channel_allow_gid' => null,
						'channel_deny_cid'  => '',
						'channel_deny_gid'  => ''
				]]
		];
	}

}