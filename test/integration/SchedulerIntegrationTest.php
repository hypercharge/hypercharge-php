<?php
namespace Hypercharge;
require_once dirname(__DIR__).'/test_helper.php';

if(getenv('DEBUG') == '1') Config::setLogger(new StdoutLogger());

class SchedulerIntegrationTest extends HyperchargeTestCase {

	function setUp() {
		$this->credentials('sandbox'); //'development' 'sandbox2'
		// echo "\n";
		// print_r($this->credentials);
		Config::setIdSeparator('---');
	}

	function testEach() {

		$n = 0;
		$_this = $this;
		Scheduler::each(array('page'=>1, 'per_page'=>10), function($schedule) use($_this, &$n) {
			$n++;
			$_this->assertIsA($schedule, 'Hypercharge\Scheduler');
			$_this->assertEqual('DateRecurringSchedule', $schedule->type);
		});
		$this->assertEqual($n, 10);
	}

}
