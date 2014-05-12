<?php
namespace Hypercharge;
require_once dirname(__DIR__).'/test_helper.php';
use \Mockery as m;

if(getenv('DEBUG') == '1') Config::setLogger(new StdoutLogger());

class PaymentIntegrationTest extends HyperchargeTestCase {

	function setUp() {
		$this->credentials();

		Config::setIdSeparator('---');

		$this->expected_payment_methods = array(
			"barzahlen"
			,"credit_card"
			,"direct_debit"
			,"direct_pay24_sale"
			,"giro_pay_sale"
			,"ideal_sale"
			,"pay_in_advance"
			,"pay_pal"
			,"pay_safe_card_sale"
			,"payment_on_delivery"
			,"purchase_on_account"
		);
		sort($this->expected_payment_methods);
	}

	function tearDown() {
		m::close();
		Config::setFactory(new Factory);
	}


	function testWrongPwd() {
		$this->expectException(new Errors\NetworkError($this->credentials->paymentHost.'/payment', 401, 'The requested URL returned error: 401'));
		Config::set($this->credentials->user, 'wrong password', Config::ENV_SANDBOX);
		$data = $this->fixture('wpf_payment_request_simple.json');
		$payment = Payment::wpf($data);
	}

	function testCrapyData() {
		$this->expectException('Hypercharge\Errors\ValidationError');
		$data = array('foo' => 'bar');
		$payment = Payment::wpf($data);
	}

	// TODO test more exception reasons

	function testWpfCreate() {
		$data = $this->fixture('wpf_payment_request_simple.json');
		$payment = Payment::wpf($data);
		$this->assertIsA($payment, 'Hypercharge\Payment');
		$this->assertTrue($payment->isNew(), 'isNew %s '. print_r($payment, true));
		$this->assertTrue($payment->shouldRedirect());
		$this->assertEqual('WpfPayment', $payment->type);
		$this->assertPattern('/^[0-9a-f]{32}$/', $payment->unique_id);
		$this->assertEqual('5000', $payment->amount);
		$this->assertEqual('EUR', $payment->currency);
		$this->assertNull($payment->error);
		$this->assertEqual('new', $payment->status);
		$this->assertEqual($this->credentials->paymentHost.'/pay/step1/'.$payment->unique_id, $payment->redirect_url);
		sort($payment->payment_methods);
		$this->assertEqual(sort($payment->payment_methods), $this->expected_payment_methods, 'paymet_methods %s actual: '.print_r($payment->payment_methods, true) ."\nexpected: ". print_r($this->expected_payment_methods, true));
		$this->assertPattern('/^wev238f328nc---[0-9a-f]{13}$/', $payment->transaction_id);
		$o = Helper::extractRandomId($payment->transaction_id);
		$this->assertEqual($o->transaction_id, 'wev238f328nc');
		$this->assertPattern('/^[0-9a-f]{13}$/', $o->random_id);
	}

	function testMobileCreate() {
		$data = $this->fixture('mobile_payment_request_simple.json');
		$payment = Payment::mobile($data);
		$this->assertIsA($payment, 'Hypercharge\Payment');
		$this->assertTrue($payment->isNew(), 'isNew %s');
		$this->assertTrue($payment->shouldContinueInMobileApp());
		$this->assertEqual('MobilePayment', $payment->type);
		$this->assertPattern('/^[0-9a-f]{32}$/', $payment->unique_id);
		$this->assertEqual('5000', $payment->amount);
		$this->assertEqual('EUR', $payment->currency);
		$this->assertNull($payment->error);
		$this->assertEqual('new', $payment->status);
		$this->assertEqual($this->credentials->paymentHost.'/mobile/submit/'.$payment->unique_id, $payment->redirect_url);
		$this->assertEqual($payment->redirect_url, $payment->submit_url);
		$this->assertEqual($this->credentials->paymentHost.'/mobile/cancel/'.$payment->unique_id, $payment->cancel_url);
		sort($payment->payment_methods);
		$this->assertEqual($payment->payment_methods, $this->expected_payment_methods, 'paymet_methods %s  '.print_r($payment->payment_methods, true) ."\nexpected: ". print_r($this->expected_payment_methods, true));
		$this->assertPattern('/^wev238f328nc---[0-9a-f]{13}$/', $payment->transaction_id);
		$o = Helper::extractRandomId($payment->transaction_id);
		$this->assertEqual($o->transaction_id, 'wev238f328nc');
		$this->assertPattern('/^[0-9a-f]{13}$/', $o->random_id);
	}

