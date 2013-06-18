<?php
namespace Hypercharge;

class SimpleTransactionReturningRequest extends SimpleRequest {

	/**
	* @param array $data
	* @return Hypercharge\Transaction
	*/
	function createResponse($data) {
		if(isset($data['payment_response'])) {
			return new Transaction($data['payment_response']);
		} else if(isset($data['payment'])) {
			return new Transaction($data['payment']);
		}
		throw new Errors\Error('unexpected response format', print_r($data, true));
	}
}