<?php
namespace Hypercharge;

require_once dirname(__DIR__).'/test_helper.php';
use \Mockery as m;

class TransactionTest extends HyperchargeTestCase {

	function tearDown() {
		m::close();
		Config::setFactory(new Factory());
	}

	function response($xmlFixtureFile) {
		$data = XmlSerializer::dom2hash(new \SimpleXMLElement(JsonSchemaFixture::response($xmlFixtureFile)));
		return $data['payment_response'];
	}

	function testToString() {
		$t = new Transaction($this->response('debit_sale.xml'));
		$this->assertEqual($t.''
			,'Hypercharge\Transaction { type: debit_sale, unique_id: 5e2cbbad71d2b13432323153c208223a, status: approved, transaction_id: 119643250547501c79d8295, timestamp: 2007-11-30T14:21:48Z, error: }');
	}

	function testStatusHelper() {
		$r = $this->response('debit_sale.xml');
		$t = new Transaction($r);
		$this->assertEqual($t->status, Transaction::STATUS_APPROVED);
		$this->assertTrue($t->isApproved());

		$r['status'] = Transaction::STATUS_DECLINED;
		$t = new Transaction($r);
		$this->assertTrue($t->isDeclined());

		$r['status'] = Transaction::STATUS_PENDING;
		$t = new Transaction($r);
		$this->assertTrue($t->isPending());

		$r['status'] = Transaction::STATUS_PENDING_ASYNC;
		$t = new Transaction($r);
		$this->assertTrue($t->isPendingAsync());

		$r['status'] = Transaction::STATUS_ERROR;
		$t = new Transaction($r);
		$this->assertTrue($t->isError());

		$r['status'] = Transaction::STATUS_VOIDED;
		$t = new Transaction($r);
		$this->assertTrue($t->isVoided());

		$r['status'] = Transaction::STATUS_CHARGEBACKED;
		$t = new Transaction($r);
		$this->assertTrue($t->isChargebacked());

		$r['status'] = Transaction::STATUS_REFUNDED;
		$t = new Transaction($r);
		$this->assertTrue($t->isRefunded());

		$r['status'] = Transaction::STATUS_CHARGEBACK_REVERSED;
		$t = new Transaction($r);
		$this->assertTrue($t->isChargebackReversed());

		$r['status'] = Transaction::STATUS_PRE_ARBITRATED;
		$t = new Transaction($r);
		$this->assertTrue($t->isPreArbitrated());

		$r['status'] = Transaction::STATUS_REJECTED;
		$t = new Transaction($r);
		$this->assertTrue($t->isRejected());

		$r['status'] = Transaction::STATUS_CAPTURED;
		$t = new Transaction($r);
		$this->assertTrue($t->isCaptured());
	}

	function testShouldRedirectIfRedirectUrlPresent() {
		$urlPat = '|^https://[^/]+/redirect/to_acquirer/[a-f0-9]{32}$|';
		$r = $this->response('sale3d_async.xml');
		$this->assertPattern($urlPat, $r['redirect_url']);
		$t = new Transaction($r);
		$this->assertTrue($t->shouldRedirect());
		$this->assertPattern($urlPat, $t->redirect_url);
		$this->assertTrue($t->isPendingAsync());
		$this->assertPattern('|^[a-f0-9]{32}$|', $t->unique_id);
	}

	function testShouldNotRedirectIfRedirectUrlMissing() {
		$r = $this->response('sale3d_async.xml');
		unset($r['redirect_url']);
		$t = new Transaction($r);
		$this->assertFalse($t->shouldRedirect());
		$this->assertNull($t->redirect_url);
		$this->assertTrue($t->isPendingAsync());
		$this->assertPattern('|^[a-f0-9]{32}$|', $t->unique_id);
	}

	function testShouldNotRedirectIfRedirectUrlEmpty() {
		$r = $this->response('sale3d_async.xml');
		$r['redirect_url'] = '';
		$t = new Transaction($r);
		$this->assertFalse($t->shouldRedirect());
		$this->assertEqual('', $t->redirect_url);
		$this->assertTrue($t->isPendingAsync());
		$this->assertPattern('|^[a-f0-9]{32}$|', $t->unique_id);
	}

	function testShouldNotRedirectIfNotPersitentInHypercharge() {
		$r = $this->response('sale3d_async.xml');
		$t = m::mock('\Hypercharge\Transaction[isPersistentInHypercharge]', array($r));
		$t->shouldReceive('isPersistentInHypercharge')->andReturn(false);
		$this->assertFalse($t->isPersistentInHypercharge());

		$this->assertFalse($t->shouldRedirect());
		$this->assertTrue($t->redirect_url);
		$this->assertTrue($t->isPendingAsync());
		$this->assertPattern('|^[a-f0-9]{32}$|', $t->unique_id);
	}

