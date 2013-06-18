<?php
namespace Hypercharge;

/**
* see hypercharge api doc chapter "1.7 Advanced risk management with RiskParams"
*/
class RiskParams implements Serializable {

	/**
	* @param array $p a hash field => value
	*/
	function __construct($p) {
		Helper::assign($this, $p);
	}

}
