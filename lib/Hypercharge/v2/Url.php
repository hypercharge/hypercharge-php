<?php
namespace Hypercharge\v2;

class Url implements \Hypercharge\IUrl {

	private $mode;
	private $path;
	private $params;

	protected $urls = array(
		 \Hypercharge\Config::ENV_LIVE    => 'https://hypercharge.net/v2'
		,\Hypercharge\Config::ENV_SANDBOX => 'https://test.hypercharge.net/v2'
	);

	/**
	* @param string $mode \Hypercharge\Config::ENV_LIVE or ::ENV_SANDBOX
	* @param string|array $path e.g. 'scheduler' or an array of string e.g. array('scheduler', '<UNIQUE_ID>', 'transactions')
	* @param array|object $params GET params as key-value hash e.g. array('page'=>1, 'per_page'=>30) examples see unittest
	*/
	function __construct($mode, $path, $params=array()) {
		if(!\Hypercharge\Config::isValidMode($mode)) throw new \Exception('mode must be "sandbox" or "live"');

		$this->mode   = $mode;
		$this->path   = $path;
		$this->params = $params;
	}

	public function getUrl() {
		return $this->urls[$this->mode];
	}

	public function get() {
		$url = $this->getUrl();

		$url .= '/'.(is_array($this->path) ? join($this->path, '/') : $this->path);

		// php is so fu***** inconsistent empty($emptyObject) returns false :-|
		// you always have to fiddle around. php eats your time.
		$params = is_object($this->params) ? get_object_vars($this->params) : $this->params;

		if(!empty($params)) $url .= '?'.http_build_query($params);

		return $url;
	}
}