	function testWpfRemoteValidationError() {
		$data = $this->fixture('wpf_payment_request_simple.json');
		unset($data['billing_address']);
		$payment = Payment::wpf($data);
		$this->assertIsA($payment, 'Hypercharge\Payment');
		$this->assertTrue($payment->isError(), 'isError %s');
		$this->assertIsA($payment->error, 'Hypercharge\Errors\Error');
		$this->assertEqual($payment->error->technical_message, "'billing_address' is missing!");
		$this->assertEqual($payment->error->message, 'Please check input data for errors!');
		$this->assertEqual($payment->error->status_code, '320');
		$this->assertEqual($payment->amount, '5000');
		$this->assertEqual($payment->currency, 'EUR');
		$this->assertPattern('/^[0-9a-f]{32}$/', $payment->unique_id);
		$this->assertEqual($payment->type, 'WpfPayment');
		$this->assertEqual($payment->status, 'error');
		$this->assertPattern('/^wev238f328nc---[0-9a-f]{13}$/', $payment->transaction_id);
		$o = Helper::extractRandomId($payment->transaction_id);
		$this->assertEqual($o->transaction_id, 'wev238f328nc');
		$this->assertPattern('/^[0-9a-f]{13}$/', $o->random_id);
	}

	function testMobileRemoteValidationError() {
		$data = $this->fixture('mobile_payment_request_simple.json');
		unset($data['billing_address']);
		$payment = Payment::mobile($data);
		$this->assertIsA($payment, 'Hypercharge\Payment');
		$this->assertTrue($payment->isError(), 'isError %s');
		$this->assertIsA($payment->error, 'Hypercharge\Errors\Error');
		$this->assertEqual($payment->error->technical_message, "'billing_address' is missing!");
		$this->assertEqual($payment->error->message, 'Please check input data for errors!');
		$this->assertEqual($payment->error->status_code, '320');
		$this->assertEqual($payment->amount, '5000');
		$this->assertEqual($payment->currency, 'EUR');
		$this->assertPattern('/^[0-9a-f]{32}$/', $payment->unique_id);
		$this->assertEqual($payment->type, 'MobilePayment');
		$this->assertEqual($payment->status, 'error');
		$this->assertPattern('/^wev238f328nc---[0-9a-f]{13}$/', $payment->transaction_id);
		$o = Helper::extractRandomId($payment->transaction_id);
		$this->assertEqual($o->transaction_id, 'wev238f328nc');
		$this->assertPattern('/^[0-9a-f]{13}$/', $o->random_id);
	}

	function testWpfCancel() {
		$data = $this->fixture('wpf_payment_request_simple.json');
		$payment = Payment::wpf($data);
		$this->assertIsA($payment, 'Hypercharge\Payment');
		$this->assertTrue($payment->isNew());
		$this->assertPattern('/^[0-9a-f]{32}$/', $payment->unique_id);
		$response = Payment::cancel($payment->unique_id);
		$this->assertIsA($response, 'Hypercharge\Payment');
		$this->assertTrue($response->isCanceled());
		$this->assertEqual($response->unique_id, $payment->unique_id);
		$this->assertNull($response->error);
		$o = Helper::extractRandomId($payment->transaction_id);
		$this->assertEqual($response->transaction_id, $data['transaction_id'].'---'.$o->random_id);
		$this->assertEqual($response->amount  , $data['amount']);
		$this->assertEqual($response->currency, $data['currency']);
		$this->assertFalse(empty($response->timestamp));
	}

