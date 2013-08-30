<?php
namespace Hypercharge;

class Payment implements IResponse {

	const STATUS_NEW                 = 'new';
	const STATUS_USER                = 'user';
	const STATUS_TIMEOUT             = 'timeout';
	const STATUS_IN_PROGRESS         = 'in_progress';
	const STATUS_UNSUCCESSFULL       = 'unsuccessful';
	const STATUS_PENDING             = 'pending';
	const STATUS_PENDING_ASYNC       = 'pending_async';
	const STATUS_APPROVED            = 'approved';
	const STATUS_DECLINED            = 'declined';
	const STATUS_ERROR               = 'error';
	const STATUS_CANCELED            = 'canceled';
	const STATUS_REFUNDED            = 'refunded';
	const STATUS_CHARGEBACKED        = 'chargebacked';
	const STATUS_CHARGEBACK_REVERSED = 'chargeback_reversed';
	const STATUS_PRE_ARBITRATED      = 'pre_arbitrated';
	const STATUS_CAPTURED            = 'captured';
	const STATUS_VOIDED              = 'voided';

	public $unique_id;
	public $type;
	public $error = null;
	public $transactions = null;

	function __construct($p) {
		Helper::assign($this, $p);

		// alias for convinience
		if($this->type == 'MobilePayment') {
			$this->submit_url = @ $p['redirect_url'];
		}

		$this->error = Errors\errorFromResponseHash($p);
		unset($this->code);
		unset($this->message);
		unset($this->technical_message);

		if(isset($p['payment_transaction'])) {
			$this->transactions = array();
			if(array_key_exists(0, $p['payment_transaction'])) {
				foreach($p['payment_transaction'] as $data) {
					$this->transactions[] = new Transaction($data);
				}
			} else {
				$this->transactions[] = new Transaction($p['payment_transaction']);
			}
		}
	}
	//
	// convinience methods
	//
	function isNew() {
		return $this->status == self::STATUS_NEW;
	}
	function isUser() {
		return $this->status == self::STATUS_USER;
	}
	function isApproved() {
		return $this->status == self::STATUS_APPROVED;
	}
	function isError() {
		return $this->status == self::STATUS_ERROR;
	}
	function isCanceled() {
		return $this->status == self::STATUS_CANCELED;
	}
	function isVoided() {
		return $this->status == self::STATUS_VOIDED;
	}
	function isCaptured() {
		return $this->status == self::STATUS_CAPTURED;
	}
	function isRefunded() {
		return $this->status == self::STATUS_REFUNDED;
	}
	function isPersistentInHypercharge() {
		return isset($this->type)
		    && isset($this->unique_id)
		    && isset($this->transaction_id);
	}
	function isFatalError() {
		return !$this->isPersistentInHypercharge();
	}

	/**
	* for MobilePayment only
	* @return boolean
	*/
	function shouldContinueInMobileApp() {
		return $this->isNew() && $this->type == 'MobilePayment';
	}

	/**
	* for WpfPayment only
	* @return boolean
	*/
	function shouldRedirect() {
		return $this->isNew() && $this->type == 'WpfPayment';
	}

	/**
	* for WpfPayment only
	* @param string $locale 'en' or 'de'
	* @param string url
	*/
	function getRedirectUrl($locale = 'en') {
		if(!($locale == 'en' || $locale = 'de')) throw new Errors\ArgumentError('locale', 'must be "en" or "de"');
		return $this->redirect_url . '?locale='.$locale;
	}

	//
	// Hypercharge WPF/Mobile Payment Calls
	//

	/**
	* @param mixed $request array or Hypercharge\WpfPaymentRequest
	* @return Hypercharge\WpfPayment
	* @throws Hypercharge\Errors\Error if no payment created on hypercharge server
	*/
	static function wpf($request) {
		$request['type'] = 'WpfPayment';
		$request = Helper::ensure('PaymentRequest', $request);
		$request->validate();
		$factory = Config::getFactory();
		$url = $factory->createPaymentUrl();
		return $factory->createWebservice()->call($url, $request);
	}

