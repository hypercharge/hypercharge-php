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
				return new Transaction($e);
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

		foreach($response->entries as $e) {
			$callback(new Transaction($e));
		}
	}


	/**
	* DO NOT USE, PRIVATE METHOD! only public for unittests
	* @param string $uid scheduler.unique_id
	* @param array $params hash keys (all optional): page, per_page
	* @return Hypercharge\RecurringScheduler
	*/
	static function index($uid, $params = array()) {
		Helper::validateUniqueId($uid);

		$params = Helper::arrayToObject($params);
		$error = JsonSchemaValidator::validate('scheduler_transactions_index', $params);
		if(!empty($error)) {
			throw new Errors\ValidationError($error);
		}
		$factory = Config::getFactory();
		$url      = $factory->createUrl(array('scheduler', $uid, 'transactions'), $params);
		$response = $factory->createHttpsClient()->jsonGet($url);

		if($response->entries_base_type != 'PaymentTransaction') {
			throw new Errors\ResponseFormatError('entries_base_type expected "PaymentTransaction" but was: '.$response->entries_base_type, $response);
		}
		return $response;
	}
}