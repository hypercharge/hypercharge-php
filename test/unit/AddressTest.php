<?php
require_once dirname(__DIR__).'/test_helper.php';

class AddressTest extends UnitTestCase {

	function address() {
		return array(
			'first_name'=> 'Hans',
			'last_name'=> 'Hübner',
			'address1'=> 'Kuhrfürstenstr. 124',
			'address2'=> '',
			'zip_code'=> '10578',
			'city'=> 'Berlin',
			'state'=> '',
			'country'=> 'DE'
		);
	}

	function testConstructor() {
		$a = new Hypercharge\Address($this->address());
		$this->assertEqual($a->first_name, 'Hans');
		$this->assertEqual($a->last_name, 'Hübner');
		$this->assertEqual($a->address1, 'Kuhrfürstenstr. 124');
		$this->assertEqual($a->address2, '');
		$this->assertEqual($a->zip_code, '10578');
		$this->assertEqual($a->city, 'Berlin');
		$this->assertEqual($a->state, '');
		$this->assertEqual($a->country, 'DE');
	}

	function testSerialize() {
		$a = new Hypercharge\Address($this->address());
		Hypercharge\XmlSerializer::$sort = false;
		$root = Hypercharge\XmlSerializer::createDocument('r');
		Hypercharge\XmlSerializer::_toXml($a, $root);
		$str = $root->ownerDocument->saveXML();
		$this->assertEqual($str, '<?xml version="1.0" encoding="UTF-8"?>
<r>
  <first_name>Hans</first_name>
  <last_name>Hübner</last_name>
  <address1>Kuhrfürstenstr. 124</address1>
  <address2></address2>
  <zip_code>10578</zip_code>
  <city>Berlin</city>
  <state></state>
  <country>DE</country>
</r>
');
	}


	function testSerializeWithRootNodeMappingShouldThrowError() {
		$a = new Hypercharge\Address($this->address());
		$this->expectException(new PatternExpectation('/class "Address" has no root-name mapping/'));
		$serializer = new Hypercharge\XmlSerializer();
		$serializer->toXml($a);
	}

}














