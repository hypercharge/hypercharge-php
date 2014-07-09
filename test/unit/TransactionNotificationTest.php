<?php
namespace Hypercharge;

require_once dirname(__DIR__).'/test_helper.php';

class TransactionNotificationTest extends HyperchargeTestCase {

	/**
	* example 1 from Hypercharge API doc
	* 4.2 Notifications -> "Notification signature examples"
	* unique_id  : fc6c3c8c0219730c7a099eaa540f70dc
	* api passord: bogus
	* signature  : 08d01ae1ebdc22b6a1a764257819bb26e9e94e8d
	*/
	function testVerifyValidSignatureBogus() {
		$postData = $this->schemaNotification('transaction_notification.json');
		$tn = new TransactionNotification($postData);
		$apiPassword = 'bogus';
		$tn->verify($apiPassword);
		$this->assertTrue($tn->isVerified());
	}

	/**
	* example 2 from Hypercharge API doc
	* 4.2 Notifications -> "Notification signature examples"
	* unique_id  : 130319cfb3bf65ff3c4a4045487b173e
	* api passord: test123
	* signature  : 1b34dabed996788efcc049567809484454ee8b17
	*/
	function testVerifyValidSignatureTest123() {
		$postData = $this->schemaNotification('transaction_notification.json');
		$postData['unique_id'] = '130319cfb3bf65ff3c4a4045487b173e';
		$postData['signature'] = '1b34dabed996788efcc049567809484454ee8b17';
		$tn = new TransactionNotification($postData);
		$apiPassword = 'test123';
		$tn->verify($apiPassword);
		$this->assertTrue($tn->isVerified());
	}

	function testVerifyInvalidSignatureTest123() {
		$postData = $this->schemaNotification('transaction_notification.json');
		$postData['unique_id'] = '130319cfb3bf65ff3c4a4045487b173e';
		$postData['signature'] = 'wrongabed996788efcc049567809484454ee8b17';
		$tn = new TransactionNotification($postData);
		$apiPassword = 'test123';
		$tn->verify($apiPassword);
		$this->assertFalse($tn->isVerified());
	}

	function testConstructorEmptyParamShouldThrowException() {
		$this->expectException(new \PatternExpectation("/Missing or empty argument 1/i"));
		$tn = new TransactionNotification(array());
	}

	function testConstructorVerifyNullParamNotificationShouldThrowException() {
		$this->expectException(new \PatternExpectation("/Missing or empty argument 1/i"));
		$tn = new TransactionNotification(null);
	}

	function testAck() {
		$postData = $this->schemaNotification('transaction_notification.json');
		$tn = new TransactionNotification($postData);
		$this->assertEqual($this->schemaNotification('transaction_notification_ack.xml'), $tn->ack());
	}

	function testIsApproved() {
		$postData = $this->schemaNotification('transaction_notification.json');
		$tn = new TransactionNotification($postData);
		$this->assertTrue($tn->isApproved());

		$postData['status'] = Transaction::STATUS_DECLINED;
		$tn = new TransactionNotification($postData);
		$this->assertEqual($tn->status, 'declined');
		$this->assertFalse($tn->isApproved());

		$postData['status'] = Transaction::STATUS_ERROR;
		$tn = new TransactionNotification($postData);
		$this->assertEqual($tn->status, 'error');
		$this->assertFalse($tn->isApproved());
	}

	function testHasTransaction() {
		$postData = $this->schemaNotification('transaction_notification.json');
		$tn = new TransactionNotification($postData);
		$this->assertTrue($tn->hasTransaction());
	}
	function testMissingUniqueIdCausesHasNotTransaction() {
		$postData = $this->schemaNotification('transaction_notification.json');
		unset($postData['unique_id']);
		$tn = new TransactionNotification($postData);
		$this->assertFalse($tn->hasTransaction());
	}
	function testMissingChannelCausesHasNotTransaction() {
		$postData = $this->schemaNotification('transaction_notification.json');
		unset($postData['channel_token']);
		$tn = new TransactionNotification($postData);
		$this->assertFalse($tn->hasTransaction());
	}
	function testMissingTransactionTypeCausesHasNotTransaction() {
		$postData = $this->schemaNotification('transaction_notification.json');
		unset($postData['transaction_type']);
		$tn = new TransactionNotification($postData);
		$this->assertFalse($tn->hasTransaction());
	}

	function testGetTransaction() {
		XmlSerializer::$sort = false;

		// prepare post, request, response fixtures
		$postData = $this->schemaNotification('transaction_notification.json');
		$channel_token = $postData['channel_token'];
		$uid           = $postData['unique_id'];

		$requestXml  = $this->schemaRequest( 'reconcile.xml');
		$requestXml = preg_replace('/<unique_id>[^<]+<\/unique_id>/', "<unique_id>$uid</unique_id>", $requestXml);

		$responseXml = $this->schemaResponse('reconcile.xml');
		$responseXml = preg_replace('/<unique_id>[^<]+<\/unique_id>/', "<unique_id>$uid</unique_id>", $responseXml);


		$this->curlMock()
			->shouldReceive('xmlPost')
			->with("https://test.hypercharge.net/reconcile/$channel_token", $requestXml)
			->once()
			->andReturn($responseXml);


		// actually test the notification
		$tn = new TransactionNotification($postData);
		$this->assertTrue($tn->hasTransaction());

		$trx = $tn->getTransaction();

		$this->assertIsA($trx, 'Hypercharge\Transaction');
		$this->assertTrue($trx->isApproved());
		$this->assertEqual($trx->unique_id, $uid);
	}
}