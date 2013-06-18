<?php
namespace Hypercharge;

class RecurringScheduler implements Serializable {

	const WEEKLY      = 'weekly';
	const MONTHLY     = 'monthly';
	const SEMIMONTHLY = 'semimonthly';
	const QUARTERLY   = 'quarterly';
	const SEMIANUALLY = 'semianually';
	const ANUALLY     = 'anually';

	function __construct($p) {
		Helper::assign($this, $p);

		if(isset($this->amount)) $this->amount = (integer) $this->amount;
		if(isset($this->active)) $this->active = $this->active == 'true';
		if(isset($this->enabled)) $this->enabled = $this->enabled == 'true';
	}

	/**
	* @param string $channelToken
	* @param mixed $request optional array or Hypercharge\RecurringByDateRequest
	* @return Hypercharge\RecurringScheduler
	*/
	static function page($channelToken, $request = array()) {
		$request = Helper::ensure('RecurringByDateRequest', $request);
		$request->validate();
		$factory = Config::getFactory();
		$url = $factory->createRecurringUrl($channelToken, 'recurring/schedules_by_date');
		return $factory->createWebservice()->call($url, $request);
	}

	/**
	* iterates over all pages and calls callback passing RecurringScheduler as parameter
	* @param string $channelToken
	* @param mixed $request array or Hypercharge\RecurringByDateRequest
	* @param function $callback parameter (Hypercharge\RecurringScheduler $rs)
	*/
	static function each($channelToken, $request, $callback) {
		$request = Helper::ensure('RecurringByDateRequest', $request);
		$url = Config::getFactory()->createRecurringUrl($channelToken, 'recurring/schedules_by_date');
		$request->each($url, $callback);
	}

	/**
	* gets a the RecurringScheduler details. You can geht its RecurringEvents with @see #eachEvent
	* @param string $channelTokens TODO remove!
	* @param string $uid the recurring_schedule.unique_id
	* @return Hypercharge\RecurringScheduler
	*/
	static function find($channelToken, $uid) {
		// TODO
		// TODO remove recurring events from response
	}

	/**
	* terminates a RecurringScheduler permanently
	* @param string $channelToken the channel token
	* @param string $uid the recurring_schedule.unique_id
	* @return Hypercharge\RecurringScheduler
	*/
	static function terminate($channelToken, $uid) {
		$request = new SimpleRecurringRequest('recurring', $uid);
		$request->validate();
		$factory = Config::getFactory();
		$url = $factory->createRecurringUrl($channelToken, 'recurring/unsubscribe');
		return $factory->createWebservice()->call($url, $request);
	}

	/**
	* activate a RecurringScheduler
	* @param string $channelToken the channel token
	* @param string $uid the recurring_schedule.unique_id
	* @return Hypercharge\RecurringScheduler
	*/
	static function activate($channelToken, $uid) {
		$request = new SimpleRecurringRequest('recurring', $uid);
		$request->validate();
		$factory = Config::getFactory();
		$url = $factory->createRecurringUrl($channelToken, 'recurring/activate');
		return $factory->createWebservice()->call($url, $request);
	}

	/**
	* deactivate a RecurringScheduler
	* @param string $channelToken the channel token
	* @param string $uid the recurring_schedule.unique_id
	* @return Hypercharge\RecurringScheduler
	*/
	static function deactivate($channelToken, $uid) {
		$request = new SimpleRecurringRequest('recurring', $uid);
		$request->validate();
		$factory = Config::getFactory();
		$url = $factory->createRecurringUrl($channelToken, 'recurring/deactivate');
		return $factory->createWebservice()->call($url, $request);
	}

}
