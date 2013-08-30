<?php
namespace Hypercharge;

class SchedulerTransactions {

	/**
	* @param string $uid scheduler.unique_id
	* @param array $params hash keys (all optional): page, per_page
	* @return Hypercharge\RecurringScheduler
	*/
	static function page($uid, $params = array()) {
		JsonSchemaValidator::check('scheduler_transactions_index', $params);
		$factory = Config::getFactory();
		$url      = $factory->createUrl(array('scheduler', $uid, 'transactions'), $params);
		$response = $factory->createService()->get($url);

		$page = new Hypercharge\PaginatedCollection($response->page, $response->per_page, $response->total_count, $response->pages_count);
		foreach($response->entries as $e) {
			$page->push(new Transaction($e));
		}
		return $page;
	}

	/**
	* iterates over all pages and calls callback passing RecurringScheduler as parameter
	* @param string $uid scheduler.unique_id
	* @param array $params hash keys (all optional): page, per_page
	* @param function $callback
	* @return void
	*/
	static function each($uid, $params, $callback) {
		JsonSchemaValidator::check('scheduler_transactions_index', $params);
		$factory = Config::getFactory();
		$url      = $factory->createUrl(array('scheduler', $uid, 'transactions'), $params);
		$response = $factory->createService()->get($url);

		foreach($response->entries as $e) {
			$callback(new Transaction($e));
		}
	}

}