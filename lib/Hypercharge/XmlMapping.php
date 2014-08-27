<?php
namespace Hypercharge;

class XmlMapping {

	private $rootNames = array(
		'PaymentRequest' => 'payment'
		,'TransactionRequest' => 'payment_transaction'
		,'ReconcileByDateRequest' => 'reconcile'
	);

	private $classes = array(
		'billing_address' => 'Address'
		,'shipping_address' => 'Address'
		,'risk_params' => 'RiskParams'
		,'payment_transaction' => 'Transaction'
		,'mpi_params' => 'MpiParams'
		,'recurring_schedule' => 'Scheduler'
	);

	private $converters = array(
		'transaction_types' => 'TransactionTypes'
		,'payment_methods' => 'PaymentMethods'
	);


	function getRootName(Serializable $o) {
		$klass = preg_replace('/^Hypercharge\\\/', '', get_class($o));
		$rootName = @$this->rootNames[$klass];
		if(!$rootName) throw new Errors\Error('class "'.$klass.'" has no root-name mapping!');
		return $rootName;
	}

	function getClass($nodeName) {
		$klass = @$this->classes[$nodeName];
		if(!$klass) return;
		return 'Hypercharge\\'.$klass;
	}

	/**
	* @param string $nodeName
	* @param Hypercharge\Converter a converter instance
	*/
	function getConverter($nodeName) {
		$klass = @$this->converters[$nodeName];
		if(!$klass) return;
		$klass = 'Hypercharge\\'.$klass;
		return new $klass;
	}
}