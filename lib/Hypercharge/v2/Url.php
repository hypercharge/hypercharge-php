<?php
namespace Hypercharge\v2;

class Url implements \Hypercharge\IUrl {

	private $mode;
	private $action;
	private $params;

	protected $urls = array(
		 \Hypercharge\Config::ENV_LIVE    => 'https://hypercharge.net/v2'
		,\Hypercharge\Config::ENV_SANDBOX => 'https://test.hypercharge.net/v2'
	);

	/**
	* @param string $mode \Hypercharge\Config::ENV_LIVE or ::ENV_SANDBOX
	* @param string|array $action e.g. 'scheduler' or an array of string e.g. array('scheduler', '<UNIQUE_ID>', 'transactions')
	* @param array $params GET params as key-value hash e.g. array('page'=>1, 'per_page'=>30) examples see unittest
	*/
	function __construct($mode, $action, $params=array()) {
		if(!\Hypercharge\Config::isValidMode($mode)) throw new \Exception('mode must be "sandbox" or "live"');

		$this->mode   = $mode;
		$this->action = $action;
		$this->params = $params;
	}

	public function getUrl() {
		return $this->urls[$this->mode];
	}

	public function get() {
		$url = $this->getUrl();
		$url .= '/'.(is_array($this->action) ? join($this->action, '/') : $this->action);
		if(!empty($this->params)) $url .= '?'.http_build_query($this->params);
		return $url;
	}
}