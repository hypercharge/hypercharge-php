<?php
require_once 'test_helper.php';

class AllTests extends TestSuite {
	function __construct($label=false) {
		$this->label = $label;
		$this->collect(__DIR__.'/unit', new SimpleCollector());
	}
}
