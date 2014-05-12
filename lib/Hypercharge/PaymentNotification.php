<?php
namespace Hypercharge;
/**
* PaymentNotifaction itself has a minimal set of fields (see below)
* Payment, Transaction and Schedule details can be fetched from hypercharge server with
* <pre>
* $payment     = $notification->getPayment();
* $transaction = $notification->getTransaction();
* $schedule    = $notification->getSchedule();
* </pre>
*
* private PaymentNotification fields.
* You won't need to access them directly in most cases.
*
* Payment fields:
* <pre>
*   notification_type
*   payment_unique_id
*   payment_transaction_id
*   payment_status
* </pre>
*
* If the Payment has a Transaction:
* <pre>
*   payment_transaction_transaction_type
*   payment_transaction_unique_id
*   payment_transaction_channel_token
* </pre>
*
* If the Payment has a Schedule:
* <pre>
*   schedule_unique_id
*   schedule_end_date
* </pre>
*/
class PaymentNotification implements INotification {

	private $_verified = false;

	function __construct($p) {
		if(empty($p)) throw new Errors\ArgumentError('Missing or empty argument 1');
		Helper::assign($this, $p);
	}

	/**
	* fetch all Payment details from hypercharge server
	* @return Hypercharge\Payment
	*/
	function getPayment() {
		return Payment::find($this->payment_unique_id);
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
	* fetch all Transaction details from hypercharge server.
	* @return Hypercharge\Transaction null if Notification has no Transaction
	*/
	function getTransaction() {
		if(!$this->hasTransaction()) return null;

		return Transaction::find($this->payment_transaction_channel_token, $this->payment_transaction_unique_id);
	}

	/**
	* @return boolean
	*/
	function hasSchedule() {
		return !empty($this->schedule_unique_id) && isset($this->schedule_end_date);
	}

	/**
	* fetch all Schedule details from hypercharge server.
	* @return Hypercharge\Schedule null if Notification has no Schedule
	*/
	function getSchedule() {
		if(!$this->hasSchedule()) return null;

		return Scheduler::find($this->schedule_unique_id);
	}

	/**
	* @return boolean
	*/
	public function isVerified() {
		return $this->_verified;
	}

	/**
	* @deprecated use  $payment = $notification->getPayment(); $payment->isApproved()
	* checks if Payment.status == 'approved'
	* @return boolean
	*/
	public function isApproved() {
		return $this->payment_status == Payment::STATUS_APPROVED;
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
