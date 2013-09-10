<?php
namespace Hypercharge;
use \Mockery as m;

require_once dirname(__DIR__).'/test_helper.php';

class SchedulerTest extends HyperchargeTestCase {

	function testConstructorEmpty() {
		$r = new Scheduler(array());
		$this->assertFalse(isset($r->recurring_reference_id));
		$this->assertFalse(isset($r->amount));
		$this->assertFalse(isset($r->start_date));
		$this->assertFalse(isset($r->end_date));
		$this->assertFalse(isset($r->active));
		$this->assertFalse(isset($r->enabled));
	}

	function testConstructorConsumesFixture() {
		$response = json_decode(JsonSchemaFixture::response('scheduler.json'));
		$r = new Scheduler($response);
		$this->assertEqual($r->type, 'DateRecurringSchedule');
		$this->assertEqual($r->unique_id, 'e1420438c52b4cb3a03a14a7e4fc16e1');
		$this->assertEqual($r->payment_transaction_unique_id, 'e1420438c52b4cb3a03a14a7e4fc16e1');
		$this->assertEqual($r->currency, 'USD');
		$this->assertIsA($r->amount, 'integer');
		$this->assertEqual($r->amount, 73100);
		$this->assertIsA($r->active, 'boolean');
		$this->assertTrue($r->active);
		$this->assertEqual($r->interval, 'monthly');
		$this->assertEqual($r->start_date, '2013-11-13');
		$this->assertEqual($r->end_date, '2014-06-30');
		$this->assertEqual($r->timestamp, '2013-08-13T16:42:09Z');
	}

	function request($requestFixture) {
		return json_decode(JsonSchemaFixture::request($requestFixture));
	}

	function response($responseFixture) {
		return json_decode(JsonSchemaFixture::response($responseFixture));
	}

	function testIndexThrowsIfInvalidParams() {
		$this->expectException('Hypercharge\Errors\ValidationError');

		$this->expect_Curl_jsonRequest()->never();

		Scheduler::index(array('wrong_field'=>'foo'));
	}

	function testIndexThrowsIfWrongType() {
		$this->expectException('Hypercharge\Errors\ResponseFormatError');
		$response = $this->response('scheduler_empty_result.json');
		$response->type = 'WrongType';

		$this->expect_Curl_jsonRequest()
			->with('GET', 'https://test.hypercharge.net/v2/scheduler')
			->andReturn($response);

		Scheduler::index(array());
	}

	function testIndexThrowsIfWrongEntriesBaseType() {
		$this->expectException('Hypercharge\Errors\ResponseFormatError');
		$response = $this->response('scheduler_empty_result.json');
		$response->entries_base_type = 'WrongType';

		$this->expect_Curl_jsonRequest()
			->with('GET', 'https://test.hypercharge.net/v2/scheduler')
			->andReturn($response);

		$_this = $this;

		Scheduler::index(array());
	}

	function testEachConsumesEmptyFixture() {
		$this->expect_Curl_jsonRequest()
			->with('GET', 'https://test.hypercharge.net/v2/scheduler?page=1&per_page=7')
			->andReturn($this->response('scheduler_empty_result.json'));

		$_this = $this;

		$cp = function($scheduler) use($_this) {
			$_this->fail('callback should not be called!');
		};

		Scheduler::each(array('page'=>1, 'per_page'=>7), $cp);
	}

	function testEachConsumesFixture() {
		$this->expect_Curl_jsonRequest()
			->with('GET', 'https://test.hypercharge.net/v2/scheduler?page=1&per_page=7')
			->andReturn($this->response('scheduler_page_1.json'));

		$_this = $this;
		$uids = array();

		$cp = function($scheduler) use($_this, &$uids) {
			$_this->assertEqual('DateRecurringSchedule', $scheduler->type);
			$uids[] = $scheduler->unique_id;
		};

		Scheduler::each(array('page'=>1, 'per_page'=>7), $cp);

		$this->assertEqual(
			array(
				 '0293069be5a868ae69290e8a0eff72b3','0763d2761d004a86f24807594610900b'
				,'22cd57da4a1c36652b6eb3e5b7587b03','d07061244f5a468271bf27486ccfcaa2'
				,'ff700580d3c19e1f1d8f6364c1c7d707','f3268a2e9ae4d389d92d4503c480c67d'
				,'c4e9afeddc0c7dd907433187ac86e1bd'
			),
			$uids
		);
	}

	function testPageComsumesEmptyFixture() {
		$this->expect_Curl_jsonRequest()
			->with('GET', 'https://test.hypercharge.net/v2/scheduler')
			->andReturn($this->response('scheduler_empty_result.json'));

		$page = Scheduler::page();
		$this->assertIsA($page, 'Hypercharge\PaginatedCollection');
		$this->assertEqual(1, $page->getPage());
		$this->assertEqual(0, $page->getTotalCount());
		$this->assertEqual(50, $page->getPerPage());
		$this->assertEqual(array(), $page->getEntries());
	}

