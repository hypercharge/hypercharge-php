<?php
namespace Hypercharge;

require_once dirname(__DIR__).'/test_helper.php';
if(getenv('DEBUG') == '1') Config::setLogger(new StdoutLogger());

class CurlTest extends HyperchargeTestCase {

	function testPostToInvalidShouldThrowException() {
		$curl = new Curl('user', 'passw');
		$this->expectException(new Errors\NetworkError('http://localhost/eine/falsche/url', 'The requested URL returned error: 404'));
		$curl->xmlPost('http://localhost/eine/falsche/url', '<data />');
	}

	function testPostToValidUrlShouldReturnBody() {
		try {
			$curl = new Curl('user', 'passw');
			$response = $curl->xmlPost('https://test.hypercharge.net/', '');
			//'Sat Apr 27 09:41:53 UTC 2013'
			$this->assertPattern('/^\w\w\w \w\w\w \d\d? \d\d:\d\d:\d\d UTC \d\d\d\d$/', $response);
		}	catch(\Exception $exe)	{
			$this->fail($exe->getMessage());
		}
	}

	function testJsonGetToValidUrlShouldReturnBody() {
		$this->credentials('sandbox');
		try {
			$curl = new Curl($this->credentials->user, $this->credentials->password);
			$response = $curl->jsonGet(new v2\Url('sandbox', 'scheduler?per_page=2'));
			// parsed json
			$this->assertIsA($response, '\StdClass');
			$this->assertEqual('PaginatedCollection', $response->type);
			$this->assertEqual('RecurringSchedule', $response->entries_base_type);
			$this->assertEqual(2, $response->per_page);
			$this->assertEqual(1, $response->current_page);
		}	catch(\Exception $exe)	{
			$this->fail($exe->getMessage());
		}
	}

	function testJsonGetToInValidUrlShouldThrow() {
		$this->credentials('sandbox');
		try {
			$curl = new Curl($this->credentials->user, $this->credentials->password);
			$curl->jsonGet(new v2\Url('sandbox', 'scheduler/123455668798797'));
		}	catch(Errors\NetworkError $exe)	{
			$this->assertEqual('The requested URL returned error: 404', $exe->technical_message);
			return;
		}
		$this->fail('expected NetworkError with 404');
	}
}