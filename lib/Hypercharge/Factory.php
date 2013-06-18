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
	function createHttpsClient($user, $passw) {
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
	* @param string $mode
	* @param string $channelToken
	* @param string $action
	* @return Hypercharge\TransactionUrl
	*/
	function createTransactionUrl($channelToken, $action = 'process') {
		return new TransactionUrl(Config::getMode(), $channelToken, $action);
	}

	/**
	* @param string $mode
	* @param string $channelToken
	* @param string $action
	* @return Hypercharge\TransactionUrl
	*/
	function createRecurringUrl($channelToken, $action = 'recurring') {
		return new TransactionUrl(Config::getMode(), $channelToken, $action);
	}
}