<?php
namespace Hypercharge;

// workaround to load SCHEMA_VERSION
JsonSchemaValidator::schemaPathFor('foo');
// workaround to load VERSION
Config::ENV_LIVE;

class Curl implements IHttpsClient {
	private $user;
	private $passw;
	/**
	* curl handle
	*/
	private $ch;
	public $timeout = 30;

	function __construct($user, $passw) {
		$this->user = $user;
		$this->passw = $passw;
		$this->init();
	}

	function __destruct() {
		if(method_exists($this, 'close')) $this->close();
	}

	function close() {
		if($this->ch) curl_close($this->ch);
		$this->ch = 0;
	}

	private function init() {
		$this->ch = $ch = curl_init();
		curl_setopt($ch, CURLOPT_VERBOSE, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FAILONERROR, true);
		curl_setopt($ch, CURLOPT_POST, false); // yes, off here. curl turns it on by itself
		curl_setopt($ch, CURLOPT_USERAGENT, 'hypercharge-php '.VERSION);
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($ch, CURLOPT_USERPWD, $this->user.':'.$this->passw);
		curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
	}

	/**
	* @param string $contentType e.g. 'text/xml' or 'application/json'
	* @param array $header array of string e.g array("Content-Length: 1294587")
	* @return void
	*/
	function setHeader($contentType, $header) {
		$default = array(
			'Content-type: '.$contentType
			,'Accept: '     .$contentType
			,'X-Hypercharge-Schema: '.SCHEMA_VERSION
		);
		$header = array_merge($default, $header);
		curl_setopt($this->ch, CURLOPT_HTTPHEADER, $header);
	}

	/**
	* @param string $url
	* @param string $xmlStr
	* @return string response body
	* @throws Hypercharge\Errors\NetworkError
	*/
	function xmlPost($url, $xmlStr) {
		$this->debug('POST '.$url."\n". $xmlStr);
		$ch = $this->ch;

		curl_setopt($ch, CURLOPT_URL, $url);

		$this->setHeader('text/xml', array('Content-Length: '. strlen($xmlStr)));

		curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlStr);

		$response_string = curl_exec($ch);

		//check for transport errors
		if(curl_errno($ch) != 0) {
			$this->logError(curl_error($ch).' '.print_r(curl_getinfo($ch), true));
			$exe = new Errors\NetworkError(
					$url
					,curl_getinfo($ch, CURLINFO_HTTP_CODE)
					,curl_error($ch)
					,print_r(curl_getinfo($ch), true)
			);
			throw $exe;
		}

		$this->debug('response:  '. $response_string);

		return $response_string;
	}

	/**
	* @param Hypercharge\IUrl $url
	* @return StdClass parsed json response body
	* @throws Hypercharge\Errors\Error
	*/
	public function jsonGet(IUrl $url) {
		return $this->jsonRequest('GET', $url->get());
	}

	/**
	* @param string $url
	* @param array $data
	* @return StdClass parsed json response body
	* @throws Hypercharge\Errors\Error
	*/
	public function jsonPost(IUrl $url, $data) {
		return $this->jsonRequest('POST', $url->get(), json_encode($data));
	}

	/**
	* @param string $url
	* @param array $data
	* @return StdClass parsed json response body
	* @throws Hypercharge\Errors\Error
	*/
	public function jsonPut(IUrl $url, $data) {
		return $this->jsonRequest('PUT', $url->get(), json_encode($data));
	}

	/**
	* @param string $url
	* @return void
	* @throws Hypercharge\Errors\Error
	*/
	public function jsonDelete(IUrl $url) {
		return $this->jsonRequest('DELETE', $url->get());
	}


	/**
	* @param string $method http method 'GET', 'POST', 'PUT', 'DELETE'
	* @param string $url
	* @param string $json
	* @return StdClass parsed json response body
	* @throws Hypercharge\Errors\Error
	*/
	function jsonRequest($method, $url, $json = null) {
		$this->debug($method.' '.$url);

		curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, $method);

		$header = array();
		if($json !== null) {
			curl_setopt($this->ch, CURLOPT_POSTFIELDS, $json);
			$header[] = 'Content-Length: '.mb_strlen($json);
			$this->debug('data: '. $json);
		}

		$this->setHeader('application/json', $header);

		curl_setopt($this->ch, CURLOPT_URL, $url);

		// otherwhise curl_exec() returns no body in case of a 400
		curl_setopt($this->ch, CURLOPT_FAILONERROR, false);

		$response_string = curl_exec($this->ch);
		// response is " " sometimes
		$response_string = trim($response_string);

		$status = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
		$this->debug($status.' response: '. (empty($response_string)?'--EMPTY--':$response_string));

		$this->handleError(
			$url
			,$status
			,(string)$response_string
			,curl_error($this->ch)
			,curl_getinfo($this->ch)
		);
		if($response_string === 'null') {
			return null;
		}
		if(!empty($response_string)) {
			return json_decode($response_string);
		}
	}

	/**
	* private
	* @throws Hypercharge\Errors\Error
	*/
	function handleError($url, $status, $response, $curlError, $curlInfo) {
		// redirects are considered errors as hypercharge api doesn't do redirects
		if(200 <= $status && $status < 300) return;

		if(empty($curlError)) $curlError = "The requested URL returned error: ".$status;
		$this->logError($curlError."\n".print_r($curlInfo, true));
		if($status == 400 && !empty($response)) {
			$data = json_decode($response);
			if($data && @$data->error) throw Errors\errorFromResponseHash($data->error);
		}
		throw new Errors\NetworkError(
				$url
				,$status
				,$curlError.(!empty($response)?"\n".$response:'')
				,print_r($curlInfo, true)
		);
	}


	function debug($str) {
		Config::getLogger()->debug(Helper::stripCc($str));
	}

	function logError($str) {
		Config::getLogger()->error(Helper::stripCc($str));
	}
}
