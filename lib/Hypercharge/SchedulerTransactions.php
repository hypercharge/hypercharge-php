<?php
namespace Hypercharge;

class SchedulerTransactions {

	/**
	* @param string $uid scheduler.unique_id
	* @param array $params hash keys (all optional): page, per_page
	* @return Hypercharge\RecurringScheduler
	*/
	public static function page($uid, $params = array()) {
		$response = self::index($uid, $params);

		$page = new PaginatedCollection($response
			,function($entry) {
				return new Transaction($entry);
			}
		);

		return $page;
	}

	/**
	* iterates over all pages and calls callback passing RecurringScheduler as parameter
	* @param string $uid scheduler.unique_id
	* @param array $params hash keys (all optional): page, per_page
	* @param function $callback
	* @return void
	*/
	public static function each($uid, $params, $callback) {
		$response = self::index($uid, $params);

		foreach($response->entries as $entry) {
			$callback(new Transaction($entry));
		}
	}


	/**
	* DO NOT USE, PRIVATE METHOD! must be public for unittests
	* @param string $uid scheduler.unique_id
	* @param array $params hash keys (all optional): page, per_page
	* @return Hypercharge\RecurringScheduler
	*/
	static function index($uid, $params = array()) {
		Helper::validateUniqueId($uid);

		JsonSchema::validate('scheduler_transactions_index', $params);

		$factory = Config::getFactory();
		$url      = $factory->createUrl(array('scheduler', $uid, 'transactions'), $params);
		$response = $factory->createHttpsClient()->jsonGet($url);

		if($response->entries_base_type != 'PaymentTransaction') {
			throw new Errors\ResponseFormatError('entries_base_type expected "PaymentTransaction" but was: '.$response->entries_base_type, $response);
		}
		return $response;
	}
}