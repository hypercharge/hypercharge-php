<?php
namespace Hypercharge;

class Transaction implements IResponse {
	const STATUS_APPROVED            = 'approved';
	const STATUS_DECLINED            = 'declined';
	const STATUS_PENDING             = 'pending';
	const STATUS_PENDING_ASYNC       = 'pending_async';
	const STATUS_ERROR               = 'error';
	const STATUS_VOIDED              = 'voided';
	const STATUS_CHARGEBACKED        = 'chargebacked';
	const STATUS_REFUNDED            = 'refunded';
	const STATUS_CHARGEBACK_REVERSED = 'chargeback_reversed';
	const STATUS_PRE_ARBITRATED      = 'pre_arbitrated';
	const STATUS_REJECTED            = 'rejected';
	const STATUS_CAPTURED            = 'captured';

	public $unique_id;
	public $transaction_type;
	// alias $transaction_type
	public $type;
	public $error = null;
	public $redirect_url = null;

	function __construct($p) {
		Helper::assign($this, $p);

		$this->type = $this->transaction_type;
		$this->error = Errors\errorFromResponseHash($p);
		unset($this->code);
		unset($this->message);
		unset($this->technical_message);
	}

	/**
	* convinience method
	* @return string 'sale', 'authorize', 'capture', 'refund', 'void', ...
	*/
	function getType() {
		return $this->transaction_type;
	}

	/**
	* @return boolean true if transaction successfully created and response has a redirect_url
	*/
	function shouldRedirect() {
		return $this->isPersistentInHypercharge() && !empty($this->redirect_url);
	}

	// stati
	function isApproved() {
		return $this->status == self::STATUS_APPROVED;
	}
	function isDeclined() {
		return $this->status == self::STATUS_DECLINED;
	}
	function isPending() {
		return $this->status == self::STATUS_PENDING;
	}
	function isPendingAsync() {
		return $this->status == self::STATUS_PENDING_ASYNC;
	}
	function isError() {
		return $this->status == self::STATUS_ERROR;
	}
	function isVoided() {
		return $this->status == self::STATUS_VOIDED;
	}
	function isChargebacked() {
		return $this->status == self::STATUS_CHARGEBACKED;
	}
	function isRefunded() {
		return $this->status == self::STATUS_REFUNDED;
	}
	function isChargebackReversed() {
		return $this->status == self::STATUS_CHARGEBACK_REVERSED;
	}
	function isPreArbitrated() {
		return $this->status == self::STATUS_PRE_ARBITRATED;
	}
	function isRejected() {
		return $this->status == self::STATUS_REJECTED;
	}
	function isCaptured() {
		return $this->status == self::STATUS_CAPTURED;
	}


	function isPersistentInHypercharge() {
		return !empty($this->unique_id)
		    && !empty($this->transaction_id);
	}
	function isFatalError() {
		return !$this->isPersistentInHypercharge();
	}

	function __toString() {
		return get_class()
			." { type: "         .@$this->type
			.", unique_id: "     .@$this->unique_id
			.", status: "        .@$this->status
			.", currency: "      .@$this->currency
			.", amount: "        .@$this->amount
			.", transaction_id: ".@$this->transaction_id
			.", timestamp: "     .@$this->timestamp
			.", error: "         .@$this->error
			."}";
	}

	//
	// Hypercharge Payment Gateway API Calls
	//

	/**
	* @param string $channelToken
	* @param mixed $request array or Hypercharge\TransactionRequest
	* @return Hypercharge\Transaction
	* @throws Hypercharge\Errors\Error if no transaction created on hypercharge server
	*/
	static function sale($channelToken, $request) {
		return self::_call('sale', $request, $channelToken);
	}

	/**
	* @param string $channelToken
	* @param mixed $request array or Hypercharge\TransactionRequest
	* @return Hypercharge\Transaction
	* @throws Hypercharge\Errors\Error if no transaction created on hypercharge server
	*/
	static function authorize($channelToken, $request) {
		return self::_call('authorize', $request, $channelToken);
	}

