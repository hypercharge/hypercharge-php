<?php
namespace Hypercharge;

class RecurringEvent implements Serializable {

	public $unique_id, $recurring_schedule_unique_id
		, $status, $due_date, $finalized_at, $payment_transaction;

	function __construct($p) {
		Helper::assign($this, $p);

	}


	/**
	* gets a the RecurringScheduler details. You can geht its RecurringEvents with @see #eachEvent
	* @param string $channelTokens TODO remove!
	* @param string $uid the RecurringEvent.unique_id
	* @return RecurringScheduler
	*/
	static function find($channelToken, $uid) {
		// TODO
		// TODO remove recurring events from response
	}

	/**
	*
	* @param string $channelToken TODO remove!
	* @param string $scheduler_uid
	* @param array $request see RecurringEventByDateRequest
	* @param function $callback yields RecurringEvent
	*/
	static function each($channelToken, $scheduler_uid, $request, $callback) {
		// TODO
	}

}