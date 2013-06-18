<?php
namespace Hypercharge;
require_once dirname(__DIR__).'/test_helper.php';

class LoggerTest extends \UnitTestCase {

	function testFileLoggerDefaultLevel() {
		$buffer = fopen('php://memory', 'rw');
		$logger = new FileLogger($buffer);
		$logger->debug('debug message');
		$logger->info('info message');
		$logger->error('error message');
		$this->assertTrue(rewind($buffer));
		$out = fread($buffer, 5000);
		$this->assertEqual($out, "debug message\ninfo message\nerror message\n");
	}
	function testFileLoggerDebugLevel() {
		$buffer = fopen('php://memory', 'rw');
		$logger = new FileLogger($buffer, ILogger::DEBUG);
		$logger->debug('debug message');
		$logger->info('info message');
		$logger->error('error message');
		$this->assertTrue(rewind($buffer));
		$out = fread($buffer, 5000);
		$this->assertEqual($out, "debug message\ninfo message\nerror message\n");
	}
	function testFileLoggerInfoLevel() {
		$buffer = fopen('php://memory', 'rw');
		$logger = new FileLogger($buffer, ILogger::INFO);
		$logger->debug('debug message');
		$logger->info('info message');
		$logger->error('error message');
		$this->assertTrue(rewind($buffer));
		$out = fread($buffer, 5000);
		$this->assertEqual($out, "info message\nerror message\n");
	}
	function testFileLoggerErrorLevel() {
		$buffer = fopen('php://memory', 'rw');
		$logger = new FileLogger($buffer, ILogger::ERROR);
		$logger->debug('debug message');
		$logger->info('info message');
		$logger->error('error message');
		$this->assertTrue(rewind($buffer));
		$out = fread($buffer, 5000);
		$this->assertEqual($out, "error message\n");
	}

	function testNullLogger() {
		$logger = new NullLogger();
		$logger->debug('ignored debug message');
		$logger->info('ignored info message');
		$logger->error('ignored error message');
	}
}