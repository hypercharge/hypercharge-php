<?php
namespace Hypercharge;

require_once dirname(__DIR__).'/test_helper.php';

use \Mockery as m;

class PaymentNotificationTest extends HyperchargeTestCase {

	function setUp() {
		Config::setIdSeparator(false);
    XmlSerializer::$sort = false;

		$this->curl = $curl = m::mock('Curl');
		$factory = m::mock(new Factory());
		$factory
			->shouldReceive('createHttpsClient')
			->with('the user', 'the passw')
			->andReturn($curl);

		// Scheduler calls without params - so defaults are used
		$factory
			->shouldReceive('createHttpsClient')
			->andReturn($curl);

		Config::setFactory($factory);
		Config::set('the user', 'the passw', Config::ENV_SANDBOX);
	}

	function tearDown() {
		m::close();
		Config::setFactory(new Factory);
	}

	/**
	* example 1 from Hypercharge API doc
	* 4.2 Notifications -> "Notification signature examples"
	* unique_id   : 26aa150ee68b1b2d6758a0e6c44fce4c
	* api password: b5af4c9cf497662e00b78550fd87e65eb415f42f
	* signature   : 3d82fef85cb60...
	*/
	function testVerifyValidSignatureBogus() {
		$postData = $this->schemaNotification('payment_notification_with_transaction.json');
		$notification = new PaymentNotification($postData);
		$apiPassword = 'b5af4c9cf497662e00b78550fd87e65eb415f42f';
		$notification->verify($apiPassword);
		$this->assertTrue($notification->isVerified());
	}

	/**
	* example 2 from Hypercharge API doc
	* 6.2.3 Notification -> "Notification signature examples"
	* unique_id   : 3f760162ef57...
	* api password: 50fd87e65eb4...
	* signature   : 14519d0db2f7...
	*/
	function testVerifyValidSignatureTest123() {
		$postData = $this->schemaNotification('payment_notification_with_transaction.json');
		$postData['payment_unique_id'] = '3f760162ef57a829011e5e2379b3fa17';
		$postData['signature'] = '14519d0db2f7f8f407efccc9b099c5303f55c0262e3b9132e5bcc97f7febf5f9ab19df03929c1ead271be79807b4086321a023743d2b6b1278c2082b61cf3ff0';
		$notification = new PaymentNotification($postData);
		$this->assertEqual($notification->payment_unique_id, '3f760162ef57a829011e5e2379b3fa17');
		$apiPassword = '50fd87e65eb415f42fb5af4c9cf497662e00b785';
		$notification->verify($apiPassword);
		$this->assertTrue($notification->isVerified());
	}

	function testConstructorEmptyParamShouldThrowException() {
		$this->expectException(new \PatternExpectation("/Missing or empty argument 1/i"));
		$notification = new PaymentNotification(array());
	}

	function testConstructorVerifyNullParamNotificationShouldThrowException() {
		$this->expectException(new \PatternExpectation("/Missing or empty argument 1/i"));
		$notification = new PaymentNotification(null);
	}

	function testAck() {
		$postData = $this->schemaNotification('payment_notification.json');
		$notification = new PaymentNotification($postData);
		$this->assertEqual($this->schemaNotification('payment_notification_ack.xml'), $notification->ack());
	}

	function testAckWithTransaction() {
		$postData = $this->schemaNotification('payment_notification_with_transaction.json');
		$notification = new PaymentNotification($postData);
		$this->assertEqual($this->schemaNotification('payment_notification_ack.xml'), $notification->ack());
	}

	function testAckWithSchedule() {
		$postData = $this->schemaNotification('payment_notification_with_schedule.json');
		$notification = new PaymentNotification($postData);
		$this->assertEqual($this->schemaNotification('payment_notification_ack.xml'), $notification->ack());
	}

	function testIsApproved() {
		$postData = $this->schemaNotification('payment_notification_with_transaction.json');
		$notification = new PaymentNotification($postData);
		$this->assertTrue($notification->isApproved());

		$postData['payment_status'] = Payment::STATUS_DECLINED;
		$notification = new PaymentNotification($postData);
		$this->assertEqual($notification->payment_status, 'declined');
		$this->assertFalse($notification->isApproved());

		$postData['payment_status'] = Payment::STATUS_ERROR;
		$notification = new PaymentNotification($postData);
		$this->assertEqual($notification->payment_status, 'error');
		$this->assertFalse($notification->isApproved());
	}

	function testIsApprovedWithStatusCanceled() {
		$postData = $this->schemaNotification('payment_notification.json');
		$notification = new PaymentNotification($postData);
		$this->assertEqual($notification->payment_status, Payment::STATUS_CANCELED);
		$this->assertFalse($notification->isApproved());
	}

