<?php
/*
 * Copyright (c) 2016-2017 Hubzilla
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

namespace Zotlabs\Tests\Unit\Lib;

use phpmock\phpunit\PHPMock;
use Zotlabs\Tests\Unit\UnitTestCase;
use Zotlabs\Lib\PermissionDescription;

/**
 * @brief Unit Test case for PermissionDescription class.
 *
 * @covers Zotlabs\Lib\PermissionDescription
 */
class PermissionDescriptionTest extends UnitTestCase {

	use PHPMock;

	public function testFromDescription() {
		$permDesc = PermissionDescription::fromDescription('test');
		$permDesc2 = PermissionDescription::fromDescription('test');
		$permDesc3 = PermissionDescription::fromDescription('test2');

		$this->assertEquals($permDesc, $permDesc2);
		$this->assertNotEquals($permDesc, $permDesc3);
	}

	public function testFromStandalonePermission() {
		// Create a stub for global function t()
		$t = $this->getFunctionMock('Zotlabs\Lib', 't');
		$t->expects($this->atLeastOnce())->willReturnCallback(
			function ($string) {
				return $string;
			}
		);
		// Create a mock for global function logger()
		$this->getFunctionMock('Zotlabs\Lib', 'logger');

		$permDescUnknown = PermissionDescription::fromStandalonePermission(-1);
		$permDescSelf = PermissionDescription::fromStandalonePermission(0);

		$this->assertNull($permDescUnknown);
		$this->assertNotNull($permDescSelf);
	}

	public function testFromGlobalPermission() {
		//$permDesc = PermissionDescription::fromGlobalPermission('view_profile');

		$this->markTestIncomplete(
			'The method fromGlobalPermission() is not yet testable ...'
		);
	}

	public function testGetPermissionDescription() {
		// Create a stub for global function t()
		$t = $this->getFunctionMock('Zotlabs\Lib', 't');
		$t->expects($this->atLeastOnce())->willReturnCallback(
				function ($string) {
					return $string;
				}
		);
		// Create a mock for global function logger()
		$this->getFunctionMock('Zotlabs\Lib', 'logger');

		// Create a stub for the PermissionDescription class
		$stub = $this->createMock(PermissionDescription::class);
		$stub->method('get_permission_description')
			->will($this->returnArgument(0));

		$permDescSelf = PermissionDescription::fromStandalonePermission(0);
		$this->assertInstanceOf(PermissionDescription::class, $permDescSelf);
		$this->assertEquals($permDescSelf->get_permission_description(), 'Only me');

		$permDescPublic = PermissionDescription::fromStandalonePermission(PERMS_PUBLIC);
		$this->assertEquals($permDescPublic->get_permission_description(), 'Public');
	}
}
