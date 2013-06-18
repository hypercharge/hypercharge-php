<?php
namespace Hypercharge;

class TransactionNotification implements INotification {

	private $_verified = false;

	function __construct($p) {
		if(empty($p)) throw new Errors\ArgumentError('Missing or empty argument 1');
		Helper::assign($this, $p);
	}

	/**
	* @protected
	* @param string $password merchant api password
	*/
	function verify($password) {
		// sha1 !!! not sha512 as in PaymentNotification
		$this->_verified = hash('sha1', $this->unique_id . $password) == $this->signature;
	}

	/**
	* do not trust Notification if isVerified() returns false!
	* @return boolean
	*/
	function isVerified() {
		return $this->_verified;
	}

	/**
	* checks if Payment.status == 'approved'
	* @return boolean
	*/
	function isApproved() {
		return $this->status == Transaction::STATUS_APPROVED;
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
		XmlSerializer::addChild($root, 'unique_id', $this->unique_id);
		return $root->ownerDocument->saveXml();
	}
}