	function testGetXWithTransaction() {
		$postData = $this->schemaNotification('payment_notification_with_transaction.json');
		$paymentId = $postData['payment_unique_id'];
		$postData['payment_transaction_unique_id'] = '5e2cbbad71d2b1343232abc3c208223a';
		$channelToken = $postData['payment_transaction_channel_token'];
		$notification = new PaymentNotification($postData);
		$notification->verify('b5af4c9cf497662e00b78550fd87e65eb415f42f');
		$this->assertTrue($notification->isVerified());

		$request  = $this->schemaRequest('reconcile.xml');
		$response = $this->schemaResponse('WpfPayment_find.xml');
		$request  = preg_replace('/<unique_id>[0-9a-f]+<\/unique_id>/', "<unique_id>$paymentId</unique_id>", $request);
		$response = preg_replace('/<unique_id>[0-9a-f]+<\/unique_id>/', "<unique_id>$paymentId</unique_id>", $response);

		$this->curl
			->shouldReceive('xmlPost')
			->with('https://testpayment.hypercharge.net/payment/reconcile', $request)
			->andReturn($response);

		$payment = $notification->getPayment();
		$this->assertIsA($payment, 'Hypercharge\Payment');
		$this->assertEqual($payment->unique_id, $paymentId);
		$this->assertEqual($payment->status, 'approved');
		$this->assertEqual($payment->transaction_id, '0AF671AF-4134-4BE7-BDF0-26E38B74106E---d8981080a4f701303cf4542696cde09d');

		$this->assertTrue($notification->hasTransaction());

		$request  = $this->schemaRequest('reconcile.xml');
		$response = $this->schemaResponse('sale.xml');
		$request = preg_replace('/<unique_id>[0-9a-f]+<\/unique_id>/', '<unique_id>5e2cbbad71d2b1343232abc3c208223a</unique_id>', $request);
		$this->curl
			->shouldReceive('xmlPost')
			->with('https://test.hypercharge.net/reconcile/'.$channelToken, $request)
			->andReturn($response);

		$trx = $notification->getTransaction();
		$this->assertIsA($trx, 'Hypercharge\Transaction');
		$this->assertEqual($trx->getType(), 'sale');
		$this->assertTrue($trx->isApproved());
		$this->assertEqual($trx->amount, 5000);
		$this->assertEqual($trx->currency, 'USD');
		$this->assertEqual($trx->unique_id, '5e2cbbad71d2b1343232abc3c208223a');

		$this->assertFalse($notification->hasSchedule());
		$this->assertEqual($notification->getSchedule(), null);
	}

	function testGetXWithSchedule() {
		$postData = $this->schemaNotification('payment_notification_with_schedule.json');
		$paymentId = $postData['payment_unique_id'];
		$scheduleId = $postData['schedule_unique_id'];
		$notification = new PaymentNotification($postData);
		$notification->verify('b5af4c9cf497662e00b78550fd87e65eb415f42f');
		$this->assertTrue($notification->isVerified());


		$request  = $this->schemaRequest('reconcile.xml');
		$response = $this->schemaResponse('WpfPayment_find.xml');
		$request  = preg_replace('/<unique_id>[0-9a-f]+<\/unique_id>/', "<unique_id>$paymentId</unique_id>", $request);
		$response = preg_replace('/<unique_id>[0-9a-f]+<\/unique_id>/', "<unique_id>$paymentId</unique_id>", $response);

		$this->curl
			->shouldReceive('xmlPost')
			->with('https://testpayment.hypercharge.net/payment/reconcile', $request)
			->andReturn($response);

		$payment = $notification->getPayment();
		$this->assertIsA($payment, 'Hypercharge\Payment');
		$this->assertEqual($payment->unique_id, $paymentId);
		$this->assertEqual($payment->status, 'approved');
		$this->assertEqual($payment->transaction_id, '0AF671AF-4134-4BE7-BDF0-26E38B74106E---d8981080a4f701303cf4542696cde09d');

		$this->assertFalse($notification->hasTransaction());
		$this->assertEqual($notification->getTransaction(), null);

		$this->assertTrue($notification->hasSchedule());

		$response = $this->schemaResponse('scheduler.json');
		$response['unique_id'] = $scheduleId;
		$this->expect_Curl_jsonRequest()
			->with('GET', 'https://test.hypercharge.net/v2/scheduler/'.$scheduleId)
			->andReturn($response);

		$schedule = $notification->getSchedule();
		$this->assertIsA($schedule, 'Hypercharge\Scheduler');
		$this->assertEqual($schedule->type, 'DateRecurringSchedule');
		$this->assertEqual($schedule->unique_id, $scheduleId);
		$this->assertEqual($schedule->start_date, '2013-11-13');
		$this->assertEqual($schedule->end_date, '2014-06-30');
		$this->assertEqual($schedule->interval, 'monthly');
	}

	function testGetXPaymentOnly() {
		$postData = $this->schemaNotification('payment_notification.json');
		$paymentId = $postData['payment_unique_id'];

		$notification = new PaymentNotification($postData);
		$notification->verify('b5af4c9cf497662e00b78550fd87e65eb415f42f');
		$this->assertTrue($notification->isVerified());

		$request  = $this->schemaRequest('reconcile.xml');
		$response = $this->schemaResponse('WpfPayment_find.xml');
		$request  = preg_replace('/<unique_id>[0-9a-f]+<\/unique_id>/', "<unique_id>$paymentId</unique_id>", $request);
		$response = preg_replace('/<unique_id>[0-9a-f]+<\/unique_id>/', "<unique_id>$paymentId</unique_id>", $response);

		$this->curl
			->shouldReceive('xmlPost')
			->with('https://testpayment.hypercharge.net/payment/reconcile', $request)
			->andReturn($response);

		$payment = $notification->getPayment();
		$this->assertIsA($payment, 'Hypercharge\Payment');
		$this->assertEqual($payment->unique_id, $paymentId);
		$this->assertEqual($payment->status, 'approved');
		$this->assertEqual($payment->transaction_id, '0AF671AF-4134-4BE7-BDF0-26E38B74106E---d8981080a4f701303cf4542696cde09d');

		$this->assertFalse($notification->hasTransaction());
		$this->assertEqual($notification->getTransaction(), null);

		$this->assertFalse($notification->hasSchedule());
		$this->assertEqual($notification->getSchedule(), null);
	}
}