<?php
namespace Hypercharge;

require_once dirname(__DIR__).'/test_helper.php';

class ReconcileByDateRequestTest extends HyperchargeTestCase {

	function setUp() {
		$this->serializer = new XmlSerializer(new XmlMapping());
    XmlSerializer::$sort = false;
	}

	function testValidateDefaults() {
		$r = new ReconcileByDateRequest();
		$this->assertEqual('1970-01-01', $r->start_date);
		$this->assertNull($r->end_date);
		$this->assertEqual(1, $r->page);
		$r->validate();
		$xml = $this->serializer->toXml($r);
		$this->assertEqual($xml, '<?xml version="1.0" encoding="UTF-8"?>
<reconcile>
  <start_date>1970-01-01</start_date>
  <end_date/>
  <page>1</page>
</reconcile>
');
	}

	function testValidateStartDate() {
		$r = new ReconcileByDateRequest(array('start_date' => '2013-04-01'));
		$this->assertEqual('2013-04-01', $r->start_date);
		$this->assertNull($r->end_date);
		$this->assertEqual(1, $r->page);
		$r->validate();
		$xml = $this->serializer->toXml($r);
		$this->assertEqual($xml, '<?xml version="1.0" encoding="UTF-8"?>
<reconcile>
  <start_date>2013-04-01</start_date>
  <end_date/>
  <page>1</page>
</reconcile>
');
	}

	function testValidateEndDate() {
		$r = new ReconcileByDateRequest(array('end_date' => '2013-04-01'));
		$this->assertEqual('1970-01-01', $r->start_date);
		$this->assertEqual('2013-04-01', $r->end_date);
		$this->assertEqual(1, $r->page);
		$r->validate();
		$xml = $this->serializer->toXml($r);
		$this->assertEqual($xml, '<?xml version="1.0" encoding="UTF-8"?>
<reconcile>
  <start_date>1970-01-01</start_date>
  <end_date>2013-04-01</end_date>
  <page>1</page>
</reconcile>
');
	}

	function testValidatePage() {
		$r = new ReconcileByDateRequest(array('page' => 7));
		$this->assertEqual('1970-01-01', $r->start_date);
		$this->assertNull($r->end_date);
		$this->assertEqual(7, $r->page);
		$r->validate();
		$xml = $this->serializer->toXml($r);
		$this->assertEqual($xml, '<?xml version="1.0" encoding="UTF-8"?>
<reconcile>
  <start_date>1970-01-01</start_date>
  <end_date/>
  <page>7</page>
</reconcile>
');
	}

	function testValidatePage0() {
		$this->expectException(Errors\ValidationError::create('page', 'must be an integer greater or equal 1'));
		$r = new ReconcileByDateRequest(array('page' => 0));
		$r->validate();
	}
	function testValidatePage_minus1() {
		$this->expectException(Errors\ValidationError::create('page', 'must be an integer greater or equal 1'));
		$r = new ReconcileByDateRequest(array('page' => -1));
		$r->validate();
	}
	function testValidatePageIntString() {
		$r = new ReconcileByDateRequest(array('page' => '8'));
		$r->validate();
	}

	function testValidateStartDateFormat() {
		$this->expectException(Errors\ValidationError::create('start_date', 'must be yyyy-mm-dd'));
		$r = new ReconcileByDateRequest(array('start_date' => '04/01/3013'));
		$r->validate();
	}

	function testValidateEndDateFormat() {
		$this->expectException(Errors\ValidationError::create('end_date', 'must be yyyy-mm-dd'));
		$r = new ReconcileByDateRequest(array('start_date' => '2014-11-01', 'end_date'=>'2019-1-1'));
		$r->validate();
	}

	function testStartDateWithInterval1Day() {
		$r = new ReconcileByDateRequest(array('start_date' => '2013-04-01', 'period'=>'P1D'));
		$this->assertEqual('2013-04-01', $r->start_date);
		$this->assertEqual('2013-04-02', $r->end_date);
		$this->assertEqual(1, $r->page);
		$r->validate();
		$xml = $this->serializer->toXml($r);
		$this->assertEqual($xml, '<?xml version="1.0" encoding="UTF-8"?>
<reconcile>
  <start_date>2013-04-01</start_date>
  <end_date>2013-04-02</end_date>
  <page>1</page>
</reconcile>
', 'period should not be in request');
	}

	function testStartDateWithInterval1Week() {
		$r = new ReconcileByDateRequest(array('start_date' => '2013-05-31', 'period'=>'P1W'));
		$this->assertEqual('2013-05-31', $r->start_date);
		$this->assertEqual('2013-06-07', $r->end_date);
		$this->assertEqual(1, $r->page);
		$r->validate();
	}

	function testStartDateWithIntervalInstance1Month() {
		$r = new ReconcileByDateRequest(array('start_date' => '2013-05-31', 'period'=>new \DateInterval('P1M')));
		$this->assertEqual('2013-05-31', $r->start_date);
		$this->assertEqual('2013-07-01', $r->end_date);
		$this->assertEqual(1, $r->page);
		$r->validate();
	}

		function testStartDateWithDateRangeOverwritesEndDate() {
		$r = new ReconcileByDateRequest(array('start_date' => '2013-05-01', 'end_date' => '2013-06-01', 'period'=>'P1M1D'));
		$this->assertEqual('2013-05-01', $r->start_date);
		$this->assertEqual('2013-06-02', $r->end_date);
		$this->assertEqual(1, $r->page);
		$r->validate();
	}

	function testInvalidDateRangeThrowsException() {
		$this->expectException('Exception');
		new ReconcileByDateRequest(array('start_date' => '2013-05-01', 'period'=>'invalid'));
	}

	function testCreateResponseWithEmptyResult() {
		$data = $this->parseXml($this->fixture('reconcile_by_date_response_empty.xml'));
		$request = new ReconcileByDateRequest();
		$response = $request->createResponse($data);
		$this->assertEqual($response->getEntries(), array());
		$this->assertEqual($response->getTotalCount(), 0);
		$this->assertEqual($response->getPage(), 1);
		$this->assertEqual($response->getPerPage(), 100);
		$this->assertEqual($response->getPagesCount(), 1);
		$this->assertFalse($response->hasNextPage());
		foreach($response as $trx) {
			$this->fail('response should be empty!');
		}
	}

	function testCreateResponseWithOneResult() {
		$data = $this->parseXml($this->fixture('reconcile_by_date_response_one_transaction.xml'));
		$request = new ReconcileByDateRequest();
		$response = $request->createResponse($data);
		$this->assertIsA($response->getEntries(), 'array');
		$this->assertEqual(count($response->getEntries()), 1);
		$this->assertEqual($response->getTotalCount(), 1);
		$this->assertEqual($response->getPage(), 1);
		$this->assertEqual($response->getPerPage(), 100);
		$this->assertEqual($response->getPagesCount(), 1);
		$this->assertFalse($response->hasNextPage());
		$n = 0;
		foreach($response as $trx) {
			$n++;
			$this->assertIsA($trx, 'Hypercharge\Transaction');
			$this->assertEqual($trx->unique_id, '33d0ea86616a89d091a300c25ac683cf');
			$this->assertEqual($trx->getType(), 'sale');
		}
		$this->assertEqual($n, 1);
	}

	function testCreateResponseWithTwoResults() {
		$data = $this->parseXml($this->fixture('reconcile_by_date_response_two_transactions.xml'));
		$request = new ReconcileByDateRequest();
		$response = $request->createResponse($data);
		$this->assertIsA($response->getEntries(), 'array');
		$this->assertEqual(count($response->getEntries()), 2);
		$this->assertEqual($response->getTotalCount(), 2);
		$this->assertEqual($response->getPage(), 1);
		$this->assertEqual($response->getPerPage(), 100);
		$this->assertEqual($response->getPagesCount(), 1);
		$this->assertFalse($response->hasNextPage());
		$n = 0;
		foreach($response as $trx) {
			$n++;
			$this->assertIsA($trx, 'Hypercharge\Transaction');
			switch($n){
				case 1:
					$this->assertEqual($trx->unique_id, '84f1c8d8cc79fca7379d19c9cf62f08d');
					$this->assertEqual($trx->getType(), 'sale');
					break;
				case 2:
					$this->assertEqual($trx->unique_id, '0a0f8d7c71b0f45471291f25c60c694f');
					$this->assertEqual($trx->getType(), 'refund');
					break;
				default:
					$this->fail('response should have 2 entries');
					break;
			}
		}
		$this->assertEqual($n, 2);
	}
}