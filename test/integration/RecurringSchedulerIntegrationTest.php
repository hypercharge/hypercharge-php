<?php
namespace Hypercharge;
require_once dirname(__DIR__).'/test_helper.php';

if(getenv('DEBUG') == '1') Config::setLogger(new StdoutLogger());

class RecurringSchedulerIntegrationTest extends HyperchargeTestCase {

	function setUp() {
		$this->credentials('sandbox'); //'development' 'sandbox2'
		// echo "\n";
		// print_r($this->credentials);
		Config::setIdSeparator('---');
		$this->channelToken = $this->credentials->channelTokens->USD;
	}

	function testEach() {

		$n = 0;
		$_this = $this;
		RecurringScheduler::each($this->channelToken, array(), function($schedule) use($_this, &$n) {
			$_this->assertIsA($schedule, 'Hypercharge\RecurringScheduler');
		});
		$this->assertEqual($n, 100);
	}

}
