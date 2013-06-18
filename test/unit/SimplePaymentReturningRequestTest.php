<?php
namespace Hypercharge;

require_once dirname(__DIR__).'/test_helper.php';

class SimplePaymentReturningRequestTest extends \UnitTestCase {

	function testSerialize() {
		$c = new SimplePaymentReturningRequest('cancel','345');
		$serializer = new XmlSerializer();
		$str = $serializer->toXml($c);
		$this->assertEqual('<?xml version="1.0" encoding="UTF-8"?>
<cancel>
  <unique_id>345</unique_id>
</cancel>
', $str);
	}

	function testValidateThrows() {
		$this->expectException(Errors\ValidationError::create('unique_id', 'must be 32 character hex string: 345'));
		$c = new SimplePaymentReturningRequest('cancel','345');
		$c->validate();
	}

	function testValidateOk() {
		$c = new SimplePaymentReturningRequest('cancel','2dba69127788f34b5fde7e09128b74ed');
		$c->validate();
		$this->assertTrue(true);
	}

	function testCreateResponseReturnsWpfPayment() {
		$c = new SimplePaymentReturningRequest('cancel','345');
		$data = array('payment'=>array(
			'type' => 'WpfPayment'
			,'unique_id' => '2344534ljj3l45j'
			,'status' => 'canceled'
		));
		$resp = $c->createResponse($data);
		$this->assertIsA($resp, 'Hypercharge\Payment');
		$this->assertEqual('WpfPayment', $resp->type);
		$this->assertEqual('2344534ljj3l45j', $resp->unique_id);
		$this->assertEqual('canceled'       , $resp->status);
		$this->assertTrue($resp->isCanceled());
	}

	function testCreateResponseReturnsMobilePayment() {
		$c = new SimplePaymentReturningRequest('cancel', '345');
		$data = array('payment'=>array(
			'type' => 'MobilePayment'
			,'unique_id' => 'a23'
			,'status' => 'canceled'
		));
		$resp = $c->createResponse($data);
		$this->assertIsA($resp, 'Hypercharge\Payment');
		$this->assertEqual('MobilePayment', $resp->type);
		$this->assertEqual('a23', $resp->unique_id);
		$this->assertEqual('canceled'       , $resp->status);
		$this->assertTrue($resp->isCanceled());
	}

	function testCreateResponseWithOneTransaction() {
		$c = new SimplePaymentReturningRequest('find', '345');

	}

}