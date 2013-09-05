<?php
namespace Hypercharge;
use \Mockery as m;

require_once dirname(__DIR__).'/test_helper.php';

if(getenv('DEBUG') == '1') Config::setLogger(new StdoutLogger());

class SchedulerIntegrationTest extends HyperchargeTestCase {

	function setUp() {
		$this->credentials();

		Config::setIdSeparator('---');

		$this->channel_token = $this->credentials->channelTokens->EUR;
	}

	function tearDown() {
		parent::tearDown();
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

	function mockV2Url($path, $params = null) {
		if(!method_exists(Config::getFactory(), 'shouldReceive')) return;
		// mocking in php is pain in the arxx
		if($params === null) {
			$url = m::mock('Hypercharge\v2\Url[getUrl]', array('sandbox', $path));
			$url->shouldReceive('getUrl')->andReturn($this->credentials->gatewayHost.'/v2');
			Config::getFactory()->shouldReceive('createUrl')->with($path)->andReturn($url);
		} else {
			$url = m::mock('Hypercharge\v2\Url[getUrl]', array('sandbox', $path, $params));
			$url->shouldReceive('getUrl')->andReturn($this->credentials->gatewayHost.'/v2');
			Config::getFactory()->shouldReceive('createUrl')->with($path, $params)->andReturn($url);
		}
	}



	function testEach() {
		$this->mockV2Url('scheduler', array('page'=>1, 'per_page'=>10));

		$n = 0;
		$_this = $this;
		Scheduler::each(array('page'=>1, 'per_page'=>10), function($scheduler) use($_this, &$n) {
			$n++;
			$_this->assertIsA($scheduler, 'Hypercharge\Scheduler');
			$_this->assertEqual('DateRecurringSchedule', $scheduler->type);
			$_this->assertIsA($scheduler->amount, 'int');
		});
		$this->assertEqual($n, 10);
	}

	function testPage() {
		$this->mockV2Url('scheduler', array('page'=>1, 'per_page'=>10));

		$n = 0;
		$page = Scheduler::page(array('page'=>1, 'per_page'=>10));

		foreach($page as $scheduler) {
			$n++;
			$this->assertIsA($scheduler, 'Hypercharge\Scheduler');
			$this->assertEqual('DateRecurringSchedule', $scheduler->type);
			$this->assertIsA($scheduler->amount, 'int');
		}
		$this->assertEqual($n, 10);
	}

	function testCreate() {
		$trxData = $this->transactionFixture('init_recurring_authorize.json');
		unset($trxData['recurring_schedule']);

		$trx = Transaction::init_recurring_authorize($this->channel_token, $trxData);
		$this->assertTrue($trx->isApproved());

		$schedulerData = json_decode(JsonSchemaFixture::request('scheduler_create.json'), true);
		$schedulerData['payment_transaction_unique_id'] = $trx->unique_id;
		$this->setStartAndEndDate($schedulerData);

		$this->mockV2Url('scheduler');

		$scheduler = Scheduler::create($schedulerData);

		$this->assertIsA($scheduler, 'Hypercharge\Scheduler');
		$this->assertEqual($trx->unique_id, $scheduler->payment_transaction_unique_id);
		$this->assertTrue($scheduler->active);
		$this->assertIdentical(12345, $scheduler->amount);

		return $scheduler;
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
		$this->mockV2Url('scheduler');

		Scheduler::create($schedulerData);
	}

	function testFind() {
		$scheduler = $this->testCreate();
		$uid = $scheduler->unique_id;

		$this->mockV2Url(array('scheduler', $uid));

		$s = Scheduler::find($uid);

		$this->assertIsA($s, 'Hypercharge\Scheduler');
		$this->assertEqual($uid, $s->unique_id);
		$this->assertIdentical(true, $s->active);
		$this->assertEqual('DateRecurringSchedule', $s->type);
		$this->assertEqual(Scheduler::MONTHLY, $s->interval);
		$this->assertIdentical(12345, $s->amount);
		$this->assertEqual($scheduler->payment_transaction_unique_id, $s->payment_transaction_unique_id);
	}

	function testDelete() {
		$scheduler = $this->testCreate();
		$uid = $scheduler->unique_id;

		$this->mockV2Url(array('scheduler', $uid));

		Scheduler::delete($uid);

		try {
			Scheduler::find($uid);
		} catch(Errors\NetworkError $exe) {
			$this->assertEqual(404, $exe->http_status);
			$this->assertPattern('/^The requested URL returned error: 404\b/', $exe->technical_message);
			return;
		}
		$this->fail('find deleted scheduler should throw NetworkError 404!');
	}

	function testUpdate() {
		$scheduler = $this->testCreate();
		$uid = $scheduler->unique_id;
		$newAmount = $scheduler->amount + 100;

		$this->mockV2Url(array('scheduler', $uid));

		$s = Scheduler::update($uid, array('amount' => $newAmount));
		$this->assertIdentical($newAmount, $s->amount);

		$s = Scheduler::find($uid);
		$this->assertIdentical($newAmount, $s->amount);
	}

	function testDeactivateActivate() {
		$scheduler = $this->testCreate();
		$uid = $scheduler->unique_id;
		$this->assertTrue($scheduler->active);
		$this->mockV2Url(array('scheduler', $uid));

		$s = Scheduler::deactivate($uid);
		$this->assertFalse($s->active);

		$s = Scheduler::find($uid);
		$this->assertFalse($s->active);

		$s = Scheduler::activate($uid);
		$this->assertTrue($s->active);

		$s = Scheduler::find($uid);
		$this->assertTrue($s->active);
	}

	function testNext() {
		$scheduler = $this->testCreate();
		$uid = $scheduler->unique_id;

		$this->mockV2Url(array('scheduler', $uid, 'next'));

		$date = Scheduler::next($uid);
		$this->assertPattern('/^20\d\d-\d\d-\d\d$/', $date);
	}

	function testNextForDeactivated() {
		$scheduler = $this->testCreate();
		$uid = $scheduler->unique_id;
		$this->mockV2Url(array('scheduler', $uid));
		$s = Scheduler::deactivate($uid);

		$this->mockV2Url(array('scheduler', $uid, 'next'));

		$date = Scheduler::next($uid);
		$this->assertIdentical(null, $date);
	}

	function testTransactionsPage() {


	}

	function testTransactionsPageThrows() {


	}

	function testTransactionsEach() {


	}

	function testTransactionsEachThrows() {


	}


 }
