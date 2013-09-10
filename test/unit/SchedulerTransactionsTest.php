<?php
namespace Hypercharge;
use \Mockery as m;

require_once dirname(__DIR__).'/test_helper.php';

class SchedulerTransactionsTest extends HyperchargeTestCase {

	function testIndexThrowsIfInvalidUid() {
		$this->expectException('Hypercharge\Errors\ValidationError');
		SchedulerTransactions::index('crappy unique_id');
	}

	function testIndexThrowsIfInvalidParams() {
		$this->expectException('Hypercharge\Errors\ValidationError');
		SchedulerTransactions::index('e1420438c52b4cb3a03a14a7e4fc16e1', array('not_allowed_field' => 'foo'));
	}

	function testIndexThrowsIfResponseHasInvalid_entries_base_type() {
		$this->expectException('Hypercharge\Errors\ResponseFormatError');

		$response = json_decode(JsonSchemaFixture::response('scheduled_transactions_one.json'));
		$response->entries_base_type = 'Payment';

		$this->expect_Curl_jsonRequest()
			->with('GET', 'https://test.hypercharge.net/v2/scheduler/e1420438c52b4cb3a03a14a7e4fc16e1/transactions')
			->andReturn($response);

		SchedulerTransactions::index('e1420438c52b4cb3a03a14a7e4fc16e1');
	}

	function testPageWithOne() {
		$response = json_decode(JsonSchemaFixture::response('scheduled_transactions_one.json'));

		$this->expect_Curl_jsonRequest()
			->with('GET', 'https://test.hypercharge.net/v2/scheduler/e1420438c52b4cb3a03a14a7e4fc16e1/transactions')
			->andReturn($response);

		$page = SchedulerTransactions::page('e1420438c52b4cb3a03a14a7e4fc16e1');

		$this->assertIsA($page, 'Hypercharge\PaginatedCollection');
		$this->assertEqual(1, $page->getTotalCount());
		$this->assertEqual(1, $page->getPage());
		$this->assertEqual(50, $page->getPerPage());
		$this->assertEqual(1, $page->getCount());
		$this->assertEqual(1, count($page->getEntries()));

		$trx = current($page->getEntries());
		$this->assertIsA($trx, 'Hypercharge\Transaction');
		$this->assertEqual('3ba2d77ab04f773c0a47bd1081ac50be', $trx->unique_id);
		$this->assertEqual('4', $trx->transaction_id);
		$this->assertEqual('recurring_sale', $trx->transaction_type);
		$this->assertIdentical(500, $trx->amount);
		$this->assertEqual('USD', $trx->currency);
		$this->assertEqual('new', $trx->status);
		$this->assertEqual('test', $trx->mode);
		$this->assertEqual('Descriptor 1', $trx->descriptor);
		$this->assertNull($trx->error);
		$this->assertEqual('2013-08-13T16:59:45Z', $trx->timestamp);
	}

	function testPageWithEmpty() {
		$response = json_decode(JsonSchemaFixture::response('scheduled_transactions_empty.json'));

		$this->expect_Curl_jsonRequest()
			->with('GET', 'https://test.hypercharge.net/v2/scheduler/e1420438c52b4cb3a03a14a7e4fc16e1/transactions?page=3')
			->andReturn($response);

		$page = SchedulerTransactions::page('e1420438c52b4cb3a03a14a7e4fc16e1', array('page'=>3));

		$this->assertIsA($page, 'Hypercharge\PaginatedCollection');
		$this->assertEqual(0, $page->getTotalCount());
		$this->assertEqual(1, $page->getPage());
		$this->assertEqual(50, $page->getPerPage());
		$this->assertEqual(0, $page->getCount());
		$this->assertEqual(0, count($page->getEntries()));
	}

	function testEachWithOne() {
		$response = json_decode(JsonSchemaFixture::response('scheduled_transactions_one.json'));

		$this->expect_Curl_jsonRequest()
			->with('GET', 'https://test.hypercharge.net/v2/scheduler/e1420438c52b4cb3a03a14a7e4fc16e1/transactions')
			->andReturn($response);

		$uids = array();
		$_this = $this;
		SchedulerTransactions::each('e1420438c52b4cb3a03a14a7e4fc16e1', array(), function($trx) use ($_this, &$uids) {
			$uids[] = $trx->unique_id;
			$_this->assertIsA($trx, 'Hypercharge\Transaction');
			$_this->assertEqual('3ba2d77ab04f773c0a47bd1081ac50be', $trx->unique_id);
			$_this->assertEqual('4', $trx->transaction_id);
			$_this->assertEqual('recurring_sale', $trx->transaction_type);
			$_this->assertIdentical(500, $trx->amount);
			$_this->assertEqual('USD', $trx->currency);
			$_this->assertEqual('new', $trx->status);
			$_this->assertEqual('test', $trx->mode);
			$_this->assertEqual('Descriptor 1', $trx->descriptor);
			$_this->assertNull($trx->error);
			$_this->assertEqual('2013-08-13T16:59:45Z', $trx->timestamp);
		});

		$this->assertEqual(array('3ba2d77ab04f773c0a47bd1081ac50be'), $uids);
	}

	function testEachWithEmpty() {
		$response = json_decode(JsonSchemaFixture::response('scheduled_transactions_empty.json'));

		$this->expect_Curl_jsonRequest()
			->with('GET', 'https://test.hypercharge.net/v2/scheduler/e1420438c52b4cb3a03a14a7e4fc16e1/transactions?page=3')
			->andReturn($response);

		$_this = $this;
		SchedulerTransactions::each('e1420438c52b4cb3a03a14a7e4fc16e1', array('page'=>3), function($trx) use ($_this) {
			$_this->fail('callback should not be called!');
		});
	}

}