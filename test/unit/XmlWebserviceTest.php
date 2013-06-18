<?php
namespace Hypercharge;
require_once dirname(__DIR__).'/test_helper.php';
use \Mockery as m;

class XmlWebserviceTest extends HyperchargeTestCase {

	function tearDown() {
		m::close();
		Config::setFactory(new Factory());
	}

	function testCall() {
		XmlSerializer::$sort = true;

		$data = $this->schemaRequest('WpfPayment.json');
		$requestXml  = $this->schemaRequest('WpfPayment.xml');
		$responseXml = $this->schemaResponse('WpfPayment_new.xml');

		$this->curlMock()
			->shouldReceive('xmlPost')
			->with('https://testpayment.hypercharge.net/payment', $requestXml)
			->once()
			->andReturn($responseXml);

		$request = new PaymentRequest($data['payment']);

		$url = new PaymentUrl(Config::getMode());
		$ws = new XmlWebservice();
		$response = $ws->call($url, $request);
		$this->assertIsA($response, 'Hypercharge\Payment');
		$this->assertEqual($response->type, 'WpfPayment');
	}

}