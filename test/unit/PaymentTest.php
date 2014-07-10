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

	function testFind() {
		$request  = $this->schemaRequest('reconcile.xml');
		$request = preg_replace('/<unique_id>[0-9a-f]+<\/unique_id>/', '<unique_id>f002f4b2c726f8b7312fccbcda990a3c</unique_id>', $request);
		$response = $this->schemaResponse('WpfPayment_find.xml');
		// print_r($request);
		// print_r($response);

		$this->curl
			->shouldReceive('xmlPost')
			->with('https://testpayment.hypercharge.net/payment/reconcile', $request)
			->andReturn($response);

		$payment = Payment::find('f002f4b2c726f8b7312fccbcda990a3c');
		$this->assertIsA($payment, 'Hypercharge\Payment');
		$this->assertEqual($payment->unique_id, 'f002f4b2c726f8b7312fccbcda990a3c');
		$this->assertEqual($payment->amount, 5000);

		$trxs = $payment->transactions;
		$this->assertIsA($trxs, 'array');
		$this->assertEqual(count($trxs), 1);
		$this->assertIsA($trxs[0], 'Hypercharge\Transaction');
		$this->assertEqual($trxs[0]->unique_id, '33d0ea86616a89d091a300c25ac683cf');
	}

	function testFindWithSystemError() {
		$request  = $this->schemaRequest('reconcile.xml');
		$response = $this->schemaResponse('WpfPayment_error.xml');

		$this->curl
			->shouldReceive('xmlPost')
			->with('https://testpayment.hypercharge.net/payment/reconcile', $request)
			->andReturn($response);

		$payment = Payment::find('61c06cf0a03d01307dde542696cde09d');
		$this->assertIsA($payment, 'Hypercharge\Payment');
		$this->assertTrue($payment->isFatalError());
		$error = $payment->error;
		$this->assertIsA($error, 'Hypercharge\Errors\SystemError');
		$this->assertEqual($error->status_code, 100);
		$this->assertEqual($error->technical_message, 'Unknown system error. Please contact support.');
		$this->assertEqual($error->message          , 'Transaction failed, please contact support!');
}

	function testFindWithWorkflowError() {
		$request  = $this->schemaRequest('reconcile.xml');
		$response = $this->schemaResponse('WpfPayment_error_400.xml');

		$this->curl
			->shouldReceive('xmlPost')
			->with('https://testpayment.hypercharge.net/payment/reconcile', $request)
			->andReturn($response);

		$payment = Payment::find('61c06cf0a03d01307dde542696cde09d');
		$this->assertIsA($payment, 'Hypercharge\Payment');
		$this->assertTrue($payment->isFatalError());
		$error = $payment->error;
		$this->assertIsA($error, 'Hypercharge\Errors\WorkflowError');
		$this->assertEqual($error->status_code, 400);
		$this->assertEqual($error->technical_message, 'payment not found.');
		$this->assertEqual($error->message          , 'Something went wrong, please contact support!');
	}

	function testNotificationRoundtrip() {
		$postData = $this->schemaNotification('payment_notification.json');
		$apiPassword = 'b5af4c9cf497662e00b78550fd87e65eb415f42f';
		Config::set('username', $apiPassword, Config::ENV_SANDBOX);
		$notification = Payment::notification($postData);
		$this->assertTrue($notification->isVerified());
		$this->assertFalse($notification->isApproved());
		$this->assertEqual(Payment::STATUS_CANCELED, $notification->payment_status);
		$this->assertEqual($this->schemaNotification('payment_notification_ack.xml'), $notification->ack());
	}

	function testNotificationSignatureBroken() {
		$postData = $this->schemaNotification('payment_notification.json');
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
		$postData = $this->schemaNotification('payment_notification.json');
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

	function testToStringWithoutTrx() {
		$data = $this->parseXml($this->schemaResponse('WpfPayment_find.xml'));
		unset($data['payment']['payment_transaction']);
		$p = new Payment($data['payment']);
		$this->assertEqual($p.'', "Hypercharge\Payment { type: WpfPayment, unique_id: f002f4b2c726f8b7312fccbcda990a3c, status: approved, currency: USD, amount: 5000, transaction_id: 0AF671AF-4134-4BE7-BDF0-26E38B74106E---d8981080a4f701303cf4542696cde09d, timestamp: 2013-05-22T10:23:59Z, transactions: [], error: }");
	}

	function testToStringWithOneTrx() {
		$data = $this->parseXml($this->schemaResponse('WpfPayment_find.xml'));
		$p = new Payment($data['payment']);
		$this->assertEqual($p.'', "Hypercharge\Payment { type: WpfPayment, unique_id: f002f4b2c726f8b7312fccbcda990a3c, status: approved, currency: USD, amount: 5000, transaction_id: 0AF671AF-4134-4BE7-BDF0-26E38B74106E---d8981080a4f701303cf4542696cde09d, timestamp: 2013-05-22T10:23:59Z, transactions: [sale:33d0ea86616a89d091a300c25ac683cf], error: }");
	}

	function testToStringWithTwoTrx() {
		$data = $this->parseXml($this->schemaResponse('WpfPayment_find.xml'));

		$trx1 = $trx2 = $data['payment']['payment_transaction'];

		$trx2['unique_id'] = 'abcdf4b2c726f8b7312fccbcda99efgh';
		$trx2['transaction_type'] = 'refund';

		$data['payment']['payment_transaction'] = array($trx1, $trx2);

		$p = new Payment($data['payment']);
		$this->assertEqual($p.'', "Hypercharge\Payment { type: WpfPayment, unique_id: f002f4b2c726f8b7312fccbcda990a3c, status: approved, currency: USD, amount: 5000, transaction_id: 0AF671AF-4134-4BE7-BDF0-26E38B74106E---d8981080a4f701303cf4542696cde09d, timestamp: 2013-05-22T10:23:59Z, transactions: [sale:33d0ea86616a89d091a300c25ac683cf, refund:abcdf4b2c726f8b7312fccbcda99efgh], error: }");
	}
}

