<?php
namespace Hypercharge;

class PaymentRequest implements IRequest {

	private static $allowedTypes = array(
			 'WpfPayment'
			,'MobilePayment'
	);

	/**
	* $data hash of format:
	* {
	*		type: "WpfPayment",
	*		amount: 1000,
	*		currency: "USD",
	*		...
	*	}
	*
	* @param array $data
	*/
	function __construct($data) {

		if(!in_array(@$data['type'], self::$allowedTypes)) {
			throw Errors\ValidationError::create('type', 'must be one of "'.join(self::$allowedTypes, '", "').'" but is: "'.@$data['type'].'"');
		}
		Helper::assign($this, $data);

		if(isset($this->transaction_id)) {
			$this->transaction_id = Helper::appendRandomId($this->transaction_id);
		}
	}

	/**
	* @param array $data parsed xml response
	* @return Payment
	* @throws Errors\Error
	*/
	function createResponse($data) {
		$payment = new Payment($data['payment']);
		if(!$payment->unique_id) {
			if($payment->error) {
				throw $payment->error;
			}
			Config::getLogger()->error('unknown response error. data:'. Helper::stripCc(print_r($data, true)));
			throw new Errors\Error('unknown response error');
		}
		return $payment;
	}

	function getType() {
		return $this->type;
	}

	/**
	* @return array of strings
	*/
	static function getAllowedTypes() {
		return self::$allowedTypes;
	}

	function validate() {
		$errors = JsonSchema::check($this);
		if($errors) throw new Errors\ValidationError($errors);
	}

}
