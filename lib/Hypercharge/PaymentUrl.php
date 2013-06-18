<?php
namespace Hypercharge;

class PaymentUrl implements IUrl {

	private $mode;
	private $action;

	protected $urls = array(
		 Config::ENV_LIVE    => 'https://payment.hypercharge.net/payment'
		,Config::ENV_SANDBOX => 'https://testpayment.hypercharge.net/payment'
	);

	function __construct($mode, $action = 'create') {
		if(!Config::isValidMode($mode)) throw new \Exception('mode must be "sandbox" or "live"');
		$allowedActions = array('create', 'reconcile', 'cancel', 'void', 'capture', 'refund');
		if(! in_array($action, $allowedActions)) throw new \Exception('action must be one of "'.join($allowedActions, '", "').'" but is: "'.$action.'"');

		$this->mode   = $mode;
		$this->action = $action;
	}

	public function getUrl() {
		return $this->urls[$this->mode];
	}

	public function get() {
		$url = $this->getUrl();
		if($this->action == 'create') return $url;
		return $url.'/'.$this->action;
	}
}
