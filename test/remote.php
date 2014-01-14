<?php
require_once 'test_helper.php';

class RemoteTests extends TestSuite {
	function __construct() {
		$this->TestSuite('Remote tests');

		// TODO: maik, v2 Scheduler not usable atm.
		// $this->collect(__DIR__.'/integration', new SimpleCollector());
		// TODO: maik, remove the next 2 lines when v2 Scheduler was fixed.
		$this->addFile(__DIR__.'/integration/PaymentIntegrationTest.php');
		$this->addFile(__DIR__.'/integration/TransactionIntegrationTest.php');
	}
}
