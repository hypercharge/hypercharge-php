<?php
namespace Hypercharge;

require_once dirname(__DIR__).'/test_helper.php';

class PaymentUrlTest extends \UnitTestCase {

	function testSandboxDefaultAction() {
		$url = new PaymentUrl(Config::ENV_SANDBOX);
		$this->assertEqual('https://testpayment.hypercharge.net/payment', $url->get());
	}

	function testLiveDefaultAction() {
		$url = new PaymentUrl(Config::ENV_LIVE);
		$this->assertEqual('https://payment.hypercharge.net/payment', $url->get());
	}

	function testSandboxCancelAction() {
		$url = new PaymentUrl(Config::ENV_SANDBOX, 'cancel');
		$this->assertEqual('https://testpayment.hypercharge.net/payment/cancel', $url->get());
	}

	function testLiveCancelAction() {
		$url = new PaymentUrl(Config::ENV_LIVE, 'cancel');
		$this->assertEqual('https://payment.hypercharge.net/payment/cancel', $url->get());
	}

	function testWrongMode() {
		$this->expectException(new \Exception('mode must be "sandbox" or "live"'));
		$url = new PaymentUrl('wrong', 'cancel');
	}

	function testWrongAction() {
		$this->expectException(new \Exception('action must be one of "create", "reconcile", "cancel", "void", "capture", "refund" but is: "wrong"'));
		$url = new PaymentUrl(Config::ENV_SANDBOX, 'wrong');
	}
}