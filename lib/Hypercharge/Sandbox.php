<?php
namespace Hypercharge;

/**
* Sandbox class contains all API calls to the Sandbox
*/
class Sandbox {

  /**
  * Create a ChargebackTransaction on a previously APPROVED Sale or Capture Transaction
  * @param string $channelToken channel-token of the original transaction
  * @param string $unique_id the unique_id of the the original transaction
  * @return Transaction chargeback
  */
  static function create_chargeback($channelToken, $unique_id) {
    return self::request('bogus_event/chargeback', $channelToken, $unique_id);
  }

  /**
  * Create a PreArbitration on a Chargeback
  * @param string $channelToken channel token of the original chargeback
  * @param string $unique_id the unique_id of the the original chargeback
  * @return Hypercharge\Transaction pre_arbitration
  */
  static function create_pre_arbitration($channelToken, $unique_id) {
    return self::request('bogus_event/pre_arbitration', $channelToken, $unique_id);
  }

  /**
  * Create a ChargebackReversal on a Chargeback
  * @param string $channelToken channel token of the original chargeback
  * @param string $unique_id the unique_id of the the original chargeback
  * @return Hypercharge\Transaction chargeback_reversal
  */
  static function create_chargeback_reversal($channelToken, $unique_id) {
    return self::request('bogus_event/chargeback_reversal', $channelToken, $unique_id);
  }

  /**
  *
  * @param string $channelToken channel token of the original chargeback
  * @param string $unique_id the unique_id of the the original chargeback
  * @return Hypercharge\Transaction
  */
  static function create_retrieval_request($channelToken, $unique_id) {
    return self::request('bogus_event/retrieval', $channelToken, $unique_id);
  }

  /**
  *
  * @param string $channelToken channel token of the original chargeback
  * @param string $unique_id the unique_id of the the original chargeback
  * @return Hypercharge\Transaction
  */
  static function create_deposit($channelToken, $unique_id) {
    return self::request('bogus_event/deposit', $channelToken, $unique_id);
  }

  /**
  * Create a ChargebackTransaction on a previously APPROVED DebitSaleTransaction
  * @param string $channelToken channel token of the original transaction
  * @param string $unique_id the unique_id of the the original transaction
  * @return Hypercharge\Transaction chargeback
  */
  static function create_debit_chargeback($channelToken, $unique_id) {
    return self::request('bogus_event/debit_chargeback', $channelToken, $unique_id);
  }

  /**
  * Reject a DebitSale
  * @param string $channelToken channel token of the original DebitSaleTransaction
  * @param string $unique_id the unique_id of the the original DebitSaleTransaction
  * @return Hypercharge\Transaction debit_sale with status REJECTED
  */
   static function reject_debit_sale($channelToken, $unique_id) {
    return self::request('bogus_event/reject', $channelToken, $unique_id);
  }

  /**
  * Charge a DebitSale
  * @param string $channelToken channel token of the original DebitSaleTransaction
  * @param string $unique_id the unique_id of the the original DebitSaleTransaction
  * @return Hypercharge\Transaction debit_sale with status APPROVED
  */
  static function charge_depit_sale($channelToken, $unique_id) {
    return self::request('bogus_event/charge', $channelToken, $unique_id);
  }

  /**
  * @private
  * @param string $action
  * @param string $channelToken
  * @param string $unique_id
  * @return Hypercharge\Transaction
  */
  static function request($action, $channelToken, $unique_id) {
    $url = new TransactionUrl(Config::ENV_SANDBOX, $channelToken, $action);
    $url = $url->get().'/'.$unique_id;

    $curl = new Curl(Config::getUser(), Config::getPassword());

    $responseStr = $curl->xmlPost($url, '');
    $responseDom = new \SimpleXMLElement($responseStr);

    // dummy
    $request = new TransactionRequest(array('transaction_type'=>'sale'));

    return $request->createResponse(XmlSerializer::dom2hash($responseDom));
  }
}