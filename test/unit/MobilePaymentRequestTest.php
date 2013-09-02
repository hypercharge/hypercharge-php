<?php
namespace Hypercharge;

require_once dirname(__DIR__).'/test_helper.php';

class MobilePaymentRequestTest extends \UnitTestCase {

  function setUp() {
    XmlSerializer::$sort = false;
  }

  function testSerialize() {
    Config::setIdSeparator(false);

    $data = array(
      'type' => 'MobilePayment'
      ,'amount' => 1200
      ,'currency' => 'USD'
      ,'transaction_id' => 'test-1234'
      ,'usage' => 'the Usage Text'
      ,'notification_url' => 'https://my-server.com/hypercharge/payment-notification.php'
      ,'billing_address' => array(
        'first_name' => 'Hans'
        ,'last_name' => 'Müller'
        ,'address1' => 'Domstr. 12'
        ,'city' => 'München'
        ,'zip_code' => '80123'
        ,'country' => 'DE'
      )
      ,'transaction_types' => array('sale', 'pay_pal')
      ,'risk_params' => array('session_id' => 'abcd')
    );
    $request = new PaymentRequest($data);
    $request->validate();

    $this->assertIsA($request->billing_address, 'Hypercharge\Address');
    $this->assertIsA($request->transaction_types, 'array');
    $this->assertIsA($request->risk_params, 'Hypercharge\RiskParams');

    $serializer = new XmlSerializer();
    $str = $serializer->toXml($request);
    $this->assertEqual('<?xml version="1.0" encoding="UTF-8"?>
<payment>
  <type>MobilePayment</type>
  <amount>1200</amount>
  <currency>USD</currency>
  <transaction_id>test-1234</transaction_id>
  <usage>the Usage Text</usage>
  <notification_url>https://my-server.com/hypercharge/payment-notification.php</notification_url>
  <billing_address>
    <first_name>Hans</first_name>
    <last_name>Müller</last_name>
    <address1>Domstr. 12</address1>
    <city>München</city>
    <zip_code>80123</zip_code>
    <country>DE</country>
  </billing_address>
  <transaction_types>
    <transaction_type>sale</transaction_type>
    <transaction_type>pay_pal</transaction_type>
  </transaction_types>
  <risk_params>
    <session_id>abcd</session_id>
  </risk_params>
</payment>
', $str);
  }

  function testConstructWithObject() {
    Config::setIdSeparator(false);

    $address = new Address(array(
      'first_name' => 'Hans'
      ,'last_name' => 'Müller'
      ,'address1' => 'Domstr. 12'
      ,'city' => 'München'
      ,'zip_code' => '80234'
      ,'country' => 'DE'
    ));
    $this->assertEqual('Hans', $address->first_name);
    $this->assertEqual('DE', $address->country);

    $data = array(
      'type' => 'MobilePayment'
      ,'amount' => 3000
      ,'currency' => 'CHF'
      ,'usage' => 'the usage'
      ,'transaction_id' => 'test-1234'
      ,'notification_url' => 'https://my-server.com/hypercharge/notification.php'
      ,'billing_address' => $address
    );
    $r = new PaymentRequest($data);
    $r->validate();
    $this->assertIsA($r->billing_address, 'Hypercharge\Address');
    $this->assertEqual($r->billing_address->first_name, 'Hans');
    $serializer = new XmlSerializer();
    $str = $serializer->toXml($r);
    $this->assertEqual(
'<?xml version="1.0" encoding="UTF-8"?>
<payment>
  <type>MobilePayment</type>
  <amount>3000</amount>
  <currency>CHF</currency>
  <usage>the usage</usage>
  <transaction_id>test-1234</transaction_id>
  <notification_url>https://my-server.com/hypercharge/notification.php</notification_url>
  <billing_address>
    <first_name>Hans</first_name>
    <last_name>Müller</last_name>
    <address1>Domstr. 12</address1>
    <city>München</city>
    <zip_code>80234</zip_code>
    <country>DE</country>
  </billing_address>
</payment>
', $str);
  }

  function testValidatesThrowsExeption() {
    $data = array(
      'type' => 'MobilePayment'
      ,'amount' => -2000
      ,'currency' => 'WRONG'
      ,'transaction_id' => 'test-1234'
      ,'usage' => 'the Usage Text'
      ,'notification_url' => 'https://my-server.com/hypercharge/payment-notification.php'
    );
    $request = new PaymentRequest($data);
    try {
      $request->validate();
      $this->fail('Exception expected!');
    } catch(Errors\ValidationError $exe) {
      $this->assertEqual($exe->status_code, 50);
      $this->assertPattern('/^2 validation errors/', $exe->getMessage());
      $this->assertEqual('2 affected properties: payment.amount, payment.currency', $exe->technical_message);
      $this->assertEqual('payment.amount', $exe->errors[0]['property']);
      $this->assertEqual('must have a minimum value of 1', $exe->errors[0]['message']);

      $this->assertEqual('payment.currency', $exe->errors[1]['property']);
      $this->assertPattern('/^does not have a value in the enumeration Array/', $exe->errors[1]['message']);

      return;
    }
    $this->fail('Errors\ValidationError expected!');
  }
}