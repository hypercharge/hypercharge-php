<?php
namespace Hypercharge;

class TransactionRequest implements IRequest {

	private static $allowedTypes = array(
			 'sale'
      ,'sale3d'
      ,'authorize'
      ,'authorize3d'
      ,'capture'
      ,'refund'
      ,'void'
      ,'init_recurring_sale'
      ,'recurring_sale'
      ,'ideal_sale'
      ,'referenced_fund_transfer'
      ,'debit_sale'
      ,'sepa_debit_sale'
      ,'init_recurring_sepa_debit_sale'
      ,'init_recurring_sepa_debit_authorize'
      ,'direct_pay24_sale'
      ,'giro_pay_sale'
      ,'pay_safe_card_sale'
      ,'init_recurring_authorize'
      //,'debit_chargeback'
      ,'purchase_on_account'
      ,'pay_in_advance'
      ,'payment_on_delivery'
      ,'pay_pal'
      ,'init_recurring_debit_sale'
      ,'init_recurring_debit_authorize'
      ,'recurring_debit_sale'
      ,'recurring_sepa_debit_sale'
      //,'barzahlen_sale'
    );

	function __construct($data) {
		if(!in_array(@$data['transaction_type'], self::$allowedTypes)) {
			throw Errors\ValidationError::create('transaction_type', 'value invalid: "'.@$data['transaction_type'].'"');
		}

		Helper::assign($this, $data);

		if(isset($this->transaction_id)) {
			$this->transaction_id = Helper::appendRandomId($this->transaction_id);
		}
	}

	function setBillingAddress(Address $a) {
		$this->billing_address = $a;
	}

  function setShippingAddress(Address $a) {
    $this->shipping_address = $a;
  }

	function setRiskParams(RiskParams $r) {
		$this->risk_params = $r;
	}

	/**
	* @param array $data
	* @return Hypercharge\IResponse
	* @throws Hypercharge\Errors\Error
	*/
	function createResponse($data) {
		if(is_array($data['payment_response'])) {
			$trx = new Transaction($data['payment_response']);
			if(!$trx->unique_id) {
				if($trx->error) {
					throw $trx->error;
				} else {
					Config::getLogger()->error('unknown response error. data:'. Helper::stripCc(print_r($data, true)));
					throw new Error('unknown response error');
				}
			}
			return $trx;
		}
		if(is_array($data['payment_responses'])) {
			$ret = array();
			foreach($data['payment_responses'] as $r) {
				array_push($ret, new Transaction($r['payment_response']));
			}
			return $ret;
		}
		throw new Errors\ArgumentError('field "payment_response" resp. "payment_responses" is empty');
	}

	function getType() {
		return $this->transaction_type;
	}

	/**
	* @return array of strings
	*/
	static function getAllowedTypes() {
		return self::$allowedTypes;
	}

	function validate() {
		$errors = JsonSchema::check($this);
		if($errors) throw new Errors\ValidationError($errors);
	}

}
