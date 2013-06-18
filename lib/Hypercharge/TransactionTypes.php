<?php
namespace Hypercharge;

class TransactionTypes implements Converter {

	/**
	* $all has format:
	* array('transaction_type' => array('sale', 'pay_pal'))
	* returns:
	* array('sale', 'pay_pal')
	*
	* @param array $all array of hash
	* @return array of string or null if $all was null
	*/
	function fromXml(array $all = null) {
		if(!is_array($all)) return;
		if(!sizeof($all)) return;
		return array_key_exists('transaction_type', $all) ? (array)$all['transaction_type'] : $all;
	}

	/**
	* @param array $all array of string
	* @param DOMNode $parent the xml node receiving items of $all <transaction_type>ITEM</transaction_type>
	* @return void
	*/
	function toXml($all, \DOMNode $parent) {
		if(!sizeof($all)) return;
		foreach($all as $t) {
			XmlSerializer::addChild($parent, 'transaction_type', $t);
		}
	}
}
