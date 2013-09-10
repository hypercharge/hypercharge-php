<?php
namespace Hypercharge;

/**
* introduced Factory so instances of some core features can be mocked
*/
class Factory implements IFactory {

	/**
	* @return Hypercharge\XmlWebservice
	*/
	function createWebservice() {
		return new XmlWebservice();
	}

	/**
	* @param string $user
	* @param string $passw
	* @return Hypercharge\IHttpsClient
	*/
	function createHttpsClient($user=null, $passw=null) {
		if($user  === null) $user  = Config::getUser();
		if($passw === null) $passw = Config::getPassword();
		return new Curl($user, $passw);
	}

	/**
	* @param string $action
	* @return Hypercharge\PaymentUrl
	*/
	function createPaymentUrl($action = 'create') {
		return new PaymentUrl(Config::getMode(), $action);
	}

	/**
	* @param string $channelToken
	* @param string $action
	* @return Hypercharge\TransactionUrl
	*/
	function createTransactionUrl($channelToken, $action = 'process') {
		return new TransactionUrl(Config::getMode(), $channelToken, $action);
	}

	/**
	* see Hypercharge\v2\Url#__construct
	* @param string|array $action e.g. 'scheduler' or an array of string e.g. array('scheduler', '<UNIQUE_ID>', 'transactions')
	* @param array $params GET params as key-value hash e.g. array('page'=>1, 'per_page'=>30) examples see unittest
	* @return Hypercharge\v2\Url
	*/
	function createUrl($action, $params=array()) {
		return new v2\Url(Config::getMode(), $action, $params);
	}
}