	function testMobileCancel() {
		$data = $this->fixture('mobile_payment_request_simple.json');
		$payment = Payment::mobile($data);
		$this->assertIsA($payment, 'Hypercharge\Payment');
		$this->assertTrue($payment->isNew());
		$this->assertPattern('/^[0-9a-f]{32}$/', $payment->unique_id);
		$response = Payment::cancel($payment->unique_id);
		$this->assertIsA($response, 'Hypercharge\Payment');
		$this->assertTrue($response->isCanceled());
		$this->assertEqual($response->unique_id, $payment->unique_id);
		$this->assertNull($response->error);
		$o = Helper::extractRandomId($payment->transaction_id);
		$this->assertEqual($response->transaction_id, $data['transaction_id'].'---'.$o->random_id);
		$this->assertEqual($response->amount  , $data['amount']);
		$this->assertEqual($response->currency, $data['currency']);
		$this->assertFalse(empty($response->timestamp));
	}

	function testCancelWithWrongId() {
		$response = Payment::cancel('2dba69127788f34b5fde7e09128b74ed'); // hex 32
		$this->assertIsA($response, 'Hypercharge\Payment');
		$this->assertTrue($response->isError());
		$this->assertNull($response->unique_id);
		$this->assertNull(@$response->amount);
		$this->assertNull(@$response->currency);
		$this->assertTrue($response->isFatalError());
		$error = $response->error;
		$this->assertIsA($error, 'Hypercharge\Errors\WorkflowError');
		$this->assertEqual($error->status_code, 400);
		$this->assertEqual($error->technical_message, 'payment not found.');
		$this->assertEqual($error->message, 'Something went wrong, please contact support!');
	}

	function testMobileFind() {
		$data = $this->fixture('mobile_payment_request_simple.json');
		$payment = Payment::mobile($data);
		$this->assertIsA($payment, 'Hypercharge\Payment');
		$this->assertTrue($payment->isNew());
		$this->assertPattern('/^[0-9a-f]{32}$/', $payment->unique_id);
		$response = Payment::find($payment->unique_id);
		$this->assertIsA($response, 'Hypercharge\Payment');
		$this->assertTrue($response->isNew());
		$this->assertEqual($response->unique_id, $payment->unique_id);
		$this->assertNull($response->error);
		$o = Helper::extractRandomId($payment->transaction_id);
		$this->assertEqual($response->transaction_id, $data['transaction_id'].'---'.$o->random_id);
		$this->assertEqual($response->amount  , $data['amount']);
		$this->assertEqual($response->currency, $data['currency']);
		$this->assertFalse(empty($response->submit_url));
		$this->assertFalse(empty($response->cancel_url));
		$this->assertFalse(empty($response->timestamp));
		sort($response->payment_methods);
		$this->assertEqual($response->payment_methods, $this->expected_payment_methods, 'payment_methods %s');
	}

	function testMobileFindWithWrongId() {
		$data = $this->fixture('mobile_payment_request_simple.json');
		$payment = Payment::mobile($data);
		$this->assertIsA($payment, 'Hypercharge\Payment');
		$this->assertTrue($payment->isNew());
		$this->assertPattern('/^[0-9a-f]{32}$/', $payment->unique_id);
		$response = Payment::find('000111222333444555666777888999ab'); // wrong ID
		$this->assertIsA($response, 'Hypercharge\Payment');
		$this->assertTrue($response->isFatalError());
		$error = $response->error;
		// check the fixture fields correspond to gateway response
		$fixture = $this->parseXml($this->schemaResponse('WpfPayment_error_400.xml'));
		$this->assertIsA($error, 'Hypercharge\Errors\WorkflowError');
		$this->assertEqual($error->status_code, 400);
		$this->assertEqual($error->status_code, $fixture['payment']['code']);
		$this->assertEqual($error->message, $fixture['payment']['message']);
		$this->assertEqual($error->technical_message, $fixture['payment']['technical_message']);
	}

