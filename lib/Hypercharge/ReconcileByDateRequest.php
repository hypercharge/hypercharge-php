<?php
namespace Hypercharge;

/**
* see hypercharge api doc chapter "3.2 By date range"
*/
class ReconcileByDateRequest extends PaginatedByDateRequest {

	/**
	* the 'period' argument is not part of the Hypercharge XML API.
	* 'period' may be usefull for the usecases 'give me the transactions for day xy' or '... for one week from date xy'.
	* 'period' is a valid DateInterval (see http://php.net/manual/en/class.dateinterval.php) string or DateInterval allowed.
	* if period given: end_date will be calculated with start_date + period. If you specify end_date and period, given end_date will be ignored.
	* e.g. for the range 2013-06-12 00:00:00 until 2013-06-12 23:59:59
	* $request = new Hypercharge\ReconcileByDateRequest(array('start_date'=>'2013-06-12', 'period'=>'P1D');
	* or
	* $request = new Hypercharge\ReconcileByDateRequest(array('start_date'=>'2013-06-12', 'period'=>new DateInterval('P1D'));
	* or if you prefere to explicitely set the end_date (not fun on month endings)
	* $request = new Hypercharge\ReconcileByDateRequest(array('start_date'=>'2013-06-12', 'end_date'=>'2013-06-13');
	*
	* @param array $p optional format {start_date:optional String, end_date:optional String, period: optional String or DateInterval, page:optional int}
	* @throws Exception if period is invalid DateInterval string representation.
	*/
	function __construct(array $p = null) {
		parent::__construct($p);
	}

	/**
	* @param array $data
	* @return Hypercharge\PaginatedCollection containing instances of Hypercharge\Transaction
	*/
	function createResponse($data) {
		$ret = $this->createPaginatedCollection($data['payment_responses']['__attributes']);
		if(!empty($data['payment_responses']['payment_response'])) {
			// xml -> php quirks
			if(array_key_exists(0, $data['payment_responses']['payment_response'])) {
				foreach($data['payment_responses']['payment_response'] as $p) {
					$ret->push(new Transaction($p));
				}
			} else {
				$ret->push(new Transaction($data['payment_responses']['payment_response']));
			}
		}
		return $ret;
	}

	function getType(){
		return 'reconcile';
	}

}

