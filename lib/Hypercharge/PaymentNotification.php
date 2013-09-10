<?php
namespace Hypercharge;

class PaymentNotification implements INotification {

	private $_verified = false;

	function __construct($p) {
		if(empty($p)) throw new Errors\ArgumentError('Missing or empty argument 1');
		Helper::assign($this, $p);
	}

	/**
	* convinience method
	* @return stdClass fields: type, unique_id, transaction_id, status
	*/
	function getPayment() {
		$o = new \stdClass();
		$o->type           = $this->notification_type;
		$o->unique_id      = $this->payment_unique_id;
		$o->transaction_id = $this->payment_transaction_id;
		$o->status         = $this->payment_status;
		return $o;
	}

	/**
	* @return boolean
	*/
	function hasTransaction() {
		return !empty($this->payment_transaction_transaction_type)
			&& !empty($this->payment_transaction_unique_id)
			&& !empty($this->payment_transaction_channel_token);
	}

	/**
	* convinience method
	* @return stdClass  fields: transaction_type, unique_id, channel_token
	*/
	function getTransaction() {
		if(!$this->hasTransaction()) return null;
		$o = new \stdClass();
		$o->transaction_type = $this->payment_transaction_transaction_type;
		$o->unique_id        = $this->payment_transaction_unique_id;
		$o->channel_token    = $this->payment_transaction_channel_token;
		return $o;
	}

	/**
	* @return boolean
	*/
	function hasSchedule() {
		return !empty($this->schedule_unique_id) && isset($this->schedule_end_date);
	}

	/**
	* convinience method
	* @return stdClass  fields: unique_id, end_date
	*/
	function getSchedule() {
		if(!$this->hasSchedule()) return null;
		$o = new \stdClass();
		$o->unique_id = $this->schedule_unique_id;
		$o->end_date  = $this->schedule_end_date;
		return $o;
	}

	/**
	* @return boolean
	*/
	public function isVerified() {
		return $this->_verified;
	}

	/**
	* checks if Payment.status == 'approved'
	* @return boolean
	*/
	public function isApproved() {
		return $this->payment_status == Payment::STATUS_APPROVED;
	}

	/**
	* returns xml echo
	*
	* <?xml version="1.0" encoding="UTF-8"?>
	* <notification_echo>
	*   <unique_id>26aa150ee68b1b2d6758a0e6c44fce4c</unique_id>
	* </notification_echo>
	*
	* @return string xml
	*/
	function ack() {
		$root = XmlSerializer::createDocument('notification_echo');
		XmlSerializer::addChild($root, 'unique_id', $this->payment_unique_id);
		return $root->ownerDocument->saveXml();
	}

	/**
	* Do not use! for internal use only
	* @protected
	* @param string $password merchant api password
	*/
	function verify($password) {
		$this->_verified = hash('sha512', $this->payment_unique_id . $password) == $this->signature;
	}

}
