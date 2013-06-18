<?php
namespace Hypercharge;

require_once dirname(__DIR__).'/test_helper.php';

class TransactionUrlTest extends \UnitTestCase {

	function testSandboxDefaultAction() {
		$url = new TransactionUrl(Config::ENV_SANDBOX, 'channel-token');
		$this->assertEqual('https://test.hypercharge.net/process/channel-token', $url->get());
	}

	function testLiveDefaultAction() {
		$url = new TransactionUrl(Config::ENV_LIVE, 'channel-token');
		$this->assertEqual('https://hypercharge.net/process/channel-token', $url->get());
	}

	function testSandboxCancelAction() {
		$url = new TransactionUrl(Config::ENV_SANDBOX, 'channel-token', 'reconcile');
		$this->assertEqual('https://test.hypercharge.net/reconcile/channel-token', $url->get());
	}

	function testLiveCancelAction() {
		$url = new TransactionUrl(Config::ENV_LIVE, 'channel-token', 'reconcile');
		$this->assertEqual('https://hypercharge.net/reconcile/channel-token', $url->get());
	}

	function testWrongMode() {
		$this->expectException(new \Exception('mode must be "sandbox" or "live"'));
		$url = new TransactionUrl('wrong', 'channel-token');
	}

	function testWrongAction() {
		$this->expectException(new \Exception('action must be one of "process", "reconcile", "reconcile/by_date", "recurring/schedules_by_date", "recurring/unsubscribe", "recurring/activate", "recurring/deactivate" but got "wrong"'));
		$url = new TransactionUrl(Config::ENV_SANDBOX, 'channel-token', 'wrong');
	}
}