	/**
	* hypercharge-schema/test/fixtures/responses $> grep 'redirect_url' test/fixtures/responses/*.xml
	* authorize3d_pending_async.xml:  <redirect_url>https://test.hypercharge.net/redirect/to_acquirer/0d803525dae4c9fd422571a86c6a9a11</redirect_url>
	* direct_pay24_sale.xml:  <redirect_url>https://test.hypercharge.net/redirect/to_acquirer/2e193f8316a460ab2eb0dd935139fb07</redirect_url>
	* giro_pay_sale.xml:  <redirect_url>https://test.hypercharge.net/redirect/to_acquirer/2e193f8316a460ab2eb0dd935139fb06</redirect_url>
	* ideal_sale.xml:  <redirect_url>https://test.hypercharge.net/redirect/to_acquirer/d104089fedb78a34de5f3714208bba9f</redirect_url>
	* pay_pal.xml:  <redirect_url>https://test.hypercharge.net/redirect/to_acquirer/2da6bca931232f84a24fc88a7463e1a9</redirect_url>
	* sale3d_async.xml:  <redirect_url>https://test.hypercharge.net/redirect/to_acquirer/ddda0f68a8f12bfca799e8982ceff276</redirect_url>
	* pay_safe_card_sale.xml:  <redirect_url>https://test.hypercharge.net/redirect/to_acquirer/ddda0f68a8f12bfca799e8982ceff276</redirect_url>
	*/
	function testShouldRedirectTransactionTypes() {
		$t = new Transaction($this->response('authorize3d_pending_async.xml'));
		$this->assertTrue($t->shouldRedirect());
		$this->assertEqual($t->redirect_url, 'https://test.hypercharge.net/redirect/to_acquirer/0d803525dae4c9fd422571a86c6a9a11');

		$t = new Transaction($this->response('direct_pay24_sale.xml'));
		$this->assertTrue($t->shouldRedirect());
		$this->assertEqual($t->redirect_url, 'https://test.hypercharge.net/redirect/to_acquirer/2e193f8316a460ab2eb0dd935139fb07');

		$t = new Transaction($this->response('giro_pay_sale.xml'));
		$this->assertTrue($t->shouldRedirect());
		$this->assertEqual($t->redirect_url, 'https://test.hypercharge.net/redirect/to_acquirer/2e193f8316a460ab2eb0dd935139fb06');

		$t = new Transaction($this->response('ideal_sale.xml'));
		$this->assertTrue($t->shouldRedirect());
		$this->assertEqual($t->redirect_url, 'https://test.hypercharge.net/redirect/to_acquirer/d104089fedb78a34de5f3714208bba9f');

		$t = new Transaction($this->response('pay_pal.xml'));
		$this->assertTrue($t->shouldRedirect());
		$this->assertEqual($t->redirect_url, 'https://test.hypercharge.net/redirect/to_acquirer/2da6bca931232f84a24fc88a7463e1a9');

		$t = new Transaction($this->response('pay_safe_card_sale.xml'));
		$this->assertTrue($t->shouldRedirect());
		$this->assertEqual($t->redirect_url, 'https://test.hypercharge.net/redirect/to_acquirer/ddda0f68a8f12bfca799e8982ceff276');
	}

	function testShouldNotRedirect() {
		$syncResponseFixtures = array(
			'authorize3d_sync.xml'
			,'authorize_approved.xml'
			,'authorize_error.xml'
			,'capture.xml'
			,'create_chargeback.xml'
			,'create_chargeback_reversal.xml'
			,'create_charged_debit_sale.xml'
			,'create_debit_chargeback.xml'
			,'create_deposit.xml'
			,'create_pre_arbitration.xml'
			,'create_rejected_debit_sale.xml'
			,'create_retrieval_request.xml'
			,'debit_sale.xml'
			,'init_recurring_authorize.xml'
			,'init_recurring_debit_authorize.xml'
			,'init_recurring_debit_sale.xml'
			,'init_recurring_sale.xml'
			,'pay_in_advance.xml'
			,'payment_on_delivery.xml'
			,'purchase_on_account.xml'
			,'recurring_debit_sale.xml'
			,'referenced_fund_transfer.xml'
			,'refund.xml'
			,'sale.xml'
			,'sale3d_sync.xml'
			,'void.xml'
		);
		foreach($syncResponseFixtures as $fixture) {
			$t = new Transaction($this->response($fixture));
			$this->assertFalse($t->shouldRedirect(), "$fixture %s");
		}
	}

	function testIsPersistentInHypercharge() {
		$r = $this->response('debit_sale.xml');
		$t = new Transaction($r);
		$this->assertTrue($t->isPersistentInHypercharge());
	}