	/**
	* @param mixed $request array or Hypercharge\MobilePaymentRequest
	* @return Hypercharge\MobilePayment
	* @throws Hypercharge\Errors\Error if no payment created on hypercharge server
	*/
	static function mobile($request) {
		$request['type'] = 'MobilePayment';
		$request = Helper::ensure('PaymentRequest', $request);
		$request->validate();
		$factory = Config::getFactory();
		$url = $factory->createPaymentUrl();
		return $factory->createWebservice()->call($url, $request);
	}

	/**
	* @param string $unique_id a Payment.unique_id
	* @return Hypercharge\Payment
	* @throws Hypercharge\Errors\Error
	*/
	static function cancel($unique_id) {
		$request = new SimplePaymentReturningRequest('cancel', $unique_id);
		$request->validate();
		$factory = Config::getFactory();
		$url = $factory->createPaymentUrl('cancel');
		return $factory->createWebservice()->call($url, $request);
	}

	/**
	* @param string $unique_id a Payment.unique_id
	* @return Hypercharge\Transaction
	* @throws Hypercharge\Errors\Error
	*/
	static function void($unique_id) {
		$request = new SimplePaymentReturningRequest('void', $unique_id);
		$request->validate();
		$factory = Config::getFactory();
		$url = $factory->createPaymentUrl('void');
		return $factory->createWebservice()->call($url, $request);
	}

	/**
	* @param string $unique_id a Payment.unique_id
	* @return Hypercharge\Transaction
	* @throws Hypercharge\Errors\Error
	*/
	static function capture($unique_id) {
		$request = new SimplePaymentReturningRequest('capture', $unique_id);
		$request->validate();
		$factory = Config::getFactory();
		$url = $factory->createPaymentUrl('capture');
		return $factory->createWebservice()->call($url, $request);
	}

	/**
	* @param string $unique_id a Payment.unique_id
	* @return Hypercharge\Transaction
	* @throws Hypercharge\Errors\Error
	*/
	static function refund($unique_id) {
		$request = new SimplePaymentReturningRequest('refund', $unique_id);
		$request->validate();
		$factory = Config::getFactory();
		$url = $factory->createPaymentUrl('refund');
		return $factory->createWebservice()->call($url, $request);
	}

	/**
	* see "reconcile" in Hypercharge API doc
	* @param string $unique_id a Payment.unique_id
	* @return Hypercharge\Payment
	* @throws Hypercharge\Errors\Error
	*/
	static function find($unique_id) {
		$request = new SimplePaymentReturningRequest('reconcile', $unique_id);
		$request->validate();
		$factory = Config::getFactory();
		$url = $factory->createPaymentUrl('reconcile');
		return $factory->createWebservice()->call($url, $request);
	}

	/**
	* @param array $params simply pass $_POST into
	* @return Hypercharge\PaymentNotification
	* @throws Hypercharge\Errors\ArgumentError if $params empty or merchant password not set with Config::set()
	*/
	static function notification($params) {
		$pn = new PaymentNotification($params);
		$passw = Config::getPassword();
		if(empty($passw)) {
			throw new Errors\ArgumentError('password is not configured! See Hypercharge\Config::set()');
		}
		$pn->verify($passw);
		return $pn;
	}


	/**
	* do NOT use! for internal use only!
	*
	* factory method for MobilePayment and WpfPayment
	* @protected
	* @param array $data see __construct param
	* @return Payment according to $data['type'] an instance of Hypercharge\WpfPayment or Hypercharge\MobilePayment
	*/
	static function create($data) {
		return new Payment($data);
	}

	/**
	* @return string 'MobilePayment' or 'WpfPayment' or theoretically 'Payment' as well.
	*/
	function getType() {
		return $this->type;
	}

}

