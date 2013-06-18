<?php
namespace Hypercharge;

class Address implements Serializable {

	function __construct($p) {
		Helper::assign($this, $p);
	}

}