	/**
	* @param string $channelToken
	* @param mixed $request array or Hypercharge\TransactionRequest
	* @return Hypercharge\Transaction
	* @throws Hypercharge\Errors\Error if no transaction created on hypercharge server
	*/
	static function capture($channelToken, $request) {
		return self::_call('capture', $request, $channelToken);
	}

	/**
	* @param string $channelToken
	* @param mixed $request array or Hypercharge\TransactionRequest
	* @return Hypercharge\Transaction
	* @throws Hypercharge\Errors\Error if no transaction created on hypercharge server
	*/
	static function refund($channelToken, $request) {
		return self::_call('refund', $request, $channelToken);
	}

	/**
	* @param string $channelToken
	* @param mixed $request array or Hypercharge\TransactionRequest
	* @return Hypercharge\Transaction
	* @throws Hypercharge\Errors\Error if no transaction created on hypercharge server
	*/
	static function void($channelToken, $request) {
		return self::_call('void', $request, $channelToken);
	}

	/**
	* @param string $channelToken
	* @param mixed $request array or Hypercharge\TransactionRequest
	* @return Hypercharge\Transaction
	* @throws Hypercharge\Errors\Error if no transaction created on hypercharge server
	*/
	static function referenced_fund_transfer($channelToken, $request) {
		return self::_call('referenced_fund_transfer', $request, $channelToken);
	}

	/**
	* @param string $channelToken
	* @param mixed $request array or Hypercharge\TransactionRequest
	* @return Hypercharge\Transaction
	* @throws Hypercharge\Errors\Error if no transaction created on hypercharge server
	*/
	static function authorize3d($channelToken, $request) {
		return self::_call('authorize3d', $request, $channelToken);
	}

	/**
	* @param string $channelToken
	* @param mixed $request array or Hypercharge\TransactionRequest
	* @return Hypercharge\Transaction
	* @throws Hypercharge\Errors\Error if no transaction created on hypercharge server
	*/
	static function sale3d($channelToken, $request) {
		return self::_call('sale3d', $request, $channelToken);
	}

	/**
	* @param string $channelToken
	* @param mixed $request array or Hypercharge\TransactionRequest
	* @return Hypercharge\Transaction
	* @throws Hypercharge\Errors\Error if no transaction created on hypercharge server
	*/
	static function init_recurring_sale($channelToken, $request) {
		return self::_call('init_recurring_sale', $request, $channelToken);
	}

	/**
	* @param string $channelToken
	* @param mixed $request array or Hypercharge\TransactionRequest
	* @return Hypercharge\Transaction
	* @throws Hypercharge\Errors\Error if no transaction created on hypercharge server
	*/
	static function init_recurring_authorize($channelToken, $request) {
		return self::_call('init_recurring_authorize', $request, $channelToken);
	}

	/**
	* @param string $channelToken
	* @param mixed $request array or Hypercharge\TransactionRequest
	* @return Hypercharge\Transaction
	* @throws Hypercharge\Errors\Error if no transaction created on hypercharge server
	*/
	static function recurring_sale($channelToken, $request) {
		return self::_call('recurring_sale', $request, $channelToken);
	}

	/**
	* @param string $channelToken
	* @param mixed $request array or Hypercharge\TransactionRequest
	* @return Hypercharge\Transaction
	* @throws Hypercharge\Errors\Error if no transaction created on hypercharge server
	*/
	static function pay_pal($channelToken, $request) {
		return self::_call('pay_pal', $request, $channelToken);
	}

	/**
	* @param string $channelToken
	* @param mixed $request array or Hypercharge\TransactionRequest
	* @return Hypercharge\Transaction
	* @throws Hypercharge\Errors\Error if no transaction created on hypercharge server
	*/
	static function debit_sale($channelToken, $request) {
		return self::_call('debit_sale', $request, $channelToken);
	}

	/**
	* @param string $channelToken
	* @param mixed $request array or Hypercharge\TransactionRequest
	* @return Hypercharge\Transaction
	* @throws Hypercharge\Errors\Error if no transaction created on hypercharge server
	*/
	static function init_recurring_debit_sale($channelToken, $request) {
		return self::_call('init_recurring_debit_sale', $request, $channelToken);
	}

