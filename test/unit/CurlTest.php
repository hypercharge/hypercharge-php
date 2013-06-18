<?php
namespace Hypercharge;

require_once dirname(__DIR__).'/test_helper.php';

class CurlTest extends \UnitTestCase {

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
		}		catch(\Exception $exe)	{
			$this->fail($exe->getMessage());
		}
	}
}