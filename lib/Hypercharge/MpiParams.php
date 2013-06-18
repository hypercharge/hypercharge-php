<?php
namespace Hypercharge;

/**
* see hypercharge api doc chapter "1.3.2 Sale3D" or "1.3.1 Authorize3D"
*/
class MpiParams implements Serializable {

	/**
	* @param array $p a hash field => value
	*/
	function __construct($p) {
		Helper::assign($this, $p);
	}

}