	function testIsPersistentInHyperchargeWithoutUniqueId() {
		$r = $this->response('debit_sale.xml');
		unset($r['unique_id']);
		$t = new Transaction($r);
		$this->assertFalse($t->isPersistentInHypercharge());
		$this->assertTrue($t->isFatalError());

		$t->unique_id = null;
		$this->assertFalse($t->isPersistentInHypercharge());
		$t->unique_id = '';
		$this->assertFalse($t->isPersistentInHypercharge());
		$t->unique_id = '1234567889';
		$this->assertTrue($t->isPersistentInHypercharge());
	}

	function testIsPersistentInHyperchargeWithoutTransactionId() {
		$r = $this->response('debit_sale.xml');
		unset($r['transaction_id']);
		$t = new Transaction($r);
		$this->assertFalse($t->isPersistentInHypercharge());
		$this->assertTrue($t->isFatalError());

		$t->transaction_id = null;
		$this->assertFalse($t->isPersistentInHypercharge());
		$t->transaction_id = '';
		$this->assertFalse($t->isPersistentInHypercharge());
		$t->transaction_id = '1234567889';
		$this->assertTrue($t->isPersistentInHypercharge());
	}

	function testIsPersistentInHyperchargeWithoutUniqueIdAndTransactionId() {
		$r = $this->response('debit_sale.xml');
		unset($r['unique_id']);
		unset($r['transaction_id']);
		$t = new Transaction($r);
		$this->assertFalse($t->isPersistentInHypercharge());
		$this->assertTrue($t->isFatalError());
	}

	function testEachWithSingleResult() {
		XmlSerializer::$sort = false;

		$data = array('start_date'=>'2013-06-05', 'period'=>'P1D');
		$requestXml = '<?xml version="1.0" encoding="UTF-8"?>
<reconcile>
  <start_date>2013-06-05</start_date>
  <end_date>2013-06-06</end_date>
  <page>1</page>
</reconcile>
';
		$responseXml = $this->schemaResponse('reconcile_by_date_single_result.xml');

		$this->curlMock()
			->shouldReceive('xmlPost')
			->with('https://test.hypercharge.net/reconcile/by_date/CHANNEL_TOKEN', $requestXml)
			->once()
			->andReturn($responseXml);

		$n = 0;
		$_this = $this;
		Transaction::each('CHANNEL_TOKEN', $data, function($trx) use(&$n, $_this) {
			$n++;
			$_this->assertIsA($trx, 'Hypercharge\Transaction');
			$_this->assertEqual($trx->unique_id, '25a1464848387259c63200a99f466e8c');
			$_this->assertTrue($trx->isApproved());
		});
		$this->assertEqual($n, 1);
	}

	function test_callSetsTransactionTypeWithArray() {
		XmlSerializer::$sort = true;
		Config::setLogger(new StdoutLogger());
		$curl = $this->curlMock();

		$requestXml  = $this->schemaRequest('sale.xml');
		$responseXml = $this->schemaResponse('sale.xml');
		$curl
			->shouldReceive('xmlPost')
			->with('https://test.hypercharge.net/process/CHANNEL_TOKEN', $requestXml)
			->once()
			->andReturn($responseXml);

		$data = $this->schemaRequest('sale.json');
		$this->assertIsA($data, 'array');
		$data['payment_transaction']['transaction_type'] = 'foobar';
		$_call = new \ReflectionMethod('Hypercharge\Transaction', '_call');
		$_call->setAccessible(true);
		$_call->invoke(null, 'sale', $data['payment_transaction'], 'CHANNEL_TOKEN');
	}

	function test_callSetsTransactionTypeWithObject() {
		XmlSerializer::$sort = true;
		Config::setLogger(new StdoutLogger());
		$curl = $this->curlMock();

		$requestXml  = $this->schemaRequest('sale.xml');
		$responseXml = $this->schemaResponse('sale.xml');
		$curl
			->shouldReceive('xmlPost')
			->with('https://test.hypercharge.net/process/CHANNEL_TOKEN', $requestXml)
			->once()
			->andReturn($responseXml);

		$data = $this->schemaRequest('sale.json');
		$request = new TransactionRequest($data['payment_transaction']);
		$request->transaction_type = 'foobar';

		$_call = new \ReflectionMethod('Hypercharge\Transaction', '_call');
		$_call->setAccessible(true);
		$_call->invoke(null, 'sale', $request, 'CHANNEL_TOKEN');
	}

