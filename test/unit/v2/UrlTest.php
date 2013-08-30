<?php
namespace Hypercharge\v2;

require_once dirname(dirname(__DIR__)).'/test_helper.php';

class UrlTest extends \Hypercharge\HyperchargeTestCase {

	function getRequestFixture($fileName) {
		return json_decode(\Hypercharge\JsonSchemaFixture::request($fileName), true);
	}

	function testSandboxSchedulerIndex() {
		$url = new Url('sandbox', 'scheduler');
		$this->assertEqual('https://test.hypercharge.net/v2/scheduler', $url->get());
	}

	function testLiveSchedulerIndex() {
		$url = new Url('live', 'scheduler');
		$this->assertEqual('https://hypercharge.net/v2/scheduler', $url->get());
	}

	function testLiveSchedulerIndexWithFalseParam() {
		$url = new Url('live', 'scheduler', array('active'=>false));
		$this->assertEqual('https://hypercharge.net/v2/scheduler?active=0', $url->get());
	}

	function testLiveSchedulerIndexWithTrueParam() {
		$url = new Url('live', 'scheduler', array('active'=>true));
		$this->assertEqual('https://hypercharge.net/v2/scheduler?active=1', $url->get());
	}

	function testLiveSchedulerIndexWithParams() {
		$params = $this->getRequestFixture('scheduler_index_get_params.json');
		$this->assertIsA($params, 'array');
		$url = new Url('live', 'scheduler', $params);
		$this->assertEqual('https://hypercharge.net/v2/scheduler?page=1&per_page=20&start_date_from=2014-03-01&start_date_to=2014-04-01&end_date_from=2015-09-01&end_date_to=2015-10-01&active=1', $url->get());
	}

	function testSchedulerUid() {
		$url = new Url('live', array('scheduler', 'abcdef12345'));
		$this->assertEqual('https://hypercharge.net/v2/scheduler/abcdef12345', $url->get());
	}

	function testSchedulerUidTransactions() {
		$url = new Url('live', array('scheduler', 'abcdef12345', 'transactions'));
		$this->assertEqual('https://hypercharge.net/v2/scheduler/abcdef12345/transactions', $url->get());
	}

	function testSchedulerUidTransactionsWithParams() {
		$params = $this->getRequestFixture('scheduler_transactions_index_get_params.json');
		$url = new Url('live', array('scheduler', 'abcdef12345', 'transactions'), $params);
		$this->assertEqual('https://hypercharge.net/v2/scheduler/abcdef12345/transactions?page=1&per_page=20', $url->get());
	}

	function testWrongMode() {
		$this->expectException(new \Exception('mode must be "sandbox" or "live"'));
		$url = new Url('wrong', 'scheduler');
	}

}