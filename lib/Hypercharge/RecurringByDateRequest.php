<?php
namespace Hypercharge;

class RecurringByDateRequest extends PaginatedByDateRequest {

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
		$ret = $this->createPaginatedCollection($data['recurring_schedules']['__attributes']);
		if(!empty($data['recurring_schedules']['recurring_schedule'])) {
			// xml -> php quirks
			if(array_key_exists(0, $data['recurring_schedules']['recurring_schedule'])) {
				foreach($data['recurring_schedules']['recurring_schedule'] as $p) {
					$ret->push(new RecurringScheduler($p));
				}
			} else {
				$ret->push(new RecurringScheduler($data['recurring_schedules']['recurring_schedule']));
			}
		}
		return $ret;
	}

	function getType(){
		return 'recurring_schedule';
	}

}

