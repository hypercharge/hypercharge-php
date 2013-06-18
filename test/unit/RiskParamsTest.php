<?php
require_once dirname(__DIR__).'/test_helper.php';

class RiskParamsTest extends UnitTestCase {

	function testSerialize() {
		$r = new Hypercharge\RiskParams(array('session_id'=>'123'));
		$root = Hypercharge\XmlSerializer::createDocument('test');
		Hypercharge\XmlSerializer::_toXml($r, $root);
		$str = $root->ownerDocument->saveXML();
		$this->assertEqual('<?xml version="1.0" encoding="UTF-8"?>
<test>
  <session_id>123</session_id>
</test>
', $str);
	}
}