	/**
	* @param string $channelToken
	* @param mixed $request array or Hypercharge\TransactionRequest
	* @return Hypercharge\Transaction
	* @throws Hypercharge\Errors\Error if no transaction created on hypercharge server
	*/
	static function init_recurring_debit_authorize($channelToken, $request) {
		return self::_call('init_recurring_debit_authorize', $request, $channelToken);
	}

	/**
	* @param string $channelToken
	* @param mixed $request array or Hypercharge\TransactionRequest
	* @return Hypercharge\Transaction
	* @throws Hypercharge\Errors\Error if no transaction created on hypercharge server
	*/
	static function sepa_debit_sale($channelToken, $request) {
		return self::_call('sepa_debit_sale', $request, $channelToken);
	}

	/**
	* @param string $channelToken
	* @param mixed $request array or Hypercharge\TransactionRequest
	* @return Hypercharge\Transaction
	* @throws Hypercharge\Errors\Error if no transaction created on hypercharge server
	*/
	static function init_recurring_sepa_debit_sale($channelToken, $request) {
		return self::_call('init_recurring_sepa_debit_sale', $request, $channelToken);
	}

	/**
	* @param string $channelToken
	* @param mixed $request array or Hypercharge\TransactionRequest
	* @return Hypercharge\Transaction
	* @throws Hypercharge\Errors\Error if no transaction created on hypercharge server
	*/
	static function init_recurring_sepa_debit_authorize($channelToken, $request) {
		return self::_call('init_recurring_sepa_debit_authorize', $request, $channelToken);
	}

	/**
	* @param string $channelToken
	* @param mixed $request array or Hypercharge\TransactionRequest
	* @return Hypercharge\Transaction
	* @throws Hypercharge\Errors\Error if no transaction created on hypercharge server
	*/
	static function giro_pay_sale($channelToken, $request) {
		return self::_call('giro_pay_sale', $request, $channelToken);
	}

	/**
	* @param string $channelToken
	* @param mixed $request array or Hypercharge\TransactionRequest
	* @return Hypercharge\Transaction
	* @throws Hypercharge\Errors\Error if no transaction created on hypercharge server
	*/
	static function direct_pay24_sale($channelToken, $request) {
		return self::_call('direct_pay24_sale', $request, $channelToken);
	}

	/**
	* @param string $channelToken
	* @param mixed $request array or Hypercharge\TransactionRequest
	* @return Hypercharge\Transaction
	* @throws Hypercharge\Errors\Error if no transaction created on hypercharge server
	*/
	static function ideal_sale($channelToken, $request) {
		return self::_call('ideal_sale', $request, $channelToken);
	}

	/**
	* Kauf auf Rechnung
	* @param string $channelToken
	* @param mixed $request array or Hypercharge\TransactionRequest
	* @return Hypercharge\Transaction
	* @throws Hypercharge\Errors\Error if no transaction created on hypercharge server
	*/
	static function purchase_on_account($channelToken, $request) {
		return self::_call('purchase_on_account', $request, $channelToken);
	}

	/**
	* Vorkasse
	* @param string $channelToken
	* @param mixed $request array or Hypercharge\TransactionRequest
	* @return Hypercharge\Transaction
	* @throws Hypercharge\Errors\Error if no transaction created on hypercharge server
	*/
	static function pay_in_advance($channelToken, $request) {
		return self::_call('pay_in_advance', $request, $channelToken);
	}

	/**
	* @param string $channelToken
	* @param mixed $request array or Hypercharge\TransactionRequest
	* @return Hypercharge\Transaction
	* @throws Hypercharge\Errors\Error if no transaction created on hypercharge server
	*/
	static function payment_on_delivery($channelToken, $request) {
		return self::_call('payment_on_delivery', $request, $channelToken);
	}

	/**
	* @param string $channelToken
	* @param mixed $request array or Hypercharge\TransactionRequest
	* @return Hypercharge\Transaction
	* @throws Hypercharge\Errors\Error if no transaction created on hypercharge server
	*/
	static function recurring_debit_sale($channelToken, $request) {
		return self::_call('recurring_debit_sale', $request, $channelToken);
	}