	function testPageComsumesFixture() {
		$this->expect_Curl_jsonRequest()
			->with('GET', 'https://test.hypercharge.net/v2/scheduler')
			->andReturn($this->response('scheduler_page_1.json'));

		$page = Scheduler::page();
		$this->assertIsA($page, 'Hypercharge\PaginatedCollection');
		$this->assertEqual( 1, $page->getPage());
		$this->assertEqual(10, $page->getTotalCount());
		$this->assertEqual( 7, $page->getPerPage());
		$uids = array();
		foreach($page as $entry) {
			$this->assertIsA($entry, 'Hypercharge\Scheduler');
			$uids[] = $entry->unique_id;
		}
		$this->assertEqual(
			array(
				 '0293069be5a868ae69290e8a0eff72b3','0763d2761d004a86f24807594610900b'
				,'22cd57da4a1c36652b6eb3e5b7587b03','d07061244f5a468271bf27486ccfcaa2'
				,'ff700580d3c19e1f1d8f6364c1c7d707','f3268a2e9ae4d389d92d4503c480c67d'
				,'c4e9afeddc0c7dd907433187ac86e1bd'
			),
			$uids
		);
	}

	function testFind() {
		$this->expect_Curl_jsonRequest()
			->with('GET', 'https://test.hypercharge.net/v2/scheduler/e1420438c52b4cb3a03a14a7e4fc16e1')
			->andReturn($this->response('scheduler.json'));

		$scheduler = Scheduler::find('e1420438c52b4cb3a03a14a7e4fc16e1');

		$this->assertIsA($scheduler, 'Hypercharge\Scheduler');
		$this->assertEqual('DateRecurringSchedule', $scheduler->type);
		$this->assertEqual('e1420438c52b4cb3a03a14a7e4fc16e1', $scheduler->unique_id);
		$this->assertEqual('e1420438c52b4cb3a03a14a7e4fc16e1', $scheduler->payment_transaction_unique_id);
		$this->assertIdentical(73100, $scheduler->amount);
		$this->assertEqual('monthly', $scheduler->interval);
		$this->assertEqual('2013-11-13', $scheduler->start_date);
		$this->assertEqual('2014-06-30', $scheduler->end_date);
		$this->assertEqual('USD', $scheduler->currency);
		$this->assertIdentical(true, $scheduler->active);
		$this->assertEqual('2013-08-13T16:42:09Z', $scheduler->timestamp);
	}

	function testFindWithInvalidUniqueIdThrows() {
		$this->expectException('Hypercharge\Errors\ValidationError');

		$this->expect_Curl_jsonRequest()->never();
		Scheduler::find('invalid-unique_id');
	}

	function testCreate() {
		$request = $this->request('scheduler_create.json');
		$this->expect_Curl_jsonRequest()
			->with('POST', 'https://test.hypercharge.net/v2/scheduler', json_encode($request))
			->andReturn($this->response('scheduler.json'));

		$scheduler = Scheduler::create($request);

		$this->assertIsA($scheduler, 'Hypercharge\Scheduler');
		$this->assertEqual('DateRecurringSchedule', $scheduler->type);
		$this->assertEqual('e1420438c52b4cb3a03a14a7e4fc16e1', $scheduler->unique_id);
		$this->assertEqual('e1420438c52b4cb3a03a14a7e4fc16e1', $scheduler->payment_transaction_unique_id);
		$this->assertIdentical(73100, $scheduler->amount);
		$this->assertEqual('monthly', $scheduler->interval);
		$this->assertEqual('2013-11-13', $scheduler->start_date);
		$this->assertEqual('2014-06-30', $scheduler->end_date);
		$this->assertEqual('USD', $scheduler->currency);
		$this->assertIdentical(true, $scheduler->active);
		$this->assertEqual('2013-08-13T16:42:09Z', $scheduler->timestamp);
	}

	function testCreateThrowsIfInvalidData() {
		$this->expectException('Hypercharge\Errors\ValidationError');

		$request = $this->request('scheduler_create.json');
		$request->payment_transaction_unique_id = 'crapy-id';

		$this->expect_Curl_jsonRequest()->never();
		Scheduler::create($request);
	}

