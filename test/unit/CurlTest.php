<?php
namespace Hypercharge;

require_once dirname(__DIR__).'/test_helper.php';
if(getenv('DEBUG') == '1') Config::setLogger(new StdoutLogger());

class CurlTest extends HyperchargeTestCase {

	function testPostToInvalidShouldThrowException() {
		$curl = new Curl('user', 'passw');
		try {
			$curl->xmlPost('https://test.hypercharge.net/eine/falsche/url', '<data />');
		} catch(Errors\NetworkError $exe) {
			$this->assertEqual('https://test.hypercharge.net/eine/falsche/url', $exe->url);
			$this->assertIdentical(404, $exe->http_status);
			$this->assertEqual('The requested URL returned error: 404', $exe->technical_message);
			$this->assertPattern('/^Array\n\(\n/', $exe->body);
		}
	}

	function testPostToValidUrlShouldReturnBody() {
		try {
			$curl = new Curl('user', 'passw');
			$response = $curl->xmlPost('https://test.hypercharge.net/', '');
			// $response = '2013-11-12 10:54:00 BNN'; // invalid time zone
			// $response = 'Sat Apr 27 09:41:53 UTC 2013'; // valid
			// $response = '2013-11-12 19:41:53 UTC'; // valid
			$parsed = \date_parse($response);
			$this->assertIsA($parsed, 'array', $response);
			$this->assertEqual(0, $parsed['error_count'], 'response: "'.$response.'" when parsed, error_count should be 0 but is: '.print_r($parsed, true));
			$this->assertEqual(array(), $parsed['errors'], 'errors should be empty: '.print_r($parsed, true));
			$this->assertEqual(array(), $parsed['warnings'], 'warnings should be empty: '.print_r($parsed, true));
			// too restrictive - format slightly changes when server packages updated
			//$this->assertPattern('/^\w\w\w \w\w\w \d\d? [0-2]\d:[0-5]\d:[0-5]\d UTC 20\d\d$/', $response);
		}	catch(\Exception $exe)	{
			$this->fail($exe->getMessage());
		}
	}

	// TODO fix 500 error #4  GET https://test.hypercharge.net/v2/scheduler?per_page=2
	// function testJsonGetToValidUrlShouldReturnBody() {
	// 	if(!$this->credentials('sandbox')) return;

	// 	try {
	// 		$curl = new Curl($this->credentials->user, $this->credentials->password);
	// 		$response = $curl->jsonGet(new v2\Url('sandbox', 'scheduler?per_page=2'));
	// 		// parsed json
	// 		$this->assertIsA($response, '\StdClass');
	// 		$this->assertEqual('PaginatedCollection', $response->type);
	// 		$this->assertEqual('RecurringSchedule', $response->entries_base_type);
	// 		$this->assertEqual(2, $response->per_page);
	// 		$this->assertEqual(1, $response->current_page);
	// 	}	catch(\Exception $exe)	{
	// 		$this->fail($exe->getMessage());
	// 	}
	// }

	function testJsonGetToInValidHostShouldThrow() {
		if(!$this->credentials('sandbox')) return;
		$response = null;
		try {
			$curl = new Curl($this->credentials->user, $this->credentials->password);
			$response = $curl->jsonRequest('GET', 'http://www.wrong-hostname.de/foo/bar');
		}	catch(Errors\NetworkError $exe)	{
			$this->assertEqual(10, $exe->status_code);
			// if you're in a LAN connected to internet via a DSL router, from your provider you might get a 302 redirect to their search engine :-|
			// instead of a 404
			if($exe->http_status == 404) {
				$this->assertPattern('/^'.preg_quote('Could not resolve host: www.wrong-hostname.de').'/', $exe->technical_message);
			}
			return;
		}
		print_r($response);

		$this->fail('expected NetworkError but got none!');
	}

	function testJsonGetToInValidUrlShouldThrow() {
		if(!$this->credentials('sandbox')) return;

		try {
			$curl = new Curl($this->credentials->user, $this->credentials->password);
			$curl->jsonGet(new v2\Url('sandbox', 'scheduler/123455668798797'));
		}	catch(Errors\NetworkError $exe)	{
			$this->assertEqual('The requested URL returned error: 404', $exe->technical_message);
			return;
		}
		$this->fail('expected NetworkError with 404');
	}

	function testJsonGetUnauthorizedShouldThrow() {
		if(!$this->credentials('sandbox')) return;

		try {
			$curl = new Curl('user', 'password');
			$curl->jsonGet(new v2\Url('sandbox', 'scheduler/123455668798797'));
		}	catch(Errors\NetworkError $exe)	{
			$this->assertEqual('The requested URL returned error: 401', $exe->technical_message);
			return;
		}
		$this->fail('expected NetworkError with 401');
	}

	function testHandleErrorSilentIfCodeLt400() {
		$curl = new Curl('user', 'passw');
		$curl->handleError('http://url', 200, '', null, array());
	}

	function testHandleError300() {
		$this->expectException('Hypercharge\Errors\NetworkError');
		$curl = new Curl('user', 'passw');
		$curl->handleError('http://url', 300, '', null, array());
	}

	function testHandleError302() {
		$this->expectException('Hypercharge\Errors\NetworkError');
		$curl = new Curl('user', 'passw');
		$curl->handleError('http://url', 302, '<html>redirect bla</html>', null, array());
	}

	function testHandleError400() {
		$this->expectException('Hypercharge\Errors\InputDataInvalidError');
		$curl = new Curl('user', 'passw');
		$curl->handleError('http://url', 400, JsonSchemaFixture::response('scheduler_error.json'), null, array());
	}

	function testHandleError401() {
		$this->expectException('Hypercharge\Errors\NetworkError');
		$curl = new Curl('user', 'passw');
		$curl->handleError('http://url', 401, '', null, array());
	}

	function testHandleError500() {
		$this->expectException('Hypercharge\Errors\NetworkError');
		$curl = new Curl('user', 'passw');
		$curl->handleError('http://url', 500, '', null, array());
	}
}