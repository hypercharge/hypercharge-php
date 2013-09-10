<?php
namespace Hypercharge;

require_once dirname(__DIR__).'/test_helper.php';

class PaymentNotificationTest extends HyperchargeTestCase {

	/**
	* example 1 from Hypercharge API doc
	* 4.2 Notifications -> "Notification signature examples"
	* unique_id  : 26aa150ee68b1b2d6758a0e6c44fce4c
	* api passord: b5af4c9cf497662e00b78550fd87e65eb415f42f
	* signature  : 3d82fef85cb60...
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
	* unique_id  : 3f760162ef57...
	* api passord: 50fd87e65eb4...
	* signature  : 14519d0db2f7...
	*/
	function testVerifyValidSignatureTest123() {
		$postData = $this->schemaNotification('payment_notification_with_transaction.json');
		$postData['payment_unique_id'] = '3f760162ef57a829011e5e2379b3fa17';
		$postData['signature'] = '14519d0db2f7f8f407efccc9b099c5303f55c0262e3b9132e5bcc97f7febf5f9ab19df03929c1ead271be79807b4086321a023743d2b6b1278c2082b61cf3ff0';
		$notification = new PaymentNotification($postData);
		$this->assertEqual($notification->getPayment()->unique_id, '3f760162ef57a829011e5e2379b3fa17');
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
		$notification = new PaymentNotification($postData);

		$payment = $notification->getPayment();
		$this->assertIsA($payment, 'stdClass');
		$this->assertEqual($payment->type, 'WpfPayment');
		$this->assertEqual($payment->unique_id, '26aa150ee68b1b2d6758a0e6c44fce4c');
		$this->assertEqual($payment->status, 'approved');
		$this->assertEqual($payment->transaction_id, 'the-id-provided-by-merchant-aadfasdfadf');

		$this->assertTrue($notification->hasTransaction());
		$trx = $notification->getTransaction();
		$this->assertIsA($trx, 'stdClass');
		$this->assertEqual($trx->transaction_type, 'sale');
		$this->assertEqual($trx->unique_id, 'bad08183a9ec545daf0f24c48361aa10');
		$this->assertEqual($trx->channel_token, 'the-channel-token-o234uouizeiz2492834792');

		$this->assertFalse($notification->hasSchedule());
		$this->assertEqual($notification->getSchedule(), null);
	}

	function testGetXWithSchedule() {
		$postData = $this->schemaNotification('payment_notification_with_schedule.json');
		$notification = new PaymentNotification($postData);

		$payment = $notification->getPayment();
		$this->assertIsA($payment, 'stdClass');
		$this->assertEqual($payment->type, 'WpfPayment');
		$this->assertEqual($payment->unique_id, '26aa150ee68b1b2d6758a0e6c44fce4c');
		$this->assertEqual($payment->status, 'approved');
		$this->assertEqual($payment->transaction_id, 'the-id-provided-by-merchant-aadfasdfadf');

		$this->assertFalse($notification->hasTransaction());
		$this->assertEqual($notification->getTransaction(), null);

		$this->assertTrue($notification->hasSchedule());
		$schedule = $notification->getSchedule();
		$this->assertIsA($schedule, 'stdClass');
		$this->assertEqual($schedule->unique_id, 'bad08183a9ec545daf0f24c48361aa10');
		$this->assertEqual($schedule->end_date, '2013-01-21 00:00:00');
	}

	function testGetX() {
		$postData = $this->schemaNotification('payment_notification.json');
		$notification = new PaymentNotification($postData);

		$payment = $notification->getPayment();
		$this->assertIsA($payment, 'stdClass');
		$this->assertEqual($payment->type, 'WpfPayment');
		$this->assertEqual($payment->unique_id, '26aa150ee68b1b2d6758a0e6c44fce4c');
		$this->assertEqual($payment->status, 'canceled');
		$this->assertEqual($payment->transaction_id, 'the-id-provided-by-merchant-aadfasdfadf');

		$this->assertFalse($notification->hasTransaction());
		$this->assertEqual($notification->getTransaction(), null);

		$this->assertFalse($notification->hasSchedule());
		$this->assertEqual($notification->getSchedule(), null);
	}
}