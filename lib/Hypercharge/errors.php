<?php
namespace Hypercharge\Errors;

class Error extends \Exception {
	/**
	* int 0-99 local errors from SKD
	* int 100-1000 remote errors from Hypercharge XML API
	*/
	public $status_code;
	public $message;
	public $technical_message;

	function __construct($message, $technical_message='') {
		parent::__construct(\Hypercharge\Helper::stripCc($message));
		$this->technical_message = \Hypercharge\Helper::stripCc($technical_message);
	}

	function addTechMsg($str) {
		$this->technical_message .= ' '.\Hypercharge\Helper::stripCc($str);
	}

	function __toString() {
		$klass = new \ReflectionClass($this);
		return $klass->getShortName()." {status_code: {$this->status_code}, technical_message: '{$this->technical_message}', message: '{$this->message}'}";
	}
}

class ArgumentError extends Error {
	public $status_code = 40;
}

/**
* holds local validation error on your mashine - in contrary to remote errors from hypercharge gateway (e.g. InputDataInvalidError).
* wraps json schema errors
*/
class ValidationError extends Error {
	public $status_code = 50;

	/**
	* json schema validation errors as returned from json-schema (php) library
	* array of hash
	* [
	*   {"property": string, "message": string}
	*   , ...
	* ]
	*/
	public $errors;

	function __construct(array $errors = array()) {
		parent::__construct('');
		$strip = function(&$val) {
			if(is_scalar($val)) {
				$val = \Hypercharge\Helper::stripCc($val);
			}
		};
		array_walk_recursive($errors, $strip);
		$this->errors = $errors;

		$this->flush();
	}

	/**
	* after calling #add() you have to rebuild message and technical_message by calling #flush()
	* @param string $field
	* @param string $message
	*/
	function add($field, $msg) {
		$this->errors[] = array('property'=>$field, 'message'=>\Hypercharge\Helper::stripCc($msg));
	}

	/**
	* call it after using add()
	* rebuilds message and technical_message.
	* @return boolean true if contains errors, false if errors is empty
	*/
	function flush() {
		$n = count($this->errors);
		$this->message = $n.' validation '.($n==1?'error':'errors');

		$this->technical_message = '';
		if($n) {
			$props = array();
			$extractProps = function($val, $key) use (&$props) {
				if($key !== 'property' || !is_scalar($val)) return;
				// stripCc() ...  no cc data expected in property name. but you never know.
				$props[] = \Hypercharge\Helper::stripCc($val);
			};
			array_walk_recursive($this->errors, $extractProps);
			$props = array_unique($props);
			$nProps = count($props);
			$this->technical_message = $nProps.' affected '.($nProps==1?'property':'properties').': '.join($props, ', ');
		}
		if(getenv('DEBUG') == '1') $this->message .= ': '.$this->technical_message."\n".print_r($this->errors, true);
		return $n > 0;
	}

	/**
	* @param string $field
	* @param string $msg
	* @return Hypercharge\Errors\ValidationError
	*/
	static function create($field, $msg) {
		$e = new ValidationError();
		$e->add($field, $msg);
		$e->flush();
		return $e;
	}
}

class NetworkError extends Error {
	public $status_code = 10;
	public $url;
	public $http_status = 0;
	public $body;

	/**
	* @param string $url
	* @param int $http_status
	* @param string $message
	* @param string $body
	*/
	function __construct($url, $http_status, $message, $body='') {
		parent::__construct('Connection to Payment Gateway failed.', $message);
		$this->url = $url;
		$this->http_status = $http_status;
		$this->body = \Hypercharge\Helper::stripCc($body);
	}
}

class XmlParsingError extends Error {
	public $status_code = 60;
	public $line;
	public $column;
	function __construct($msg, $line, $column) {
		parent::__construct($msg, "at line {$line} column {$column}");
		$this->line = $line;
		$this->column = $column;
	}
}

class ResponseFormatError extends Error {
	public $status_code = 70;
	function __construct($msg, $data) {
		parent::__construct($msg, print_r($data, true));
	}
}

/**
* TODO: exchange technical_message with user_message because user_message isn't containing any usefull informations and is pretty useless.
*       Exception->message is the field printed into shell message if unhandled exception occurse and that's important when developing imho.
* @protected
* Factory function for creating Error Object from error in Hypercharge XML API response
* @param array|object $response parsed hypercharge XML API response containing fields {code, message, technical_message}
* @return 'Hypercharge\Errors\Error' and subclasses if field 'code' given in $response. Returns null if no 'code' given.
*/
function errorFromResponseHash($response) {

	$code = is_object($response) ? @$response->code : @$response['code'];
	if($code === null) return null;

	$msg      = is_object($response) ? @$response->user_message : @$response['message'];
	$tech_msg = is_object($response) ? @$response->message      : @$response['technical_message'];

	$klass = ERROR_MAPPING::get($code);
	if($klass) {
		$klass = 'Hypercharge\Errors\\'.$klass;
		return new $klass($msg, $tech_msg);
	}
	// error not in mapping
	$error = new Error($msg, $tech_msg);
	$error->status_code = (int) $code;
	return $error;
}

	//////////////////////////////////////////////
 //        Hypercharge XML API errors        //
