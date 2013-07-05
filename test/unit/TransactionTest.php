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

		$r['status'] = Transaction::STATUS_ERROR;
		$t = new Transaction($r);
		$this->assertTrue($t->isError());

		$r['status'] = Transaction::STATUS_VOIDED;
		$t = new Transaction($r);
		$this->assertTrue($t->isVoided());

		$r['status'] = Transaction::STATUS_PENDING_ASYNC;
		$t = new Transaction($r);
		$this->assertTrue($t->isPendingAsync());

		// TODO maybe implement all stati
	}

	function testShouldRedirectWithUidAndPendingAsync() {

		$this->fail('TODO define redirect conditions  #1649 ');

		$r = $this->response('debit_sale.xml');
		$r['status'] = Transaction::STATUS_PENDING_ASYNC;
		$t = new Transaction($r);
		$this->assertTrue($t->shouldRedirect());
	}

	function testShouldRedirectWithoutUid() {
		$r = $this->response('authorize3d_pending_async.xml');
		$r['unique_id'] = '';
		$t = new Transaction($r);
		$this->assertFalse($t->shouldRedirect());
	}

	function testShouldRedirectWithoutBoth() {
		$r = $this->response('debit_sale.xml');
		$r['status'] = Transaction::STATUS_PENDING_ASYNC;
		$r['unique_id'] = '';
		$t = new Transaction($r);
		$this->assertFalse($t->shouldRedirect());
	}

	function testShouldRedirect() {
		$t = new Transaction($this->response('authorize3d_pending_async.xml'));
		$this->assertTrue($t->shouldRedirect());
	}

	function testShouldNotRedirect() {
		$t = new Transaction($this->response('debit_sale.xml'));
		$this->assertFalse($t->shouldRedirect());
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

}