	function testUpdate() {
		$request = $this->request('scheduler_update.json');
		$this->expect_Curl_jsonRequest()
			->with('PUT', 'https://test.hypercharge.net/v2/scheduler/e1420438c52b4cb3a03a14a7e4fc16e1'
							, json_encode($request))
			->andReturn($this->response('scheduler.json'));

		$scheduler = Scheduler::update('e1420438c52b4cb3a03a14a7e4fc16e1', $request);

		$this->assertIsA($scheduler, 'Hypercharge\Scheduler');
		$this->assertEqual('DateRecurringSchedule', $scheduler->type);
		$this->assertEqual('e1420438c52b4cb3a03a14a7e4fc16e1', $scheduler->unique_id);
		$this->assertEqual('e1420438c52b4cb3a03a14a7e4fc16e1', $scheduler->payment_transaction_unique_id);
		$this->assertIdentical(73100, $scheduler->amount);
		$this->assertEqual('monthly', $scheduler->interval);
		$this->assertEqual('2013-11-13', $scheduler->start_date);
		$this->assertEqual('2014-06-30', $scheduler->end_date);
		$this->assertEqual('USD', $scheduler->currency);
		$this->assertIdentical(true, $scheduler->active);
		$this->assertEqual('2013-08-13T16:42:09Z', $scheduler->timestamp);
	}

	function testUpdateThrowsIfInvalidData() {
		$this->expectException('Hypercharge\Errors\ValidationError');

		$request = $this->request('scheduler_update.json');
		$request->payment_transaction_unique_id = 'not-allowed';

		$this->expect_Curl_jsonRequest()->never();
		Scheduler::update('e1420438c52b4cb3a03a14a7e4fc16e1', $request);
	}

	function testUpdateThrowsIfInvalidUid() {
		$this->expectException('Hypercharge\Errors\ValidationError');

		$request = $this->request('scheduler_update.json');

		$this->expect_Curl_jsonRequest()->never();
		Scheduler::update('e1420438c52b', $request); // uid too short
	}

	function testActivate() {
		$this->expect_Curl_jsonRequest()
			->with('PUT', 'https://test.hypercharge.net/v2/scheduler/e1420438c52b4cb3a03a14a7e4fc16e1'
							, json_encode(array('active'=>true)))
			->andReturn($this->response('scheduler.json'));

		$scheduler = Scheduler::activate('e1420438c52b4cb3a03a14a7e4fc16e1');
	}

	function testActivateThrowsIfInvalideUid() {
		$this->expectException('Hypercharge\Errors\ValidationError');

		$this->expect_Curl_jsonRequest()->never();

		$scheduler = Scheduler::activate('wrong');
	}

	function testDeactivate() {
		$this->expectException('Hypercharge\Errors\ValidationError');

		$this->expect_Curl_jsonRequest()->never();

		$scheduler = Scheduler::deactivate('wrong');
	}

	function testDelete() {
		$this->expect_Curl_jsonRequest()
			->with('DELETE', 'https://test.hypercharge.net/v2/scheduler/e1420438c52b4cb3a03a14a7e4fc16e1');

		Scheduler::delete('e1420438c52b4cb3a03a14a7e4fc16e1');
	}

	function testNextInvalidUidThrows() {
		$this->expectException('Hypercharge\Errors\ValidationError');

		$this->expect_Curl_jsonRequest()->never();

		Scheduler::next('wrong_unique_id');
	}

	function testNextResponseNull() {
		$this->expect_Curl_jsonRequest()
			->with('GET', 'https://test.hypercharge.net/v2/scheduler/e1420438c52b4cb3a03a14a7e4fc16e1/next')
			->andReturn(null);

		$never = Scheduler::next('e1420438c52b4cb3a03a14a7e4fc16e1');
		$this->assertIdentical(null, $never);
	}

	function testNextResponseWrongFormatThrows() {
		$this->expectException('Hypercharge\Errors\ResponseFormatError');

		$this->expect_Curl_jsonRequest()
			->with('GET', 'https://test.hypercharge.net/v2/scheduler/e1420438c52b4cb3a03a14a7e4fc16e1/next')
			->andReturn($this->request('scheduler_update.json'));

		Scheduler::next('e1420438c52b4cb3a03a14a7e4fc16e1');
	}

	function testNextResponseNoDueDateThrows() {
		$this->expectException('Hypercharge\Errors\ResponseFormatError');
		$response = $this->response('scheduler_next.json');
		unset($response->due_date);

		$this->expect_Curl_jsonRequest()
			->with('GET', 'https://test.hypercharge.net/v2/scheduler/e1420438c52b4cb3a03a14a7e4fc16e1/next')
			->andReturn($response);

		Scheduler::next('e1420438c52b4cb3a03a14a7e4fc16e1');
	}

	function testNext() {
		$response = $this->response('scheduler_next.json');

		$this->expect_Curl_jsonRequest()
			->with('GET', 'https://test.hypercharge.net/v2/scheduler/e1420438c52b4cb3a03a14a7e4fc16e1/next')
			->andReturn($response);

		$next = Scheduler::next('e1420438c52b4cb3a03a14a7e4fc16e1');
		$this->assertEqual('2014-06-02', $next);
	}
}