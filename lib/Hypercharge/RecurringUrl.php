<?php
namespace Hypercharge;

class RecurringUrl implements IUrl {

	private $mode;
	private $action;

	private static $liveUrl;

	private $urls = array(
		 Config::ENV_LIVE    => 'https://hypercharge.net/v2'
		,Config::ENV_SANDBOX => 'https://test.hypercharge.net/v2'
	);

	function __construct($mode, $action = 'process') {
		if(!Config::isValidMode($mode)) throw new \Exception('mode must be "sandbox" or "live"');
		$allowedActions = array(

		);
		if(!in_array($action, $allowedActions)) throw new \Exception('action must be one of "'.join($allowedActions, '", "').'" but got "'.$action.'"');

		$this->mode    = $mode;
		$this->channel = $channel;
		$this->action  = $action;
	}

	function getUrl() {
		return $this->urls[$this->mode];
	}

	function get() {
		return $this->getUrl().'/'.$this->action
	}

}
