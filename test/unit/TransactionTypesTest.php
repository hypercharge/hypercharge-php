<?php
namespace Hypercharge;

require_once dirname(__DIR__).'/test_helper.php';

class TransactionTypesTest extends HyperchargeTestCase {

	function testFromXmlWithUserFormat() {
		$data = array('sale', 'authorize');
		$t = new TransactionTypes();
		$this->assertEqual($t->fromXml($data), array('sale', 'authorize'));
	}

	function testFromXmlWithNull() {
		$data = null;
		$t = new TransactionTypes();
		$this->assertEqual($t->fromXml($data), null);
	}

	function testFromXmlWithEmptyArray() {
		$data = array();
		$t = new TransactionTypes();
		$this->assertEqual($t->fromXml($data), null);
	}

	function testFromXmlWithXmlResponseFormat() {
		$data = array('transaction_type' => array('sale', 'authorize'));
		$t = new TransactionTypes();
		$this->assertEqual($t->fromXml($data), array('sale', 'authorize'));
	}

	function testFromXmlWithXmlResponseFormatOneField() {
		$data = array('transaction_type' => 'sale');
		$t = new TransactionTypes();
		$this->assertEqual($t->fromXml($data), array('sale'));
	}

	function testFromXmlWithXmlResponseFormatEmptyArray() {
		$data = array('transaction_type' => array());
		$t = new TransactionTypes();
		$this->assertEqual($t->fromXml($data), array());
	}

	function testFromXmlWithXmlResponseFormatNull() {
		$data = array('transaction_type' => null);
		$t = new TransactionTypes();
		$this->assertEqual($t->fromXml($data), null);
	}

	function testToXml() {
		$data = array('sale', 'authorize');
		$t = new TransactionTypes();
		$parent = XmlSerializer::createDocument('transaction_types');
		$t->toXml($data, $parent);
		$this->assertEqual($parent->ownerDocument->saveXml(), '<?xml version="1.0" encoding="UTF-8"?>
<transaction_types>
  <transaction_type>sale</transaction_type>
  <transaction_type>authorize</transaction_type>
</transaction_types>
');
	}
	function testToXmlWithEmptyArray() {
		$data = array();
		$t = new TransactionTypes();
		$parent = XmlSerializer::createDocument('transaction_types');
		$t->toXml($data, $parent);
		$this->assertEqual($parent->ownerDocument->saveXml(), '<?xml version="1.0" encoding="UTF-8"?>
<transaction_types/>
');
	}
	function testToXmlWithNull() {
		$data = null;
		$t = new TransactionTypes();
		$parent = XmlSerializer::createDocument('transaction_types');
		$t->toXml($data, $parent);
		$this->assertEqual($parent->ownerDocument->saveXml(), '<?xml version="1.0" encoding="UTF-8"?>
<transaction_types/>
');
	}
}
