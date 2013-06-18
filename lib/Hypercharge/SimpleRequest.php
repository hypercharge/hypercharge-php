<?php
namespace Hypercharge;

abstract class SimpleRequest implements IRequest {
	protected $rootNodeName;
	public $unique_id;

	/**
	* @param string $rootNodeName e.g. 'reconcile' for a reconcile request
	* @param string $unique_id hex Payment or Transaction unique_id
	*/
	function __construct($rootNodeName, $unique_id) {
		$this->rootNodeName = $rootNodeName;
		$this->unique_id = $unique_id;
	}

	function getRootName() {
		return $this->rootNodeName;
	}

	function getType() {
		// dummy
		return $this->rootNodeName;
	}

	/**
	* @throws Hypercharge\Errors\ValidationError
	* @return void
	*/
	function validate() {
		$errors = new Errors\ValidationError();
		if(!preg_match('/^[a-f0-9]{32}$/', $this->unique_id))
			$errors->add('unique_id', 'must be 32 character hex string but was: '. $this->unique_id);

		if($errors->flush()) throw $errors;
	}
}