	/**
	* @param string $channelToken
	* @param mixed $request array or Hypercharge\TransactionRequest
	* @return Hypercharge\Transaction
	* @throws Hypercharge\Errors\Error if no transaction created on hypercharge server
	*/
	static function recurring_sepa_debit_sale($channelToken, $request) {
		return self::_call('recurring_sepa_debit_sale', $request, $channelToken);
	}

	/**
	* @param string $channelToken
	* @param mixed $request array or Hypercharge\TransactionRequest
	* @return Hypercharge\Transaction
	* @throws Hypercharge\Errors\Error if no transaction created on hypercharge server
	*/
	static function pay_safe_card_sale($channelToken, $request) {
		return self::_call('pay_safe_card_sale', $request, $channelToken);
	}

	/**
	* called "Reconcile" "Single Transaction" in API doc
	* @param string $channelToken
	* @param string $unique_id hex Transaction unique_id
	* @return Hypercharge\Transaction
	* @throws Hypercharge\Errors\Error
	*/
	static function find($channelToken, $unique_id) {
		$request = new SimpleTransactionReturningRequest('reconcile', $unique_id);
		$request->validate();
		$factory = Config::getFactory();
		$url = $factory->createTransactionUrl($channelToken, 'reconcile');
		return $factory->createWebservice()->call($url, $request);
	}

	/**
	* called "reconcile" "By date range" in API doc
	* request.period e.g. "P1D" for one day, see php DateInterval::__construct
	* @param string $channelToken
	* @param mixed $request optional array or Hypercharge\ReconcileByDateRequest  {start_date: "YYYY-MM-DD", end_date: "YYYY-MM-DD", period: String}
	* @return Hypercharge\PaginatedCollection containing instances of Hypercharge\Transaction
	* @throws Hypercharge\Errors\Error
	*/
	static function page($channelToken, $request = null) {
		$request = Helper::ensure('ReconcileByDateRequest', $request);
		$request->validate();
		$factory = Config::getFactory();
		$url = $factory->createTransactionUrl($channelToken, 'reconcile/by_date');
		return $factory->createWebservice()->call($url, $request);
	}

	/**
	* iterates over all pages and calls callback passing Transaction as parameter
	* request.period e.g. "P1D" for one day, see php DateInterval::__construct
	* @param string $channelToken
	* @param mixed $request array or Hypercharge\ReconcileByDateRequest  {start_date: "YYYY-MM-DD", end_date: "YYYY-MM-DD", period: String}
	* @param function $callback parameter (Hypercharge\Transaction $trx)
	* @throws Hypercharge\Errors\Error
	*/
	static function each($channelToken, $request, $callback) {
		$request = Helper::ensure('ReconcileByDateRequest', $request);
		$url = Config::getFactory()->createTransactionUrl($channelToken, 'reconcile/by_date');
		$request->each($url, $callback);
	}

	/**
	* @param array $params simply pass $_POST into
	* @return Hypercharge\TransactionNotification
	* @throws Hypercharge\Errors\ArgumentError if $params empty or merchant password not set with Config::set()
	*/
	static function notification($params) {
		$tn = new TransactionNotification($params);
		$passw = Config::getPassword();
		if(empty($passw)) {
			throw new Errors\ArgumentError('password is not configured! See Hypercharge\Config::set()');
		}
		$tn->verify($passw);
		return $tn;
	}

	/**
	* @param string $klass
	* @param mixed $request array or Hypercharge\TransactionRequest
	* @param string $channelToken
	* @param string $action
	* @return Hypercharge\Transaction
	*/
	private static function _call($klass, $request, $channelToken, $action='process') {
		if(is_array($request)){
			$request['transaction_type'] = $klass;
		} else if(is_object($request)) {
			$request->transaction_type = $klass;
		}

		$request = Helper::ensure('TransactionRequest', $request);
		$request->validate();
		$factory = Config::getFactory();
		$url = $factory->createTransactionUrl($channelToken, $action);
		return $factory->createWebservice()->call($url, $request);
	}

}