	function testEachWith3Pages() {
		XmlSerializer::$sort = false;
		$curl = $this->curlMock(3);

		$data = array('start_date'=>'2013-06-05', 'period'=>'P1W');
		$requestXml = '<?xml version="1.0" encoding="UTF-8"?>
<reconcile>
  <start_date>2013-06-05</start_date>
  <end_date>2013-06-12</end_date>
  <page>1</page>
</reconcile>
';
		$curl
			->shouldReceive('xmlPost')
			->with('https://test.hypercharge.net/reconcile/by_date/CHANNEL_TOKEN', $requestXml)
			->once()
			->andReturn($this->schemaResponse('reconcile_by_date_page_1.xml'));

		$requestXml = '<?xml version="1.0" encoding="UTF-8"?>
<reconcile>
  <start_date>2013-06-05</start_date>
  <end_date>2013-06-12</end_date>
  <page>2</page>
</reconcile>
';
		$curl
			->shouldReceive('xmlPost')
			->with('https://test.hypercharge.net/reconcile/by_date/CHANNEL_TOKEN', $requestXml)
			->once()
			->andReturn($this->schemaResponse('reconcile_by_date_page_2.xml'));

		$requestXml = '<?xml version="1.0" encoding="UTF-8"?>
<reconcile>
  <start_date>2013-06-05</start_date>
  <end_date>2013-06-12</end_date>
  <page>3</page>
</reconcile>
';
		$curl
			->shouldReceive('xmlPost')
			->with('https://test.hypercharge.net/reconcile/by_date/CHANNEL_TOKEN', $requestXml)
			->once()
			->andReturn($this->schemaResponse('reconcile_by_date_page_3.xml'));

		$n = 0;
		$_this = $this;
		$uids = array();
		Transaction::each('CHANNEL_TOKEN', $data, function($trx) use(&$n, $_this, &$uids) {
			$n++;
			$_this->assertIsA($trx, 'Hypercharge\Transaction');
			if(isset($uids[$trx->unique_id])) $_this->fail("dublicate trx: $n  unique_id: {$trx->unique_id}");
			$uids[$trx->unique_id] = true;
		});
		$this->assertEqual($n, 298);
	}

	function testNotificationRoundtrip() {
		$postData = $this->schemaNotification('transaction_notification.json');
		$apiPassword = 'bogus';
		Config::set('username', $apiPassword, Config::ENV_SANDBOX);
		$notification = Transaction::notification($postData);
		$this->assertTrue($notification->isVerified());
		$this->assertTrue($notification->isApproved());
		$this->assertEqual($this->schemaNotification('transaction_notification_ack.xml'), $notification->ack());
	}

	function testNotificationSignatureBroken() {
		$postData = $this->schemaNotification('transaction_notification.json');
		$apiPassword = 'wrong';
		Config::set('username', $apiPassword, Config::ENV_SANDBOX);
		$notification = Transaction::notification($postData);
		$this->assertFalse($notification->isVerified());
	}

	function testNotificationEmptyParamsThrows() {
		$postData = array();
		$apiPassword = 'bogus';
		Config::set('username', $apiPassword, Config::ENV_SANDBOX);
		try {
			Transaction::notification($postData);
			$this->fail('Errors\ArgumentError expected!');
		} catch (Errors\ArgumentError $exe) {
			$this->assertEqual('Missing or empty argument 1', $exe->getMessage());
			return;
		} catch(Exception $exe) {
			$this->fail('unexpected Exception: '. $exe->toString());
		}
	}

	function testNotificationMissingPasswordThrows() {
		$postData = $this->schemaNotification('transaction_notification.json');
		Config::set('username', '', Config::ENV_SANDBOX);
		$this->assertEqual('', Config::getPassword());
		try {
			Transaction::notification($postData);
			$this->fail('Errors\ArgumentError expected!');
		} catch (Errors\ArgumentError $exe) {
			$this->assertEqual('password is not configured! See Hypercharge\Config::set()', $exe->getMessage());
			return;
		} catch(Exception $exe) {
			$this->fail('unexpected Exception: '. $exe->toString());
		}
	}

	function testMethodsForAllowedRequestsExist() {
		$reflection = new \ReflectionClass('Hypercharge\Transaction');
		$methods = $reflection->getMethods(\ReflectionMethod::IS_STATIC);
		$methods = array_map(function($m) {return $m->name;}, $methods);
		$nonTrxTypes = array('_call', 'find', 'each', 'page');
		$methods = array_diff($methods, $nonTrxTypes);
		$diff = array_diff(TransactionRequest::getAllowedTypes(), $methods);
		$this->assertEqual(array(), $diff, '%s diff: '. print_r($diff, true));
	}

	function testConstructorLoadsJsonResponse() {
		$response = json_decode(JsonSchemaFixture::response('scheduled_transactions_one.json'));
		$this->assertIsA($response->entries[0], 'stdClass');
		$trx = new Transaction($response->entries[0]);
		$this->assertIdentical(500, $trx->amount);
		$this->assertIdentical('USD', $trx->currency);
		$this->assertEqual('3ba2d77ab04f773c0a47bd1081ac50be', $trx->unique_id);
	}
}

