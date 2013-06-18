<?php
require_once 'test_helper.php';

class AllTests extends TestSuite {
	function __construct() {
		$this->TestSuite('Remote tests');
		$this->collect(__DIR__.'/integration', new SimpleCollector());
	}
}