	function testMobileSubmit() {
		$data = $this->fixture('mobile_payment_request_simple.json');
		// create MobilePayment
		$payment = Payment::mobile($data);
		$this->assertIsA($payment, 'Hypercharge\Payment');
		$this->assertTrue($payment->isNew());
		$this->assertPattern('/^[0-9a-f]{32}$/', $payment->unique_id);

		// mobile client submit
		$wpf = new XmlWebservice();
		$submitResponse = $wpf->call(new MobileSubmitUrl($payment->submit_url), new MobileSubmitRequest());
		$this->assertIsA($submitResponse, 'Hypercharge\Payment');
		$this->assertEqual($submitResponse->type, 'MobilePayment');
		$this->assertEqual($submitResponse->status, 'approved', 'status %s , error:'.$submitResponse->error);
		$this->assertTrue($submitResponse->isApproved());
		$this->assertEqual($submitResponse->unique_id, $payment->unique_id);
		$this->assertEqual($submitResponse->transaction_id, $payment->transaction_id);
		//$this->assertEqual($submitResponse->mode, 'test');
		$this->assertEqual($submitResponse->amount  , $data['amount']);
		$this->assertEqual($submitResponse->currency, $data['currency']);
		return $payment;
	}


	function testMobileSubmitAuthorize() {
		$data = $this->fixture('mobile_payment_request_simple.json');
		$data['transaction_types'][] = 'authorize';
		// create MobilePayment for authorize
		$payment = Payment::mobile($data);
		$this->assertIsA($payment, 'Hypercharge\Payment');
		$this->assertTrue($payment->isNew());
		$this->assertPattern('/^[0-9a-f]{32}$/', $payment->unique_id);
		$this->assertEqual($payment->payment_methods, array('credit_card'));

		// mobile client submit
		$wpf = new XmlWebservice();
		$submitResponse = $wpf->call(new MobileSubmitUrl($payment->submit_url), new MobileSubmitRequest());
		$this->assertIsA($submitResponse, 'Hypercharge\Payment');
		$this->assertEqual($submitResponse->type, 'MobilePayment');
		$this->assertEqual($submitResponse->status, 'approved', 'status %s , error:'.$submitResponse->error);
		$this->assertTrue($submitResponse->isApproved());
		$this->assertEqual($submitResponse->unique_id, $payment->unique_id);
		$this->assertEqual($submitResponse->transaction_id, $payment->transaction_id);
		//$this->assertEqual($submitResponse->mode, 'test');
		$this->assertEqual($submitResponse->amount  , $data['amount']);
		$this->assertEqual($submitResponse->currency, $data['currency']);

		return $payment;
	}

	function testMobileVoid() {

		$payment = $this->testMobileSubmit();

		$response = Payment::void($payment->unique_id);
		$this->assertNull($response->error, "error %s . error:\n".$response->error);
		$this->assertIsA($response, 'Hypercharge\Payment');
		$this->assertEqual($response->type, 'MobilePayment');
		$this->assertEqual($response->status, 'voided', 'status %s');
		$this->assertTrue($response->isVoided(), 'isVoided() %s');
		$this->assertEqual($response->unique_id, $payment->unique_id, 'unique_id %s');
		$this->assertEqual($response->transaction_id, $payment->transaction_id);
		$this->assertFalse(empty($response->timestamp));
		//$this->assertEqual($response->descriptor, 'sankyu.com/bogus +49123456789');

		$this->assertIsA($response->transactions, 'array');
		$this->assertIsA($response->transactions[0], 'Hypercharge\Transaction');
		$trx = $response->transactions[0];
		$this->assertEqual($trx->getType(), 'sale');
		$this->assertTrue($trx->isVoided());
		$this->assertEqual($trx->amount        , $payment->amount);
		$this->assertEqual($trx->currency      , $payment->currency);
		$this->assertEqual($trx->transaction_id, $payment->transaction_id);
	}