//////////////////////////////////////////////

// Error with unspecified reason
class SystemError extends Error {
	public $status_code = 100;
}

// Error class used to indicate maintenance mode {.
class MaintenanceError extends Error {
	public $status_code = 101;
}

// autentication failed for processing.
class AuthenticationError extends SystemError {
	public $status_code = 110;
}

// configuration is inconsistent.
class ConfigurationError extends SystemError {
	public $status_code = 120;
}

// Base class for Acquirer errors with the acquirer {.
class CommunicationError extends Error {
	public $status_code = 200;
}

// connection with the acquirer failed.
class ConnectionError extends CommunicationError {
	public $status_code = 210;
}

// account with the acquirer is invalid.
class AccountError extends CommunicationError {
	public $status_code = 220;
}

// requests to the acquirer times out.
class TimeoutError extends CommunicationError {
	public $status_code = 230;
}

// response failed.
class ResponseError extends CommunicationError {
	public $status_code = 240;
}

// response could not be parsed.
class ParsingError extends CommunicationError {
	public $status_code = 250;
}

// Base class for all data input errors like invalid formats or characters {.
class InputDataError extends Error {
	public $status_code = 300;
}

// PaymentTransaction type is invalid.
class InvalidTransactionTypeError extends InputDataError {
	public $status_code = 310;
}

// data is missing.
class InputDataMissingError extends InputDataError {
	public $status_code = 320;
}

// entered data has invalid format.
class InputDataFormatError extends InputDataError {
	public $status_code = 330;
}

// entered data has invalid characters.
class InputDataInvalidError extends InputDataError {
	public $status_code = 340;
}

// request contains invalid XML.
class InvalidXmlError extends InputDataError {
	public $status_code = 350;
}

class InvalidConentTypeError extends InputDataError {
	public $status_code = 360;
}

// Base class for all workflow errors like followup requests for not existend payment_transactions or transactions in wrong state for a follow up tranasction {.
class WorkflowError extends Error {
	public $status_code = 400;
}

// PaymentTransaction reference was not found for a follow up request.
class ReferenceNotFoundError extends WorkflowError {
	public $status_code = 410;
}

// PaymentTransaction is in wrong state for follow up request.
class ReferenceWorkflowError extends WorkflowError {
	public $status_code = 420;
}

// follow up request has allready been processed.
class ReferenceInvalidatedError extends WorkflowError {
	public $status_code = 430;
}

// reference missmatches.
class ReferenceMismatchError extends WorkflowError {
	public $status_code = 440;
}

// a request is resent to often within a specific timeout.
class DoubletTransactionError extends WorkflowError {
	public $status_code = 450;
}

// the requested PaymentTransaction was not found.
class TransactionNotFoundError extends WorkflowError {
	public $status_code = 460;
}

// Base class for all processing errors resulting from a request like invalid card number or expired / exceeded cards {.
class ProcessingError extends Error {
	public $status_code = 500;
}

// card number is not a valid credit card number.
class InvalidCardError extends ProcessingError {
	public $status_code = 510;
}

// credit card has already been expired.
class ExpiredCardError extends ProcessingError {
	public $status_code = 520;
}

// transaction is still pending.
class TransactionPendingError extends ProcessingError {
	public $status_code = 530;
}

// requested amount exceeds card limit.
class CreditExceededError extends ProcessingError {
	public $status_code = 540;
}

// Base class for all rocessing errors like declined transactions or risk management {.
class RiskError extends ProcessingError {
	public $status_code = 600;
}

// credit card number is blacklisted
class CardBlacklistError extends RiskError {
	public $status_code = 610;
}

// bin (bank number within the cared card number) is blacklisted
class BinBlacklistError extends RiskError {
	public $status_code = 611;
}

// country is blacklisted
class CountryBlacklistError extends RiskError {
	public $status_code = 612;
}

// ip address is blacklisted
class IpBlacklistError extends RiskError {
	public $status_code = 613;
}

// some value is blacklisted
class BlacklistError extends RiskError {
	public $status_code = 614;
}

