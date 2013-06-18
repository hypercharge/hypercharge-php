<?php
namespace Hypercharge;

interface IClient {

}

interface IUrl {
	/**
	* @return string the full url incl. action
	*/
	function get();
}

interface Typed {
	/**
	* for Payments:    'WpfPayment', 'MobilePayment'
	* for Transactions:'sale', 'authorize', 'capture', 'refund', 'void', ...
	* @returns string
	*/
	function getType();
}

interface Converter {

	/**
	* @param array $all array of string
	* @return array or null if $all was null
	*/
	function fromXml(array $all = null);

	/**
	* @param array $all array of string
	* @param DOMNode $parent the xml node receiving items of $all <transaction_type>ITEM</transaction_type>
	* @return void
	*/
	function toXml($all, \DOMNode $parent);
}

interface Serializable {

}

interface IRequest extends Serializable, Typed {

	/**
	* @param array $data
	* @returns Hypercharge\IResponse
	*/
	function createResponse($data);

	/**
	* @throws Hypercharge\Errors\ValidationError
	* @return void
	*/
	function validate();
}

interface IResponse extends Serializable, Typed {

}

interface INotification {
	/**
	* @protected
	* @param string $password merchant api password
	*/
	function verify($password);

	/**
	* do not trust Notification if isVerified() returns false!
	* @return boolean
	*/
	public function isVerified();

	/**
	* checks if Payment.status == 'approved'
	* @return boolean
	*/
	public function isApproved();

	/**
	* returns xml echo
	*
	* <?xml version="1.0" encoding="UTF-8"?>
	* <notification_echo>
	*   <unique_id>26aa150ee68b1b2d6758a0e6c44fce4c</unique_id>
	* </notification_echo>
	*
	* @return string xml
	*/
	function ack();
}

interface IHttpsClient {
	/**
	* @param string $url
	* @param string $xmlStr
	* @returns string response body
	* @throws Hypercharge\NetworkError
	*/
	function xmlPost($url, $xmlStr);
}

interface IFactory {

	/**
	* @return Hypercharge\XmlWebservice
	*/
	function createWebservice();

	/**
	* @param string $user
	* @param string $passw
	* @return Hypercharge\IHttpsClient
	*/
	function createHttpsClient($user, $passw);
}

interface ILogger {
	const DEBUG = 1;
	const INFO  = 2;
	const ERROR = 3;

	/**
	* @param string $str
	*/
	function debug($str);

	/**
	* @param string $str
	*/
	function info($str);

	/**
	* @param string $str
	*/
	function error($str);
}




















