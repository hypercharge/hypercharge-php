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
		if(is_array($p)) {
			Helper::assign($this, $p);
		} else {
			Helper::assign_json($this, $p);
		}
	}

	/**
	* @param array $params hash keys (all optional): page, per_page, start_date_from, start_date_to, end_date_from, end_date_to, active
	* @return Hypercharge\PaginatedCollection filled with instances of Hypercharge\Scheduler
	*/
	static function page($params = array()) {
		JsonSchemaValidator::validate('scheduler_index', $params);
		$factory = Config::getFactory();
		$url      = $factory->createUrl('scheduler', $params);
		$response = $factory->createHttpsClient()->jsonGet($url);

		if($response->type != 'PaginatedCollection') {
			throw new Errors\ResponseFormatError("'type' expected to be PaginatedCollection", $response);
		}
		if($response->entries_base_type != 'RecurringSchedule') {
			throw new Errors\ResponseFormatError("'entries_base_type' expected to be RecurringSchedule", $response);
		}
		$page = new PaginatedCollection($response->current_page, $response->per_page, $response->total_entries);
		foreach($response->entries as $e) {
			$page->push(new Scheduler($e));
		}
		return $page;
	}

	/**
	* iterates over all entries in page and calls callback with Scheduler instance as parameter.
	* @param array $params hash keys (all optional): page, per_page, start_date_from, start_date_to, end_date_from, end_date_to, active
	* @return void
	*/
	static function each($params, $callback) {
		JsonSchemaValidator::validate('scheduler_index', $params);
		$factory = Config::getFactory();
		$url      = $factory->createUrl('scheduler', $params);
		$response = $factory->createHttpsClient()->jsonGet($url);

		if($response->type != 'PaginatedCollection') {
			throw new Errors\ResponseFormatError("'type' expected to be PaginatedCollection", $response);
		}
		if($response->entries_base_type != 'RecurringSchedule') {
			throw new Errors\ResponseFormatError("'entries_base_type' expected to be RecurringSchedule", $response);
		}

		foreach($response->entries as $e) {
			$callback(new Scheduler($e));
		}
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
	* @param array $data key-value-Hash, the Scheduler data e.g. array('payment_transaction_unique_id'=>'e1420438c52b4cb3a03a14a7e4fc16e1', 'interval'=>'monthly', 'start_date'=>'2014-03-28', 'amount' => 3900)
	* @return Hypercharge\Scheduler
	*/
	static function create($data) {
		$data = Helper::arrayToObject($data);
		$errors = JsonSchemaValidator::validate('scheduler_create', $data);
		if(!empty($errors)) throw new Errors\ValidationError($errors);

		$factory = Config::getFactory();
		$url      = $factory->createUrl(array('scheduler'));
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
		$data = Helper::arrayToObject($data);
		$errors = JsonSchemaValidator::validate('scheduler_update', $data);
		if(!empty($errors)) throw new Errors\ValidationError($errors);

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
		$factory->createHttpsClient()->delete($url);
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

}
