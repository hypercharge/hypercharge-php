<?php
namespace Hypercharge;

class Scheduler implements Serializable {

	const WEEKLY      = 'weekly';
	const MONTHLY     = 'monthly';
	const SEMIMONTHLY = 'semimonthly';
	const QUARTERLY   = 'quarterly';
	const SEMIANUALLY = 'semianually';
	const ANUALLY     = 'anually';


	// public $unique_id, $type, $amount, $currency, $active, $start_date, $end_date;

	function __construct($p) {
		Helper::assign($this, $p);
	}

	/**
	* @param array $params hash keys (all optional): page, per_page, start_date_from, start_date_to, end_date_from, end_date_to, active
	* @return Hypercharge\PaginatedCollection filled with instances of Hypercharge\Scheduler
	*/
	static function page($params = array()) {
		$response = self::index($params);

		return new PaginatedCollection($response, function($e) { return new Scheduler($e); });
	}

	/**
	* iterates over all entries in page and calls callback with Scheduler instance as parameter.
	* @param array $params hash keys (all optional): page, per_page, start_date_from, start_date_to, end_date_from, end_date_to, active
	* @return void
	*/
	static function each($params, $callback) {
		$response = self::index($params);

		foreach($response->entries as $e) {
			$callback(new Scheduler($e));
		}
	}

	/**
	* DO NOT USE, PRIVATE METHOD! must be public for unittests.
	* @param array|object $params
	* @return object response
	*/
	static function index($params) {
		JsonSchema::validate('scheduler_index', $params);

		$factory = Config::getFactory();
		$url      = $factory->createUrl('scheduler', $params);
		$response = $factory->createHttpsClient()->jsonGet($url);

		if($response->type != 'PaginatedCollection') {
			throw new Errors\ResponseFormatError("'type' expected to be PaginatedCollection", $response);
		}
		if($response->entries_base_type != 'RecurringSchedule') {
			throw new Errors\ResponseFormatError("'entries_base_type' expected to be RecurringSchedule", $response);
		}
		return $response;
	}

	/**
	* gets a the Scheduler details. You can geht its RecurringEvents with @see #eachEvent
	* @param string $uid the scheduler.unique_id
	* @return Hypercharge\Scheduler
	*/
	static function find($uid) {
		Helper::validateUniqueId($uid);
		$factory = Config::getFactory();
		$url      = $factory->createUrl(array('scheduler', $uid));
		$response = $factory->createHttpsClient()->jsonGet($url);

		return new Scheduler($response);
	}

	/**
	* create a Scheduler for a recurreable Transaction (payment_transaction_unique_id)
	* $data e.g. array('payment_transaction_unique_id'=>'e1420438c52b4cb3a03a14a7e4fc16e1', 'interval'=>'monthly', 'start_date'=>'2014-03-28', 'amount' => 3900)
	* @see hypercharge-schema scheduler_create.json for details
	* @param array $data key-value-Hash, the Scheduler fields to update
	* @return Hypercharge\Scheduler
	*/
	static function create($data) {
		JsonSchema::validate('scheduler_create', $data);

		$factory = Config::getFactory();
		$url      = $factory->createUrl('scheduler');
		$response = $factory->createHttpsClient()->jsonPost($url, $data);

		return new Scheduler($response);
	}

	/**
	* @param string $uid the scheduler.unique_id
	* @param array $data key-value-Hash, Scheduler fields to update
	* @return Hypercharge\Scheduler
	*/
	static function update($uid, $data) {
		Helper::validateUniqueId($uid);
		JsonSchema::validate('scheduler_update', $data);

		$factory = Config::getFactory();
		$url      = $factory->createUrl(array('scheduler', $uid));
		$response = $factory->createHttpsClient()->jsonPut($url, $data);

		return new Scheduler($response);
	}

	/**
	* @param string $uid the scheduler.unique_id
	* @return void
	*/
	static function delete($uid) {
		Helper::validateUniqueId($uid);

		$factory = Config::getFactory();
		$url     = $factory->createUrl(array('scheduler', $uid));
		$factory->createHttpsClient()->jsonDelete($url);
	}

	/**
	* activate a Scheduler
	* @param string $uid the scheduler.unique_id
	* @return Hypercharge\Scheduler
	*/
	static function activate($uid) {
		return self::update($uid, array('active'=>true));
	}

	/**
	* deactivate a Scheduler
	* @param string $uid the scheduler.unique_id
	* @return Hypercharge\Scheduler
	*/
	static function deactivate($uid) {
		return self::update($uid, array('active'=>false));
	}

	/**
	* returns the date when the next Transaction is scheduled for
	* @param string $uid the scheduler.unique_id
	* @return string date 'yyyy-mm-dd' e.g. '2014-03-28'
	*/
	static function next($uid) {
		Helper::validateUniqueId($uid);

		$factory = Config::getFactory();
		$url     = $factory->createUrl(array('scheduler', $uid, 'next'));
		$response = $factory->createHttpsClient()->jsonGet($url);

		if($response === null) return;

		if(!(is_object($response) && @$response->type == 'RecurringEvent' && !empty($response->due_date))) {
			throw new Errors\ResponseFormatError("expected object type=RecurringEvent", $response);
		}
		return $response->due_date;
	}

}
