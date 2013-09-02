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

		$this->channel_token = $this->credentials->channelTokens->EUR;
	}

	function transactionFixture($fixtureName) {
		// users will likely use arrays
		$trxData = json_decode(JsonSchemaFixture::request($fixtureName), true);
		$trxData = $trxData['payment_transaction'];

		$trxData['currency'] = 'EUR';

		// expiration year
		$date = new \DateTime('now', new \DateTimeZone('UTC'));
		$date->add(new \DateInterval('P3Y'));
		$trxData['expiration_year']	= $date->format('Y');

		return $trxData;
	}

	function setStartAndEndDate(&$scheduler) {
		$start = new \DateTime('now', new \DateTimeZone('UTC'));
		$start->add(new \DateInterval('P1M'));
		$scheduler['start_date'] = $start->format('Y-m-d');
		if(!empty($scheduler['end_date'])) {
			$end = $start->add(new \DateInterval('P2Y6M'));
			$scheduler['end_date'] = $end->format('Y-m-d');
		}
	}

	// function testEach() {
	// 	$n = 0;
	// 	$_this = $this;
	// 	Scheduler::each(array('page'=>1, 'per_page'=>10), function($scheduler) use($_this, &$n) {
	// 		$n++;
	// 		$_this->assertIsA($scheduler, 'Hypercharge\Scheduler');
	// 		$_this->assertEqual('DateRecurringSchedule', $scheduler->type);
	// 		$_this->assertIsA($scheduler->amount, 'int');
	// 	});
	// 	$this->assertEqual($n, 10);
	// }

	// function testPage() {
	// 	$n = 0;
	// 	$page = Scheduler::page(array('page'=>1, 'per_page'=>10));

	// 	foreach($page as $scheduler) {
	// 		$n++;
	// 		$this->assertIsA($scheduler, 'Hypercharge\Scheduler');
	// 		$this->assertEqual('DateRecurringSchedule', $scheduler->type);
	// 		$this->assertIsA($scheduler->amount, 'int');
	// 	}
	// 	$this->assertEqual($n, 10);
	// }

	function testCreate() {
		$trxData = $this->transactionFixture('init_recurring_authorize.json');
		unset($trxData['recurring_schedule']);

		$trx = Transaction::init_recurring_authorize($this->channel_token, $trxData);
		$this->assertTrue($trx->isApproved());

		$schedulerData = json_decode(JsonSchemaFixture::request('scheduler_create.json'), true);
		$schedulerData['payment_transaction_unique_id'] = $trx->unique_id;
		$this->setStartAndEndDate($schedulerData);
		$scheduler = Scheduler::create($schedulerData);

		$this->assertIsA($scheduler, 'Hypercharge\Scheduler');
		$this->assertEqual($trx->unique_id, $scheduler->payment_transaction_unique_id);
		$this->assertTrue($scheduler->active);
	}

	function testCreateThrowsIfTransactionHasScheduler() {
		$trxData = $this->transactionFixture('init_recurring_authorize.json');
		$this->setStartAndEndDate($trxData['recurring_schedule']);
		$trx = Transaction::init_recurring_authorize($this->channel_token, $trxData);
		$this->assertTrue($trx->isApproved());
		$this->assertIsA($trx->recurring_schedule, 'Hypercharge\Scheduler');

		$schedulerData = json_decode(JsonSchemaFixture::request('scheduler_create.json'), true);
		$schedulerData['payment_transaction_unique_id'] = $trx->unique_id;
		$this->setStartAndEndDate($schedulerData);

		$this->expectException('Hypercharge\Errors\WorkflowError');
		Scheduler::create($schedulerData);
	}
}
