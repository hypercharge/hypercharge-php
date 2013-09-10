<?php
require_once 'test_helper.php';

class AllTests extends TestSuite {
	function __construct() {
		$this->TestSuite('all tests');
		$this->collect(__DIR__.'/unit', new SimpleCollector());
	}
}
