<?php
namespace Hypercharge;

class Curl implements IHttpsClient {
	private $user;
	private $passw;
	public $timeout = 30;

	function __construct($user, $passw) {
		$this->user = $user;
		$this->passw = $passw;
	}

	/**
	* @param string $url
	* @param string $xmlStr
	* @returns string response body
	* @throws Hypercharge\Errors\NetworkError
	*/
	function xmlPost($url, $xmlStr) {
		Config::getLogger()->debug(Helper::stripCc('POST '.$url.'  '. $xmlStr));
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_VERBOSE, false);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FAILONERROR, true);
		curl_setopt($ch, CURLOPT_POST, false); // yes, off here. curl turns it on by itself
		curl_setopt($ch, CURLOPT_USERAGENT, 'hypercharge-php '.VERSION);
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($ch, CURLOPT_USERPWD, $this->user.':'.$this->passw);
		curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: text/xml', 'Accept: text/xml'));
		curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlStr);

		$response_string = curl_exec($ch);

		//check for transport errors
		if(curl_errno($ch) != 0) {
			Config::getLogger()->error(Helper::stripCc(curl_error($ch).' '.print_r(curl_getinfo($ch), true)));
			$exe = new Errors\NetworkError($url, curl_error($ch), print_r(curl_getinfo($ch), true));
			curl_close($ch);
			throw $exe;
		}
		curl_close($ch);

		Config::getLogger()->debug(Helper::stripCc('response:  '. $response_string));

		return $response_string;
	}
}
