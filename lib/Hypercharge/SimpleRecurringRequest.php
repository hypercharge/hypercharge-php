<?php
namespace Hypercharge;

class SimpleRecurringRequest extends SimpleRequest {

	/**
	* @param array $data
	* @return Hypercharge\RecurringScheduler
	*/
	function createResponse($data) {
		if(isset($data['recurring_schedule'])) {
			return new RecurringScheduler($data['recurring_schedule']);
		}
		// TODO handle ERROR response

		throw new Errors\Error('unexpected response format', print_r($data, true));
	}
}