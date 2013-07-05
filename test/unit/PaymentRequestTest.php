<?php
namespace Hypercharge;

require_once dirname(__DIR__).'/test_helper.php';

class PaymentRequestTest extends HyperchargeTestCase {

	function testConstructorShouldThrowExceptionWithEmptyType() {
		try {
			$p = new PaymentRequest(array());
			$this->fail('should throw exception');
		} catch(Errors\ValidationError $exe) {
			$this->assertEqual($exe->message, '1 validation error');
			$this->assertEqual($exe->technical_message, '1 affected property: type');
			$this->assertEqual($exe->errors[0]['property'], 'type');
			$this->assertEqual($exe->errors[0]['message'], 'must be one of "WpfPayment", "MobilePayment" but is: ""');
			return;
		}
		$this->fail('should throw ValidationError');
	}

	function testConstructorShouldThrowExceptionWithWrongType() {
		try {
			$p = new PaymentRequest(array('type' => 'Wrong'));
			$this->fail('should throw exception');
		} catch(Errors\ValidationError $exe) {
			$this->assertEqual($exe->message, '1 validation error');
			$this->assertEqual($exe->technical_message, '1 affected property: type');
			$this->assertEqual($exe->errors[0]['property'], 'type');
			$this->assertEqual($exe->errors[0]['message'], 'must be one of "WpfPayment", "MobilePayment" but is: "Wrong"');
			return;
		}
		$this->fail('should throw ValidationError');
	}

	function testConstructorShouldAppendRandomIdToTransactionId() {
		Config::setIdSeparator('___');
		$p = new PaymentRequest($this->fixture('wpf_payment_request_simple.json'));
		$p->validate();
		$this->assertEqual('WpfPayment', $p->type);
		$this->assertEqual('WpfPayment', $p->getType());
		$this->assertPattern('/^wev238f328nc___[a-f0-9]{13}/', $p->transaction_id);
	}

	function testConstructorShouldNotAppendRandomIdToTransactionId() {
		Config::setIdSeparator(false);
		$p = new PaymentRequest($this->fixture('wpf_payment_request_simple.json'));
		$p->validate();
		$this->assertPattern('/^wev238f328nc$/', $p->transaction_id);
	}

	function testCreateResponse() {
		$p = new PaymentRequest($this->fixture('wpf_payment_request_simple.json'));
		$r = $p->createResponse($this->parseResponseFixture('wpf_payment_response_simple.xml'));
		$this->assertIsA($r, 'Hypercharge\Payment');
		$this->assertEqual('WpfPayment', $r->type);
		$this->assertEqual('WpfPayment', $r->getType());
		$this->assertEqual('13412341234134123412341234123412341234134', $r->unique_id);
		$this->assertEqual(Payment::STATUS_NEW, $r->status);
	}

	function testCreateResponseErrorOnlyThrowsApiError() {
		$p = new PaymentRequest($this->fixture('wpf_payment_request_simple.json'));
		try {
			$p->createResponse($this->parseResponseFixture('wpf_payment_response_error_only.xml'));
			$this->fail('should throw exception');
		} catch(Errors\InputDataFormatError $exe) {
			$this->assertEqual($exe->message, 'The message');
			$this->assertEqual($exe->technical_message, 'The technical message');
			$this->assertEqual($exe->status_code, 330);
			return;
		}
		$this->fail('should throw InputDataFormatError');
	}

	function testCreateResponseErrorOnlyThrowsBaseError() {
		$p = new PaymentRequest($this->fixture('wpf_payment_request_simple.json'));
		try {
			$data = $this->parseResponseFixture('wpf_payment_response_error_only.xml');
			$data['payment']['code'] = '777777';
			$p->createResponse($data);
			$this->fail('should throw exception');
		} catch(Errors\Error $exe) {
			$this->assertEqual(get_class($exe), 'Hypercharge\Errors\Error');
			$this->assertEqual($exe->message, 'The message');
			$this->assertEqual($exe->technical_message, 'The technical message');
			$this->assertEqual($exe->status_code, 777777);
			return;
		}
		$this->fail('should throw Error');
	}

	function testAllowedTypesShouldAllHaveSchema() {
		$missingSchemas = array();
		foreach(PaymentRequest::getAllowedTypes() as $type) {
			$file = JsonSchemaValidator::schemaPathFor($type);
			if(!file_exists($file)) {
				$missingSchemas[] = $type;
			}
		}
		$this->assertEqual(0, sizeof($missingSchemas), "missing schemas for Payment types: ".join($missingSchemas, ', '));
	}

	function parseResponseFixture($xmlFile) {
		$dom = new \SimpleXMLElement($this->fixture($xmlFile));
		return XmlSerializer::dom2hash($dom);
	}
}