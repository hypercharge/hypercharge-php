<?php
namespace Hypercharge;

require_once dirname(__DIR__).'/test_helper.php';

class PaymentMethodsTest extends HyperchargeTestCase {

	function testFromXmlWithUserFormat() {
		$data = array('sale', 'pay_pal');
		$t = new PaymentMethods();
		$this->assertEqual($t->fromXml($data), array('sale', 'pay_pal'));
	}

	function testFromXmlWithNull() {
		$data = null;
		$t = new PaymentMethods();
		$this->assertEqual($t->fromXml($data), null);
	}

	function testFromXmlWithEmptyArray() {
		$data = array();
		$t = new PaymentMethods();
		$this->assertEqual($t->fromXml($data), null);
	}

	function testFromXmlWithXmlResponseFormat() {
		$data = array('payment_method' => array('sale', 'pay_pal'));
		$t = new PaymentMethods();
		$this->assertEqual($t->fromXml($data), array('sale', 'pay_pal'));
	}

	function testFromXmlWithXmlResponseFormatOneField() {
		$data = array('payment_method' => 'sale');
		$t = new PaymentMethods();
		$this->assertEqual($t->fromXml($data), array('sale'));
	}

	function testFromXmlWithXmlResponseFormatEmptyArray() {
		$data = array('payment_method' => array());
		$t = new PaymentMethods();
		$this->assertEqual($t->fromXml($data), array());
	}

	function testFromXmlWithXmlResponseFormatNull() {
		$data = array('payment_method' => null);
		$t = new PaymentMethods();
		$this->assertEqual($t->fromXml($data), null);
	}

	function testToXml() {
		$data = array('sale', 'pay_pal');
		$t = new PaymentMethods();
		$parent = XmlSerializer::createDocument('payment_methods');
		$t->toXml($data, $parent);
		$this->assertEqual($parent->ownerDocument->saveXml(), '<?xml version="1.0" encoding="UTF-8"?>
<payment_methods>
  <payment_method>sale</payment_method>
  <payment_method>pay_pal</payment_method>
</payment_methods>
');
	}
	function testToXmlWithEmptyArray() {
		$data = array();
		$t = new PaymentMethods();
		$parent = XmlSerializer::createDocument('payment_methods');
		$t->toXml($data, $parent);
		$this->assertEqual($parent->ownerDocument->saveXml(), '<?xml version="1.0" encoding="UTF-8"?>
<payment_methods/>
');
	}
	function testToXmlWithNull() {
		$data = null;
		$t = new PaymentMethods();
		$parent = XmlSerializer::createDocument('payment_methods');
		$t->toXml($data, $parent);
		$this->assertEqual($parent->ownerDocument->saveXml(), '<?xml version="1.0" encoding="UTF-8"?>
<payment_methods/>
');
	}
}
