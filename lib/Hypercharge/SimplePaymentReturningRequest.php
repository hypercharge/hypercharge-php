<?php
namespace Hypercharge;

class SimplePaymentReturningRequest extends SimpleRequest {

	/**
	* @param array $data
	* @return Hypercharge\Payment
	*/
	function createResponse($data) {
		return new Payment($data['payment']);
	}

}