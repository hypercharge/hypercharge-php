<?php
namespace Hypercharge;

class PaymentMethods implements Converter {

	/**
	* @param array $all array of string
	*/
	function fromXml(array $all = null) {
		if(!is_array($all)) return;
		if(!sizeof($all)) return;
		return array_key_exists('payment_method', $all) ? (array)$all['payment_method'] : $all;
	}

	function toXml($all, \DOMNode $parent) {
		if(!sizeof($all)) return;
		foreach($all as $m) {
			XmlSerializer::addChild($parent, 'payment_method', $m);
		}
	}
}
