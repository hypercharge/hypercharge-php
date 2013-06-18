<?php
namespace Hypercharge;

class RecurringEventByDateRequest extends PaginatedByDateRequest {

	/**
	* @param array $p optional format {start_date:optional String, end_date:optional String, interval: optional String or DateInterval, page:optional int} @see Hypercharge\ReconcileByDateRequest
	* @throws Exception if interval is invalid DateInterval string representation.
	*/
	function __construct(array $p = null) {
		parent::__construct($p);
	}

	/**
	* @param array $data
	* @return Hypercharge\PaginatedCollection containing instances of Hypercharge\RecurringScheduler
	*/
	function createResponse($data) {
		// pure error response does not use stringent root node name
		if(isset($data['payment_response'])) {
			throw Errors\errorFromResponseHash($data['payment_response']);
		}
		if(empty($data['recurring_schedule']['recurring_events'])) {
			return new PaginatedCollection();
		}
		$ret = $this->createPaginatedCollection($data['recurring_schedule']['recurring_events']['__attributes']);
		unset($data['recurring_schedule']['recurring_events']['__attributes']);

		if(empty($data['recurring_schedule']['recurring_events']) || empty($data['recurring_schedule']['recurring_events']['recurring_event'])) {
			return $ret;
		}

		// xml -> php quirks
		if(array_key_exists(0, $data['recurring_schedule']['recurring_events']['recurring_event'])) {
			foreach($data['recurring_schedule']['recurring_events']['recurring_event'] as $p) {
				$ret->push(new RecurringEvent($p));
			}
		} else {
			$ret->push(new RecurringEvent($data['recurring_schedule']['recurring_events']['recurring_event']));
		}
		return $ret;
	}

	function getType(){
		return 'recurring_schedule';
	}

}
