<?php
namespace Hypercharge;
require_once dirname(__DIR__).'/test_helper.php';

use \Mockery as m;

class PaymentTest extends HyperchargeTestCase {

	function setUp() {
		Config::setIdSeparator(false);
    XmlSerializer::$sort = false;

		$this->curl = $curl = m::mock('Curl');
		$factory = m::mock(new Factory());
		$factory
			->shouldReceive('createHttpsClient')
			->with('the user', 'the passw')
			->andReturn($curl);
		Config::setFactory($factory);
		Config::set('the user', 'the passw', Config::ENV_SANDBOX);
	}

	function tearDown() {
		m::close();
		Config::setFactory(new Factory);
	}

	function testWpf() {
		$data = $this->fixture('wpf_payment_request_simple.json');
		$requestXml  = $this->fixture('wpf_payment_request_simple.xml');
		$responseXml = $this->fixture('wpf_payment_response_simple.xml');
		$this->curl
			->shouldReceive('xmlPost')
			->with('https://testpayment.hypercharge.net/payment', $requestXml)
			->andReturn($responseXml);

		$payment = Payment::wpf($data);
		$this->assertIsA($payment, 'Hypercharge\Payment');
		$this->assertEqual($payment->type, 'WpfPayment');
	}

	function testMobile() {
		$data = $this->fixture('mobile_payment_request_simple.json');
		$requestXml  = $this->fixture('mobile_payment_request_simple.xml');
		$responseXml = $this->fixture('mobile_payment_response_simple.xml');
		$this->curl
			->shouldReceive('xmlPost')
			->with('https://testpayment.hypercharge.net/payment', $requestXml)
			->andReturn($responseXml);

		$payment = Payment::mobile($data);
		$this->assertIsA($payment, 'Hypercharge\Payment');
		$this->assertEqual($payment->type, 'MobilePayment');
	}

	function testMobileWithWpfDataThrows() {
		$error = new Errors\ValidationError();
		$error->add("payment", "The property description is not defined and the definition does not allow additional properties");
		$error->add("payment", "The property editable_by_user is not defined and the definition does not allow additional properties");
		$error->add("payment", "The property return_success_url is not defined and the definition does not allow additional properties");
		$error->add("payment", "The property return_failure_url is not defined and the definition does not allow additional properties");
		$error->add("payment", "The property return_cancel_url is not defined and the definition does not allow additional properties");
		$error->flush();
		$this->expectException($error);
		$data = $this->fixture('wpf_payment_request_simple.json');
		$this->curl
			->shouldReceive('xmlPost')
			->never();
		$payment = Payment::mobile($data);
	}

	function testCancelNoPaymentDataResponse() {
		$unique_id   = '4b356b29d49bca88a7e36666db1cc839'; // see payment_cancel_request.xml
		$requestXml  = $this->fixture('payment_cancel_request.xml');
		$responseXml = $this->fixture('no_payment_data_supplied_response.xml');

		$this->curl
			->shouldReceive('xmlPost')
			->with('https://testpayment.hypercharge.net/payment/cancel', $requestXml)
			->andReturn($responseXml);

		$payment = Payment::cancel($unique_id);
		$this->assertIsA($payment, 'Hypercharge\Payment');
		$this->assertNull($payment->type);
		$this->assertTrue($payment->isError());
		$this->assertNull($payment->unique_id);

		$error = $payment->error;
		$this->assertIsA($error, 'Hypercharge\Errors\InputDataMissingError');
		$this->assertEqual($error->status_code, 320);
		$this->assertEqual($error->message, 'Please check input data for errors!');
		$this->assertEqual($error->technical_message, 'No payment data supplied');
	}

	function testConstructWithErrorInResponse() {
		// TODO
	}

	function testConstructWithVoidResponse() {
		$response = $this->parseXml($this->schemaResponse('WpfPayment_voided.xml'));
		$p = new Payment($response['payment']);
		$this->assertIsA($p, 'Hypercharge\Payment');
		$this->assertIsA($p->transactions, 'array');
		$this->assertEqual(count($p->transactions), 1);
		$this->assertIsA($p->transactions[0], 'Hypercharge\Transaction');
		$trx = $p->transactions[0];
		$this->assertEqual($trx->transaction_id, $p->transaction_id);
		$this->assertIsA($trx->billing_address, 'Hypercharge\Address');
	}

	function testNotificationRoundtrip() {
		$postData = $this->fixture('payment_notification.json');
		$apiPassword = 'b5af4c9cf497662e00b78550fd87e65eb415f42f';
		Config::set('username', $apiPassword, Config::ENV_SANDBOX);
		$notification = Payment::notification($postData);
		$this->assertTrue($notification->isVerified());
		$this->assertFalse($notification->isApproved());
		$this->assertEqual(Payment::STATUS_CANCELED, $notification->payment_status);
		$this->assertEqual(Payment::STATUS_CANCELED, $notification->getPayment()->status);
		$this->assertEqual($this->fixture('payment_notification_ack.xml'), $notification->ack());
	}

	function testNotificationSignatureBroken() {
		$postData = $this->fixture('payment_notification.json');
		$apiPassword = 'wrong';
		Config::set('username', $apiPassword, Config::ENV_SANDBOX);
		$notification = Payment::notification($postData);
		$this->assertFalse($notification->isVerified());
	}

	function testNotificationEmptyParamsThrows() {
		$postData = array();
		$apiPassword = 'b5af4c9cf497662e00b78550fd87e65eb415f42f';
		Config::set('username', $apiPassword, Config::ENV_SANDBOX);
		try {
			Payment::notification($postData);
			$this->fail('Errors\ArgumentError expected!');
		} catch (Errors\ArgumentError $exe) {
			$this->assertEqual('Missing or empty argument 1', $exe->getMessage());
			return;
		} catch(Exception $exe) {
			$this->fail('unexpected Exception: '. $exe->toString());
		}
	}

	function testNotificationMissingPasswordThrows() {
		$postData = $this->fixture('payment_notification.json');
		Config::set('username', '', Config::ENV_SANDBOX);
		$this->assertEqual('', Config::getPassword());
		try {
			Payment::notification($postData);
			$this->fail('Errors\ArgumentError expected!');
		} catch (Errors\ArgumentError $exe) {
			$this->assertEqual('password is not configured! See Hypercharge\Config::set()', $exe->getMessage());
			return;
		} catch(Exception $exe) {
			$this->fail('unexpected Exception: '. $exe->toString());
		}
	}

}

