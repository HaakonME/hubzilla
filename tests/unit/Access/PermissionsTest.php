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
use Zotlabs\Access\Permissions;

/**
 * @brief Unit Test case for Permissions class.
 *
 * @covers Zotlabs\Access\Permissions
 */
class PermissionsTest extends UnitTestCase {

	/**
	 * @dataProvider FilledPermsProvider
	 */
	public function testFilledPerms($permarr, $expected) {
		$this->markTestIncomplete(
			'Need to mock static function Permissions::Perms() ...'
		);
		//$this->assertEquals($expected, Permissions::FilledPerms($permarr));

/*		$perms = $this->getMockBuilder(Permissions::class)
			->setMethods(['Perms'])
			->getMock();
		$perms->expects($this->once())
			->method('Perms');
		// still calls the static self::Perms()
		$perms->FilledPerms($permarr);
*/
	}
	public function FilledPermsProvider() {
		return [
				'empty' => [
						[],
						['perm1' => 0, 'perm2' => 0]
				],
				'valild' => [
						[['perm1' => 1]],
						['perm1' => 1, 'perm2' => 0]
				]
		];
	}
/*	public function testFilledPermsNull() {
		// need to mock global function btlogger();
		Permissions::FilledPerms(null);
	}
*/
	/**
	 * @dataProvider OPermsProvider
	 *
	 * @param array $permarr
	 * @param array $expected
	 */
	public function testOPerms($permarr, $expected) {
		$this->assertEquals($expected, Permissions::OPerms($permarr));
	}
	/**
	 * @return Associative array with test values for OPerms()
	 *   * \e array Array to test
	 *   * \e array Expect array
	 */
	public function OPermsProvider() {
		return [
				'empty' => [
						[],
						[]
				],
				'valid' => [
						['perm1' => 1, 'perm2' => 0],
						[['name' => 'perm1', 'value' => 1], ['name' => 'perm2', 'value' => 0]]
				],
				'null array' => [
						null,
						[]
				]
		];
	}


	/**
	 * @dataProvider permsCompareProvider
	 *
	 * @param array $p1
	 * @param array $p2
	 * @param boolean $expectedresult
	 */
	public function testPermsCompare($p1, $p2, $expectedresult) {
		$this->assertEquals($expectedresult, Permissions::PermsCompare($p1, $p2));
	}
	/**
	 * @return Associative array with test values for PermsCompare()
	 *   * \e array 1st array
	 *   * \e array 2nd array
	 *   * \e boolean expected result for the test
	 */
	public function permsCompareProvider() {
		return [
				'equal' => [
						['perm1' => 1, 'perm2' => 0],
						['perm1' => 1, 'perm2' => 0],
						true
				],
				'different values' => [
						['perm1' => 1, 'perm2' => 0],
						['perm1' => 0, 'perm2' => 1],
						false
				],
				'different order' => [
						['perm1' => 1, 'perm2' => 0],
						['perm2' => 0, 'perm1' => 1],
						true
				],
				'partial first in second' => [
						['perm1' => 1],
						['perm1' => 1, 'perm2' => 0],
						true
				],
				'partial second in first' => [
						['perm1' => 1, 'perm2' => 0],
						['perm1' => 1],
						false
				]
		];
	}
}