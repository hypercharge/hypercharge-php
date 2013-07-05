<?php
namespace Hypercharge;

require_once dirname(__DIR__).'/test_helper.php';

class TransactionRequestTest extends HyperchargeTestCase {

	function testConstructorShouldThrowExceptionWithEmptyTransactionType() {
		try {
			$p = new TransactionRequest(array());
			$this->fail('should throw exception');
		} catch(Errors\ValidationError $exe) {
			$this->assertEqual($exe->message, '1 validation error');
			$this->assertEqual($exe->technical_message, '1 affected property: transaction_type');
			$this->assertEqual($exe->errors[0]['property'], 'transaction_type');
			$this->assertEqual($exe->errors[0]['message'], 'value invalid: ""');
			return;
		}
		$this->fail('should throw ValidationError');
	}

	function testConstructorShouldThrowExceptionWithUnknonwnTransactionType() {
		try {
			$p = new TransactionRequest(array('transaction_type' => 'not_allowed'));
			$this->fail('should throw exception');
		} catch(Errors\ValidationError $exe) {
			$this->assertEqual($exe->message, '1 validation error');
			$this->assertEqual($exe->technical_message, '1 affected property: transaction_type');
			$this->assertEqual($exe->errors[0]['property'], 'transaction_type');
			$this->assertEqual($exe->errors[0]['message'], 'value invalid: "not_allowed"');
			return;
		}
		$this->fail('should throw ValidationError');
	}

	function testAllowedTypesShouldAllHaveSchema() {
		$missingSchemas = array();
		foreach(TransactionRequest::getAllowedTypes() as $type) {
			$file = JsonSchemaValidator::schemaPathFor($type);
			if(!file_exists($file)) {
				$missingSchemas[] = $type;
			}
		}
		$this->assertEqual(0, sizeof($missingSchemas), "missing schemas for Transaction types: ".join($missingSchemas, ', '));
	}

}

