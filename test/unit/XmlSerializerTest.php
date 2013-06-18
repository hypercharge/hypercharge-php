<?php
namespace Hypercharge;

require_once dirname(__DIR__).'/test_helper.php';

class XmlSerializerTest extends HyperchargeTestCase {


	function testCreateXmlDocument() {
		$node = XmlSerializer::createDocument('payment');
		$this->assertEqual($node->nodeName, 'payment');
	}

	function testAddChildValueNull() {
		$root = XmlSerializer::createDocument('wurzel');
		XmlSerializer::addChild($root, 'stamm');
		$this->assertEqual($root->ownerDocument->saveXml(), '<?xml version="1.0" encoding="UTF-8"?>
<wurzel>
  <stamm/>
</wurzel>
');
	}
	function testAddChildValueBoolTrue() {
		$root = XmlSerializer::createDocument('wurzel');
		XmlSerializer::addChild($root, 'bool', true);
		$this->assertEqual($root->ownerDocument->saveXml(), '<?xml version="1.0" encoding="UTF-8"?>
<wurzel>
  <bool>true</bool>
</wurzel>
');
	}

	function testAddChildValueBoolFalse() {
		$root = XmlSerializer::createDocument('wurzel');
		XmlSerializer::addChild($root, 'bool', false);
		$this->assertEqual($root->ownerDocument->saveXml(), '<?xml version="1.0" encoding="UTF-8"?>
<wurzel>
  <bool>false</bool>
</wurzel>
');
	}

	function testAddChildValueInt0() {
		$root = XmlSerializer::createDocument('wurzel');
		XmlSerializer::addChild($root, 'int', 0);
		$this->assertEqual($root->ownerDocument->saveXml(), '<?xml version="1.0" encoding="UTF-8"?>
<wurzel>
  <int>0</int>
</wurzel>
');
	}

	function testAddChildValueString() {
		$root = XmlSerializer::createDocument('wurzel');
		XmlSerializer::addChild($root, 'str', 'the String');
		$this->assertEqual($root->ownerDocument->saveXml(), '<?xml version="1.0" encoding="UTF-8"?>
<wurzel>
  <str>the String</str>
</wurzel>
');
	}

	function test_dom2hash_flat() {
		$xml = '<?xml version="1.0" encoding="UTF-8"?>
<payment/>
';
		$dom = new \SimpleXMLElement($xml);
		$h = XmlSerializer::dom2hash($dom);
		$this->assertEqual($h, array('payment' => array()), print_r($h, true));
	}

	function test_dom2hash_one_level() {
		$xml = '<?xml version="1.0" encoding="UTF-8"?>
<first>1</first>
';
		$dom = new \SimpleXMLElement($xml);
		$h = XmlSerializer::dom2hash($dom);
		$this->assertEqual($h, array('first' => '1'), print_r($h, true));
	}

	function test_dom2hash_1_level_1field() {
		$xml = '<?xml version="1.0" encoding="UTF-8"?>
<id-only>
	<id>1234</id>
</id-only>
';
		$dom = new \SimpleXMLElement($xml);
		$h = XmlSerializer::dom2hash($dom);
		$this->assertEqual($h, array('id-only' => array('id'=>'1234')), print_r($h, true));
	}

	function test_dom2hash_1_level_array() {
		$xml = '<?xml version="1.0" encoding="UTF-8"?>
<id_array>
	<id>1234</id>
	<id>567</id>
</id_array>
';
		$dom = new \SimpleXMLElement($xml);
		$h = XmlSerializer::dom2hash($dom);
		$this->assertEqual($h, array('id_array' => array('id' => array('1234', '567'))), print_r($h, true));
	}

	function test_dom2hash_2_level_array() {
		$xml = '<?xml version="1.0" encoding="UTF-8"?>
<one>
	<ids>
		<id>1234</id>
		<id>567</id>
	</ids>
</one>
';
		$dom = new \SimpleXMLElement($xml);
		$h = XmlSerializer::dom2hash($dom);
		$this->assertEqual($h, array('one' => array('ids' => array('id' => array('1234', '567')))), print_r($h, true));
	}

	function test_dom2hash_4_levels() {
		$xml = '<?xml version="1.0" encoding="UTF-8"?>
<one>
	<two>
		<three>
			<four>4</four>
		</three>
	</two>
</one>
';
		$dom = new \SimpleXMLElement($xml);
		$h = XmlSerializer::dom2hash($dom);
		$this->assertEqual($h, array('one' => array('two' => array('three' => array('four' => '4')))), print_r($h, true));
	}

	function test_dom2hash_4_levels_last_empty() {
		$xml = '<?xml version="1.0" encoding="UTF-8"?>
<one>
	<two>
		<three>
			<four />
		</three>
	</two>
</one>
';
		$dom = new \SimpleXMLElement($xml);
		$h = XmlSerializer::dom2hash($dom);
		$this->assertEqual($h, array('one' => array('two' => array('three' => array('four' => '')))), print_r($h, true));
	}

	function test_dom2hash_first_level_is_array() {
		$xml = '<?xml version="1.0" encoding="UTF-8"?>
<root>
	<one>
		<id>111</id>
		<name>eins</name>
	</one>
	<one>
		<id>222</id>
		<name>zwei</name>
	</one>
</root>
';
		$dom = new \SimpleXMLElement($xml);
		$h = XmlSerializer::dom2hash($dom);
		$this->assertEqual($h
			,array('root' =>
				array('one' => array(
					 array('id'=>'111', 'name'=>'eins')
					,array('id'=>'222', 'name'=>'zwei')
					)
				)
			)
			, print_r($h, true)
		);
	}

	function test_dom2hash_attributes_in_empty_root_node() {
		$xml = '<?xml version="1.0" encoding="UTF-8"?>
<root at1="attribute1 content" at2="attribute2 content">
</root>
';
		$dom = new \SimpleXMLElement($xml);
		$h = XmlSerializer::dom2hash($dom);
		$this->assertEqual($h
			,array('root' =>
				array(
					'__attributes' => array(
					  'at1'=>'attribute1 content'
					 ,'at2'=>'attribute2 content'
					)
				)
			)
			, '%s'.print_r($h, true)
		);
	}

	function test_dom2hash_attributes_in_text_in_root_node() {
		$xml = '<?xml version="1.0" encoding="UTF-8"?>
<root at1="attribute1 content" at2="attribute2 content">
	the text
</root>
';
		$dom = new \SimpleXMLElement($xml);
		$h = XmlSerializer::dom2hash($dom);
		$this->assertEqual($h
			 // the attributes are dropped. unfortunately no other choice here
			,array('root' => 'the text')
			, '%s'.print_r($h, true)
		);
	}

	function test_dom2hash_attributes_in_root_node() {
		$xml = '<?xml version="1.0" encoding="UTF-8"?>
<root at1="attribute1 content" at2="attribute2 content">
	<one>1</one>
</root>
';
		$dom = new \SimpleXMLElement($xml);
		$h = XmlSerializer::dom2hash($dom);
		$this->assertEqual($h
			,array('root' =>
				array(
					'__attributes' => array(
					  'at1'=>'attribute1 content'
					 ,'at2'=>'attribute2 content'
					)
				 	,'one'=>'1'
				)
			)
			, '%s'.print_r($h, true)
		);
	}
	function test_dom2hash_attributes_in_1st_level_atomic_node() {
		$xml = '<?xml version="1.0" encoding="UTF-8"?>
<root>
	<one at1="attribute1 content" at2="attribute2 content">1</one>
</root>
';
		$dom = new \SimpleXMLElement($xml);
		$h = XmlSerializer::dom2hash($dom);
		$this->assertEqual($h
			,array('root' =>
				array('one' => '1') // attributes are dumped - not possible and not needed in hypercharge
			)
			, '%s'.print_r($h, true)
		);
	}
	function test_dom2hash_attributes_in_1st_level_node_containing_children() {
		$xml = '<?xml version="1.0" encoding="UTF-8"?>
<root>
	<one at1="attribute1 content" at2="attribute2 content">
		<two>2</two>
	</one>
</root>
';
		$dom = new \SimpleXMLElement($xml);
		$h = XmlSerializer::dom2hash($dom);
		$this->assertEqual($h
			,array('root' =>
				array('one' =>
					array(
						'__attributes' =>
							array(
								'at1'=>'attribute1 content'
							 ,'at2'=>'attribute2 content'
							)
						,'two' => '2'
					)
				)
			)
			, '%s'.print_r($h, true)
		);
	}
	function test_dom2hash_attributes_in_multiple_nodes() {
		$xml = '<?xml version="1.0" encoding="UTF-8"?>
<root>
	<one a="2.1 attribute">
		<two>2.1</two>
	</one>
	<one a="2.2 attribute">
		<two>2.2</two>
	</one>
</root>
';
		$dom = new \SimpleXMLElement($xml);
		$h = XmlSerializer::dom2hash($dom);
		$this->assertEqual($h
			,array('root' =>
				array('one' =>
					array(
						 array('two' => '2.1', '__attributes'=>array('a'=>'2.1 attribute'))
						,array('two' => '2.2', '__attributes'=>array('a'=>'2.2 attribute'))
					)
				)
			)
			, '%s'.print_r($h, true)
		);
	}

	function test_toXmlUnsorted() {
		$a = new Address($this->fixture('address.json'));
		XmlSerializer::$sort = false;
		$root = XmlSerializer::createDocument('r');
		XmlSerializer::_toXml($a, $root);
		$str = $root->ownerDocument->saveXML();
		$this->assertEqual($str, '<?xml version="1.0" encoding="UTF-8"?>
<r>
  <first_name>Hans</first_name>
  <last_name>Johanson</last_name>
  <address1>Kurfürstendamm 123</address1>
  <city>Berlin</city>
  <zip_code>10624</zip_code>
  <country>DE</country>
</r>
');
	}


	function test_toXmlSorted() {
		$a = new Address($this->fixture('address.json'));
		XmlSerializer::$sort = true;
		$root = XmlSerializer::createDocument('r');
		XmlSerializer::_toXml($a, $root);
		$str = $root->ownerDocument->saveXML();
		$this->assertEqual($str, '<?xml version="1.0" encoding="UTF-8"?>
<r>
  <address1>Kurfürstendamm 123</address1>
  <city>Berlin</city>
  <country>DE</country>
  <first_name>Hans</first_name>
  <last_name>Johanson</last_name>
  <zip_code>10624</zip_code>
</r>
');
	}

	function test_toXmlRecursiveUnsorted() {
		$a = new Address($this->fixture('address.json'));
		$a->foo = new MpiParams(array('x'=>'1', 'a'=>'2', 'k'=>'3'));
		XmlSerializer::$sort = false;
		$root = XmlSerializer::createDocument('r');
		XmlSerializer::_toXml($a, $root);
		$str = $root->ownerDocument->saveXML();
		$this->assertEqual($str, '<?xml version="1.0" encoding="UTF-8"?>
<r>
  <first_name>Hans</first_name>
  <last_name>Johanson</last_name>
  <address1>Kurfürstendamm 123</address1>
  <city>Berlin</city>
  <zip_code>10624</zip_code>
  <country>DE</country>
  <foo>
    <x>1</x>
    <a>2</a>
    <k>3</k>
  </foo>
</r>
');
	}

	function test_toXmlRecursiveSorted() {
		$a = new Address($this->fixture('address.json'));
		$a->foo = new MpiParams(array('x'=>'1', 'a'=>'2', 'k'=>'3'));
		XmlSerializer::$sort = true;
		$root = XmlSerializer::createDocument('r');
		XmlSerializer::_toXml($a, $root);
		$str = $root->ownerDocument->saveXML();
		$this->assertEqual($str, '<?xml version="1.0" encoding="UTF-8"?>
<r>
  <address1>Kurfürstendamm 123</address1>
  <city>Berlin</city>
  <country>DE</country>
  <first_name>Hans</first_name>
  <foo>
    <a>2</a>
    <k>3</k>
    <x>1</x>
  </foo>
  <last_name>Johanson</last_name>
  <zip_code>10624</zip_code>
</r>
');
	}
}
