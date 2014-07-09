<?php
namespace Hypercharge;

class TransactionNotification implements INotification {

	private $_verified = false;

	function __construct($p) {
		if(empty($p)) throw new Errors\ArgumentError('Missing or empty argument 1');
		Helper::assign($this, $p);
	}

	/**
	* @return boolean
	*/
	function hasTransaction() {
		return !empty($this->transaction_type)
			&& !empty($this->unique_id)
			&& !empty($this->channel_token);
	}

	/**
	* fetch all Transaction details from hypercharge server.
	* @return Hypercharge\Transaction null if Notification has no Transaction
	*/
	function getTransaction() {
		if(!$this->hasTransaction()) return null;

		return Transaction::find($this->channel_token, $this->unique_id);
	}

	/**
	* do not trust Notification if isVerified() returns false!
	* @return boolean
	*/
	function isVerified() {
		return $this->_verified;
	}

	/**
	* checks if Transaction.status == 'approved'
	* @return boolean
	*/
	function isApproved() {
		return $this->status == Transaction::STATUS_APPROVED;
	}

	/**
	* In order hypercharge knows the notification has been received and processed successfully by your server
	* you have to respond with an ack message.
	* <pre>
	*  die($notifiction->ack())
	* </pre>
	*
	* If you do not do so, hypercharge will send the notification again later (up to 10 times at increasing intervals).
	* This also applies to accidentally polluting the output with php error-, warning- or notice-messages (invalid xml).
	*
	*
	* fyi: ack() returns an xml string e.g.
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


	/**
	* Do not use! for internal use only
	* @protected
	* @param string $password merchant api password
	*/
	function verify($password) {
		// sha1 !!! not sha512 as in PaymentNotification
		$this->_verified = hash('sha1', $this->unique_id . $password) == $this->signature;
	}

}
