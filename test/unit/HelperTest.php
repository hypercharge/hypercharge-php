<?php
namespace Hypercharge;

require_once dirname(__DIR__).'/test_helper.php';

class SerializableImpl implements Serializable {
	function __construct($p) {
		Helper::assign($this, $p);
	}
}


class HelperTest extends \UnitTestCase {

	function __construct() {
		parent::__construct();
		// pseudo-default value
		// php quirks - static class members are not reset after each test.
		Config::setIdSeparator('---');

		new XmlSerializer(new XmlMapping());
	}

	function __destruct() {
		// pseudo-default value
		// php quirks - static class members are not reset after each test.
		Config::setIdSeparator('---');
	}

	function testSerializeXmlSimpleHash() {
		$o = new SerializableImpl(array('field_1' => 'string in field 1'));
		$root = XmlSerializer::createDocument('r');
		XmlSerializer::_toXml($o, $root);
		$str = $root->ownerDocument->saveXML();
		$this->assertEqual(
'<?xml version="1.0" encoding="UTF-8"?>
<r>
  <field_1>string in field 1</field_1>
</r>
', $str);
	}
	function testSerializeXmlShouldOmmitArray() {
		$o = new SerializableImpl(array('field_1' => array('string in field 1')));
		$root = XmlSerializer::createDocument('r');
		XmlSerializer::_toXml($o, $root);
		$str = $root->ownerDocument->saveXML();
		$this->assertEqual(
'<?xml version="1.0" encoding="UTF-8"?>
<r/>
', $str);
	}
	function testSerializeXmlShouldOmmitNonSerializable() {
		$nonSerializable = new \stdClass();
		$nonSerializable->feld = 'not to be in xml';
		$o = new SerializableImpl(array('std_class' => $nonSerializable));
		$root = XmlSerializer::createDocument('r');
		XmlSerializer::_toXml($o, $root);
		$str = $root->ownerDocument->saveXML();
		$this->assertEqual(
'<?xml version="1.0" encoding="UTF-8"?>
<r/>
', $str);
	}


	function testSerializeXmlShouldStepIntoChildObject() {
		$o = new SerializableImpl(array(
			'objekt' => new SerializableImpl(array(
				'unique_id' => 1234
			))
		));
		$root = XmlSerializer::createDocument('r');
		XmlSerializer::_toXml($o, $root);
		$str = $root->ownerDocument->saveXml();
		$this->assertEqual(
'<?xml version="1.0" encoding="UTF-8"?>
<r>
  <objekt>
    <unique_id>1234</unique_id>
  </objekt>
</r>
', $str);
	}

	function testSerializeXmlShouldHandleNull() {
		$o = new SerializableImpl(array(
			'leer' => null
		));
		$root = XmlSerializer::createDocument('r');
		XmlSerializer::_toXml($o, $root);
		$str = $root->ownerDocument->saveXML();
		$this->assertEqual(
'<?xml version="1.0" encoding="UTF-8"?>
<r>
  <leer/>
</r>
', $str);
	}

	function testApplyShouldDropAttributes() {
		$o = new SerializableImpl(array(
			'__attributes' => array('a'=>'should not be assigned')
		));
		$this->assertFalse(isset($o->__attributes), 'is __attributes set? %s '.print_r($o, true));
	}

	function testAppendRandomIdWithDefaultDivider() {
		$this->assertPattern('/^foo-bar---[a-f0-9]{13}$/', Helper::appendRandomId('foo-bar'));
	}

	function testAssignWithObject() {
		$data = new \stdClass();
		$data->field_name = 'value';
		$o = new SerializableImpl($data);
		$this->assertEqual('value', $o->field_name);
	}

	function testAssignWithIntThrows() {
		$this->expectException('Hypercharge\Errors\ArgumentError');
		new SerializableImpl(7777);
	}


	function testExtractRandomIdWithDefaultDivider() {
		$o = Helper::extractRandomId('foo-bar---12345677aedf');
		$this->assertEqual('foo-bar', $o->transaction_id);
		$this->assertEqual('12345677aedf', $o->random_id);
	}

	function testExtractRandomIdWithWrongDivider() {
		echo "INFO: The next notice message is provoked by the test and is totally ok:\n";
		$this->expectError("WARNING in \\Hypercharge\\Helper::extractRandomId(): no seperator found. transaction_id: 'foo-bar###12345677aedf'");
		$o = Helper::extractRandomId('foo-bar###12345677aedf');
		$this->assertEqual('foo-bar###12345677aedf', $o->transaction_id);
		$this->assertEqual('', $o->random_id);
	}

	function testAppendAndExtractRandomIdWithoutDividerShouldNotAppendRandomId() {
		Config::setIdSeparator(false);
		$transaction_id = Helper::appendRandomId('foo-bar');
		$this->assertEqual('foo-bar', $transaction_id);
		$o = Helper::extractRandomId($transaction_id);
		$this->assertEqual('foo-bar', $o->transaction_id);
		$this->assertEqual('', $o->random_id);
	}

	function testAppendAndExtractRandomIdWithCustomDivider() {
		Config::setIdSeparator('###');
		$transaction_id = Helper::appendRandomId('an---order---id');
		$this->assertPattern('/^an---order---id###[a-f0-9]{13}$/', $transaction_id);
		$o = Helper::extractRandomId($transaction_id);
		$this->assertEqual('an---order---id', $o->transaction_id);
		$this->assertPattern('/^[a-f0-9]{13}$/', $o->random_id);
	}

	function testExtractRandomIdWithCustomDivider() {
		Config::setIdSeparator('###');
		$o = Helper::extractRandomId('an---order---id###adef-ag#exg');
		$this->assertEqual('an---order---id', $o->transaction_id);
		$this->assertEqual('adef-ag#exg', $o->random_id);
	}

	function testStripCcFromPhpArray() {
		$sale = json_decode(JsonSchemaFixture::request('sale.json'), true);
		$this->assertEqual($sale['payment_transaction']['card_number'], '4200000000000000');
		$this->assertEqual($sale['payment_transaction']['cvv'], '123');
		$sale['payment_transaction']['cvv'] = '666';
		$sale = print_r($sale, true);
		$this->assertTrue(strstr($sale, '4200000000000000') !== false);
		$this->assertTrue(strstr($sale, '666') !== false);
		$stripped = Helper::stripCc($sale);
		$this->assertFalse(strstr($stripped, '4200000000000000'));
		$this->assertFalse(strstr($stripped, '666'));
	}

	function testStripCcFromPhpObject() {
		$sale = json_decode(JsonSchemaFixture::request('sale.json'));
		$this->assertEqual($sale->payment_transaction->card_number, '4200000000000000');
		$this->assertEqual($sale->payment_transaction->cvv, '123');
		$sale->payment_transaction->cvv = '666';
		$sale = print_r($sale, true);
		$this->assertTrue(strstr($sale, '4200000000000000'));
		$this->assertTrue(strstr($sale, '666'));
		$stripped = Helper::stripCc($sale);
		$this->assertFalse(strstr($stripped, '4200000000000000'));
		$this->assertFalse(strstr($stripped, '666'));
	}

	function testStripCcFromXml() {
		$sale = JsonSchemaFixture::request('sale.xml');
		$this->assertTrue(strstr($sale, '4200000000000000'));
		$this->assertTrue(strstr($sale, '123'));
		$stripped = Helper::stripCc($sale);
		$this->assertFalse(strstr($stripped, '4200000000000000'));
		$this->assertFalse(preg_match('/\b123\b/', $stripped));
	}

	function testStripCcFromJson() {
		$sale = JsonSchemaFixture::request('sale.json');
		$this->assertTrue(strstr($sale, '4200000000000000'));
		$this->assertTrue(strstr($sale, '123'));
		$stripped = Helper::stripCc($sale);
		$this->assertFalse(strstr($stripped, '4200000000000000'));
		$this->assertFalse(preg_match('/\b123\b/', $stripped));
	}

	function testStripCcFromEscapedJson() {
		$str = json_encode(array('message' => 'foobar '.JsonSchemaFixture::request('sale.json')));
		$this->assertTrue(strstr($str, '4200000000000000'));
		$this->assertTrue(strstr($str, '123'));
		$stripped = Helper::stripCc($str);
		$this->assertFalse(strstr($stripped, '4200000000000000'));
		$this->assertFalse(preg_match('/\b123\b/', $stripped));
	}

	function testStripCcFromDoubleEscapedJson() {
		$str = json_encode(array('bla' => 'again '. json_encode(array('message' => 'foobar '.JsonSchemaFixture::request('sale.json')))));
		$this->assertTrue(strstr($str, '4200000000000000'));
		$this->assertTrue(strstr($str, '123'));
		$stripped = Helper::stripCc($str);
		$this->assertFalse(strstr($stripped, '4200000000000000'));
		$this->assertFalse(preg_match('/\b123\b/', $stripped));
	}

	function testStripBankAccountNumberFromPhpArray() {
		$sale = json_decode(JsonSchemaFixture::request('debit_sale.json'), true);
		$this->assertEqual($sale['payment_transaction']['bank_account_number'], '1290701');
		$sale = print_r($sale, true);
		$this->assertTrue(strstr($sale, '1290701') !== false);
		$stripped = Helper::stripCc($sale);
		$this->assertFalse(strstr($stripped, '1290701'));
	}

	function testStripBankAccountNumberFromPhpObject() {
		$sale = json_decode(JsonSchemaFixture::request('debit_sale.json'));
		$this->assertEqual($sale->payment_transaction->bank_account_number, '1290701');
		$sale = print_r($sale, true);
		$this->assertTrue(strstr($sale, '1290701'));
		$stripped = Helper::stripCc($sale);
		$this->assertFalse(strstr($stripped, '1290701'));
	}

	function testStripBankAccountNumberFromXml() {
		$sale = JsonSchemaFixture::request('debit_sale.xml');
		$this->assertTrue(strstr($sale, '1290701'));
		$stripped = Helper::stripCc($sale);
		$this->assertFalse(strstr($stripped, '1290701'));
	}

	function testStripBankAccountNumberFromJson() {
		$sale = JsonSchemaFixture::request('debit_sale.json');
		$this->assertTrue(strstr($sale, '1290701'));
		$stripped = Helper::stripCc($sale);
		$this->assertFalse(strstr($stripped, '1290701'));
	}

	function testStripBankAccountNumberFromEscapedJson() {
		$str = json_encode(array('message' => 'foobar '.JsonSchemaFixture::request('debit_sale.json')));
		$this->assertTrue(strstr($str, '1290701'));
		$stripped = Helper::stripCc($str);
		$this->assertFalse(strstr($stripped, '1290701'));
	}

	function testStripBankAccountNumberDoubleEscapedJson() {
		$str = json_encode(array('bla' => 'again '. json_encode(array('message' => 'foobar '.JsonSchemaFixture::request('debit_sale.json')))));
		$this->assertTrue(strstr($str, '1290701'));
		$stripped = Helper::stripCc($str);
		$this->assertFalse(strstr($stripped, '1290701'));
	}

	function testValidateUniqueIdThrows() {
		$debug = getenv('DEBUG');
		putenv('DEBUG=0');
		try {
			Helper::validateUniqueId('');
			$this->fail('expects Exception');
		} catch(Errors\ValidationError $e) {
			$this->assertEqual("ValidationError {status_code: 50, technical_message: '1 affected property: unique_id', message: '1 validation error'}", $e->__toString());
			return;
		}
		putenv("DEBUG=$debug");
	}
	function testValidateUniqueId() {
		Helper::validateUniqueId('0293069be5a868ae69290e8a0eff72b3');
	}


  function testArrayToObject1Level() {
    $o = new \StdClass();
    $o->a = 'foo';
    $this->assertIdentical($o, Helper::arrayToObject(array('a'=>'foo')));
  }

  function testArrayToObject2Levels() {
    $o = new \StdClass();
    $o->a = new \StdClass();
    $o->a->foo = 'bar';

    $this->assertIdentical($o, Helper::arrayToObject(array('a'=>array('foo'=>'bar'))));
  }

  function testArrayToObjectEmpty() {
    $o = new \StdClass();
    $this->assertIdentical($o, Helper::arrayToObject(array()));
  }

  function testArrayToObjectWithArray() {
    $o = new \StdClass();
    $o->a = array('a','b','c');

    $this->assertIdentical($o, Helper::arrayToObject(array('a'=>array('a','b','c'))));
  }

    function testArrayToObjectWithObject() {
    $o = new \StdClass();
    $o->a = 'foo';

    $this->assertIdentical($o, Helper::arrayToObject($o));
  }
}