	function testMobileCapture() {

		$payment = $this->testMobileSubmitAuthorize();

		$response = Payment::capture($payment->unique_id);
		$this->assertNull($response->error, "error %s . error:\n".$response->error);
		$this->assertIsA($response, 'Hypercharge\Payment');
		$this->assertEqual($response->type, 'MobilePayment');
		$this->assertEqual($response->status, 'captured', 'status %s');
		$this->assertTrue($response->isCaptured(), 'isCaptured() %s');
		$this->assertEqual($response->unique_id, $payment->unique_id, 'unique_id %s');
		$this->assertEqual($response->transaction_id, $payment->transaction_id);
		$this->assertFalse(empty($response->timestamp));
		//$this->assertEqual($response->descriptor, 'sankyu.com/bogus +49123456789');

		$this->assertIsA($response->transactions, 'array');
		$this->assertIsA($response->transactions[0], 'Hypercharge\Transaction');
		$trx = $response->transactions[0];
		$this->assertEqual($trx->getType(), 'authorize');
		$this->assertTrue($trx->isCaptured());
		$this->assertEqual($trx->amount        , $payment->amount);
		$this->assertEqual($trx->currency      , $payment->currency);
		$this->assertEqual($trx->transaction_id, $payment->transaction_id);
	}

	function testMobileRefund() {

		$payment = $this->testMobileSubmit();

		$response = Payment::refund($payment->unique_id);
		$this->assertNull($response->error, "error %s . error:\n".$response->error);
		$this->assertIsA($response, 'Hypercharge\Payment');
		$this->assertEqual($response->type, 'MobilePayment');
		$this->assertEqual($response->status, 'refunded', 'status %s');
		$this->assertTrue($response->isRefunded(), 'isRefunded() %s');
		$this->assertEqual($response->unique_id, $payment->unique_id, 'unique_id %s');
		$this->assertEqual($response->transaction_id, $payment->transaction_id);
		$this->assertFalse(empty($response->timestamp));
		//$this->assertEqual($response->descriptor, 'sankyu.com/bogus +49123456789');

		$this->assertIsA($response->transactions, 'array');
		$this->assertIsA($response->transactions[0], 'Hypercharge\Transaction');
		$trx = $response->transactions[0];
		$this->assertEqual($trx->getType(), 'sale');
		$this->assertTrue($trx->isRefunded());
		$this->assertEqual($trx->amount        , $payment->amount);
		$this->assertEqual($trx->currency      , $payment->currency);
		$this->assertEqual($trx->transaction_id, $payment->transaction_id);
	}

}

class MobileSubmitRequest implements IRequest {

	function __construct($data = array()) {
		$future = new \DateTime('now', new \DateTimeZone('UTC'));
		$future->add(new \DateInterval('P1Y'));

		$default = array(
			'payment_method' => 'credit_card'
			,'card_holder' => 'Pierre Partout'
			,'card_number' => '4200000000000000'
			,'cvv' => '667'
			,'expiration_year'  => $future->format('Y')
			,'expiration_month' => $future->format('m')
		);
		$data = array_merge($default, $data);
		Helper::assign($this, $data);
	}

	function getRootName() {
		return 'payment';
	}

	function getType() {
		// dummy
		return 'mobile submit';
	}

	/**
	* @throws Hypercharge\Errors\ValidationError
	* @return void
	*/
	function validate() {
		// do nothing
	}

	function createResponse($data) {
		return new Payment($data['payment']);
	}
}

class MobileSubmitUrl implements IUrl {
	function __construct($url) {
		$this->url = $url;
	}
	function get() {
		return $this->url;
	}
}




