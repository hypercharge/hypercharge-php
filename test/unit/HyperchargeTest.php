<?php
namespace Hypercharge;

require_once dirname(__DIR__).'/test_helper.php';

class ConfigTest extends \UnitTestCase {

	function __construct() {
		parent::__construct();
		// pseudo-default value
		// php quirks - static class members are not reset after each test.
		Config::setIdSeparator('---');
	}

	function __destruct() {
		// pseudo-default value
		// php quirks - static class members are not reset after each test.
		Config::setIdSeparator('---');
	}

	function testGetIdSeparatorShouldReturnDefault() {
		$this->assertEqual('---', Config::getIdSeparator());
	}

	function testhasIdSeparatorShouldReturnTrueDefault() {
		$this->assertTrue(Config::hasIdSeparator());
	}

	function testhasIdSeparatorShouldReturnFalseIfSetToFalse() {
		Config::setIdSeparator(false);
		$this->assertFalse(Config::hasIdSeparator());
	}

	function testSetIdSeparatorShouldThrowErrorWithBlank() {
		$this->expectException(new Errors\ArgumentError('parameter must be a non-empty string or false'));
		Config::setIdSeparator('');
	}

	function testSetIdSeparatorShouldThrowErrorWithNull() {
		$this->expectException(new Errors\ArgumentError('parameter must be a non-empty string or false'));
		Config::setIdSeparator(null);
	}

	function testSetIdSeparatorShouldThrowErrorWithTrue() {
		$this->expectException(new Errors\ArgumentError('parameter must be a non-empty string or false'));
		Config::setIdSeparator(True);
	}

	function testSetIdSeparatorShouldThrowErrorWithInt() {
		$this->expectException(new Errors\ArgumentError('parameter must be a non-empty string or false'));
		Config::setIdSeparator(1);
	}

	function testSetIdSeparatorShouldWorkWithString() {
		Config::setIdSeparator('<<<');
		$this->assertTrue(Config::hasIdSeparator());
		$this->assertEqual('<<<', Config::getIdSeparator());
	}

}