// transaction amount or count by credit card exceeds PanVelocityFilter.
class CardLimitExceededError extends RiskError {
	public $status_code = 620;
}

// transaction amount or count exceeds ChannelVelocityFilter.
class ChannelLimitExceededError extends RiskError {
	public $status_code = 621;
}

// transaction amount or count exceeds ContractVelocityFilter.
class ContractLimitExceededError extends RiskError {
	public $status_code = 622;
}

// Velocity (amount / timeframe) exceeded on card
class CardVelocityExceededError extends RiskError {
	public $status_code = 623;
}

// Amount Exceedes configured maximum
class CardTicketSizeExceededError extends RiskError {
	public $status_code = 624;
}

// User limit exceeded
class UserLimitExceededError extends RiskError {
	public $status_code = 625;
}

class MultipleFailureDetectionError extends RiskError {
	public $status_code = 626;
}

class CSDetectionError extends RiskError {
	public $status_code = 627;
}

class RecurringLimitExceededError extends RiskError {
	public $status_code = 628;
}

// Address Verfification System Error
class AvsError extends RiskError {
	public $status_code = 690;
}

// Base class for all errors occured on Acquirer gateway like timeout or workflow errors {.
class AcquirerError extends Error {
	public $status_code = 900;
}

// Acquirer system is unavailable.
class AcquirerSystemError extends AcquirerError {
	public $status_code = 910;
}

// configuration on Acquirer side is malformed or invalid.
class AcquirerConfigurationError extends AcquirerError {
	public $status_code = 920;
}

// transaction data is malformed.
class AcquirerDataError extends AcquirerError {
	public $status_code = 930;
}

// workflow errors accur on Acquirer side.
class AcquirerWorkflowError extends AcquirerError {
	public $status_code = 940;
}

// Acquirer connection timed out.
class AcquirerTimeoutError extends AcquirerError {
	public $status_code = 960;
}

// Acquirer connection fails.
class AcquirerConnectionError extends AcquirerError {
	public $status_code = 960;
}

/**
* maps remote Hypercharge XML API status_code (>= 100) to Hypercharge\Errors\Error subclasses.
* Local errors status_code 0 - 99 not included.
* php doesn't support variables in namespace so we have to wrap it into a class.
*/
class ERROR_MAPPING {
	static $map = array(
		'100' => 'SystemError',
		'101' => 'MaintenanceError',
		'110' => 'AuthenticationError',
		'120' => 'ConfigurationError',
		'200' => 'CommunicationError',
		'210' => 'ConnectionError',
		'220' => 'AccountError',
		'230' => 'TimeoutError',
		'240' => 'ResponseError',
		'250' => 'ParsingError',
		'300' => 'InputDataError',
		'310' => 'InvalidTransactionTypeError',
		'320' => 'InputDataMissingError',
		'330' => 'InputDataFormatError',
		'340' => 'InputDataInvalidError',
		'350' => 'InvalidXmlError',
		'360' => 'InvalidConentTypeError',
		'400' => 'WorkflowError',
		'410' => 'ReferenceNotFoundError',
		'420' => 'ReferenceWorkflowError',
		'430' => 'ReferenceInvalidatedError',
		'440' => 'ReferenceMismatchError',
		'450' => 'DoubletTransactionError',
		'460' => 'TransactionNotFoundError',
		'500' => 'ProcessingError',
		'510' => 'InvalidCardError',
		'520' => 'ExpiredCardError',
		'530' => 'TransactionPendingError',
		'540' => 'CreditExceededError',
		'600' => 'RiskError',
		'610' => 'CardBlacklistError',
		'611' => 'BinBlacklistError',
		'612' => 'CountryBlacklistError',
		'613' => 'IpBlacklistError',
		'614' => 'BlacklistError',
		'620' => 'CardLimitExceededError',
		'621' => 'ChannelLimitExceededError',
		'622' => 'ContractLimitExceededError',
		'623' => 'CardVelocityExceededError',
		'624' => 'CardTicketSizeExceededError',
		'625' => 'UserLimitExceededError',
		'626' => 'MultipleFailureDetectionError',
		'627' => 'CSDetectionError',
		'628' => 'RecurringLimitExceededError',
		'690' => 'AvsError',
		'900' => 'AcquirerError',
		'910' => 'AcquirerSystemError',
		'920' => 'AcquirerConfigurationError',
		'930' => 'AcquirerDataError',
		'940' => 'AcquirerWorkflowError',
		'950' => 'AcquirerTimeoutError',
		'960' => 'AcquirerConnectionError'
	);

	/**
	* @param int|string $code
	* @return Hypercharge\Errors\Error or null
	*/
	static function get($code) {
		return @ self::$map[(string)$code];
	}
}


