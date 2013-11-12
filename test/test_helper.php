<?php
namespace Hypercharge;

use \Mockery as m;

require_once(dirname(__DIR__).'/vendor/autoload.php');
require_once(dirname(__DIR__).'/vendor/simpletest/simpletest/autorun.php');


abstract class HyperchargeTestCase extends \UnitTestCase {

	function tearDown() {
		m::close();
		Config::setFactory(new Factory());
	}

	/**
	* @param string $fileName e.g. "payment_notification.json" for /test/fixtures/payment_notification.json
	* @return mixed array for *.json, string for other
	*/
	function fixture($fileName) {
		return self::parseIfJson(file_get_contents(__DIR__.'/fixtures/'.$fileName), $fileName);
	}

	/**
	* @param string $fileName e.g. "sale.json" for /vendor/hypercharge/hypercharge-schema/test/fixtures/sale.json
	* @return mixed array for *.json, string for other
	*/
	function schemaRequest($fileName) {
		return self::parseIfJson(JsonSchemaFixture::request($fileName)."\n", $fileName);
	}

	/**
	* @param string $fileName e.g. "sale.json" for /vendor/hypercharge/hypercharge-schema/test/fixtures/sale.json
	* @return mixed array for *.json, string for other
	*/
	function schemaResponse($fileName) {
		return self::parseIfJson(JsonSchemaFixture::response($fileName)."\n", $fileName);
	}

	/**
	* @param string $fileName e.g. "sale.json" for /vendor/hypercharge/hypercharge-schema/test/fixtures/sale.json
	* @return mixed array for *.json, string for other
	*/
	function schemaNotification($fileName) {
		return self::parseIfJson(JsonSchemaFixture::notification($fileName), $fileName);
	}

	/**
	* @return string|array
	*/
	static function parseIfJson($str, $name) {
		if(preg_match('/\.json$/', $name)) return json_decode($str, true);
		return $str;
	}

	/**
	* @param string xml
	* @return array
	*/
	function parseXml($xml) {
		$dom = new \SimpleXMLElement($xml);
		return XmlSerializer::dom2hash($dom);
	}

	/**
	* you can set by using shell environment variable e.g. CREDENTIALS=development php test/remote.php
	* sets $this->credentials to the part ($name) of credentials.json
	* you should use it in test setUp()
	* please do not confuse credentials name with Config:ENV_*
	* sets $this->credentials to object { user:String, password:String, ... }
	*
	* @param string $name  see first level in /test/credentials.json
	* @return boolean false if no remote tests possible (running in travis continuous integration server)
	* @throws Exception
	*/
	function credentials($name=null) {
		if(getenv('TRAVIS')) {
			$this->skip('remote credentials not yet implemented for travis ci');
			return false;
		}

		if($name === null) {
			$name = getenv('CREDENTIALS');
		}
		if(empty($name)) {
			$name = 'sandbox';
		}
		$file = __DIR__.'/credentials.json';
		if(!file_exists($file)) {
			// no trigger_error() because simpletest considers even E_USER_NOTICE as failure :-|
			echo "File does not exist $file. See README.md chapter 'Remote Tests' how to setup credentials for testing.\n";
			return false;
		}
		$str = file_get_contents($file);
		$all = json_decode($str);
		if(!$all) {
			throw new \Exception("could not load json data from $file\n");
		}
		if(!isset($all->{$name})) {
			throw new \Exception("no credentials '$name' in $file\npossible values are: ".implode(', ',array_keys(get_object_vars($all))));
		}
		$this->credentials = $all->{$name};
		$this->credentials->name = $name;

		Config::set($this->credentials->user, $this->credentials->password, Config::ENV_SANDBOX);

		if($name == 'development') {
			$this->mockUrls();
		}
		return true;
	}

	function mockUrls() {
		$mode = Config::getMode();

		$c = $this->credentials;
		$factory = m::mock('Hypercharge\Factory[createPaymentUrl,createTransactionUrl,createUrl]');

		////////////
		// Payments
		foreach(array('cancel', 'void', 'capture', 'refund', 'reconcile') as $action) {
			$url = m::mock('Hypercharge\PaymentUrl[getUrl]', array($mode, $action));
			$url->shouldReceive('getUrl')->andReturn($c->paymentHost.'/payment');
			$factory->shouldReceive('createPaymentUrl')->with($action)->andReturn($url);
		}
		// action = 'create'
		$url = m::mock('Hypercharge\PaymentUrl[getUrl]', array($mode));
		$url->shouldReceive('getUrl')->andReturn($c->paymentHost.'/payment');
		$factory->shouldReceive('createPaymentUrl')->with()->andReturn($url);

		///////////////
		// Transactions
		//
		foreach(array('process', 'reconcile', 'reconcile/by_date') as $action) {
			// USD channel
			$url = m::mock('Hypercharge\TransactionUrl[getUrl]', array($mode, $c->channelTokens->USD, $action));
			$url->shouldReceive('getUrl')->andReturn($c->gatewayHost);
			$factory->shouldReceive('createTransactionUrl')->with($c->channelTokens->USD, $action)->andReturn($url);
			// EUR channel
			$url = m::mock('Hypercharge\TransactionUrl[getUrl]', array($mode, $c->channelTokens->EUR, $action));
			$url->shouldReceive('getUrl')->andReturn($c->gatewayHost);
			$factory->shouldReceive('createTransactionUrl')->with($c->channelTokens->EUR, $action)->andReturn($url);


			$url = m::mock('Hypercharge\TransactionUrl[getUrl]', array($mode, 'wrong_channel_token', $action));
			$url->shouldReceive('getUrl')->andReturn($c->gatewayHost);
			$factory->shouldReceive('createTransactionUrl')->with('wrong_channel_token', $action)->andReturn($url);
		}

		Config::setFactory($factory);
	}

	// function mockV2Url() {
	// 	$mode = Config::getMode();

	// 	$c = $this->credentials;
	// 	$factory = m::mock('Hypercharge\Factory[createV2Url]');
	// 	$url = m::mock('Hypercharge\V2\Url[getUrl]');
	// 	$url->shouldReceive('getUrl')->andReturn($c->gatewayHost.'/v2');

	// 	return array($factory, $url);
	// }


	/**
	* mocks the network layer
	* @param int $times how often XmlWebservice::call is expected to be called
	* @return Mockery of Hypercharge\Curl
	*/
	function curlMock($times=1) {
		$curl = m::mock('curl');
		$factory = m::mock('Hypercharge\Factory[createHttpsClient]');
		$factory->shouldReceive('createHttpsClient')->times($times)->with('the user', 'the passw')->andReturn($curl);
		Config::setFactory($factory);
		Config::set('the user', 'the passw', Config::ENV_SANDBOX);
		Config::setIdSeparator(false);
		return $curl;
	}

	function expect_Curl_jsonRequest() {
		$curl = m::mock('Hypercharge\Curl[jsonRequest][close]', array(Config::getUser(), Config::getPassword()));
		$curl->shouldReceive('close');

		$factory = m::mock('Hypercharge\Factory[createHttpsClient]');
		$factory->shouldReceive('createHttpsClient')->andReturn($curl);
		Config::setFactory($factory);
		return $curl->shouldReceive('jsonRequest');
	}
}
