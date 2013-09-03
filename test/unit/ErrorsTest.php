<?php
namespace Hypercharge\Errors;

require_once dirname(__DIR__).'/test_helper.php';

class ErrorsTest extends \UnitTestCase {

	function testFactoryWithIntCode() {
		$e = errorFromResponseHash(array('code'=>110, 'message'=>'the msg', 'technical_message'=>'the tech msg'));
		$this->assertIsA($e, 'Hypercharge\Errors\AuthenticationError');
		$this->assertIsA($e->status_code, 'int');
		$this->assertEqual($e->status_code, 110);
		$this->assertEqual($e->message, 'the msg');
		$this->assertEqual($e->technical_message, 'the tech msg');
		$this->assertEqual($e->__toString(), "AuthenticationError {status_code: 110, technical_message: 'the tech msg', message: 'the msg'}");
	}

	function testFactoryWithStringCode() {
		$e = errorFromResponseHash(array('code'=>'110', 'message'=>'the msg', 'technical_message'=>'the tech msg'));
		$this->assertIsA($e, 'Hypercharge\Errors\AuthenticationError');
		$this->assertIsA($e->status_code, 'int');
		$this->assertEqual($e->status_code, 110);
		$this->assertEqual($e->message, 'the msg');
		$this->assertEqual($e->technical_message, 'the tech msg');
		$this->assertEqual($e->__toString(), "AuthenticationError {status_code: 110, technical_message: 'the tech msg', message: 'the msg'}");
	}

	function testFactoryWithUnknownCode() {
		$e = errorFromResponseHash(array('code'=>1, 'message'=>'the msg', 'technical_message'=>'the tech msg'));
		$this->assertIsA($e, 'Hypercharge\Errors\Error');
		$this->assertIsA($e->status_code, 'int');
		$this->assertEqual($e->status_code, 1);
		$this->assertEqual($e->message, 'the msg');
		$this->assertEqual($e->technical_message, 'the tech msg');
		$this->assertEqual($e->__toString(), "Error {status_code: 1, technical_message: 'the tech msg', message: 'the msg'}");
	}

	function testArgumentError() {
		$e = new ArgumentError('the msg');
		$this->assertIsA($e, 'Hypercharge\Errors\ArgumentError');
		$this->assertIsA($e->status_code, 'int');
		$this->assertEqual($e->status_code, 40);
		$this->assertEqual($e->message, 'the msg');
		$this->assertEqual($e->technical_message, '');
		$this->assertEqual($e->__toString(), "ArgumentError {status_code: 40, technical_message: '', message: 'the msg'}");
	}


	function testValidationError() {
		$errors = array(array('property'=>'payment.type', 'message'=>'must be xy'));
		$e = new ValidationError($errors);
		$this->assertIsA($e, 'Hypercharge\Errors\ValidationError');
		$this->assertIsA($e->status_code, 'int');
		$this->assertEqual($e->status_code, 50);
		$this->assertEqual($e->message, '1 validation error');
		$this->assertEqual($e->technical_message, '1 affected property: payment.type');
		$this->assertEqual($e->__toString(), "ValidationError {status_code: 50, technical_message: '1 affected property: payment.type', message: '1 validation error'}");
		$this->assertEqual($e->errors, $errors);
	}

	function testValidationErrorsStripsCc() {
		$errors = array(array('property'=>'a', 'message'=>'am'), array('property'=>'b', 'message'=>'bm "card_number":"42000000000"'));
		$errorsStripped = array(array('property'=>'a', 'message'=>'am'), array('property'=>'b', 'message'=>'bm "card_number":"xxxxxxxxxxxxxxxxxxx"'));
		$e = new ValidationError($errors);
		$this->assertIsA($e, 'Hypercharge\Errors\ValidationError');
		$this->assertIsA($e->status_code, 'int');
		$this->assertEqual($e->status_code, 50);
		$this->assertEqual($e->message, '2 validation errors');
		$this->assertEqual($e->technical_message, '2 affected properties: a, b');
		$this->assertEqual($e->__toString(), "ValidationError {status_code: 50, technical_message: '2 affected properties: a, b', message: '2 validation errors'}");
		$this->assertEqual($e->errors, $errorsStripped);
	}

	function testValidationErrorFlushSetsFieldnamesAsTechMsg() {
		$e = new ValidationError();
		$this->assertEqual($e->technical_message, '');
		$e->add('a', 'the issue with a');
		$this->assertEqual($e->technical_message, '');
		$this->assertTrue($e->flush());
		$this->assertEqual($e->technical_message, '1 affected property: a');
		$e->add('a', 'an other issue with a');
		$e->flush();
		$this->assertEqual($e->message, '2 validation errors');
		$this->assertEqual($e->technical_message, '1 affected property: a');
		$e->add('b', 'an issue with b');
		$e->flush();
		$this->assertEqual($e->message, '3 validation errors');
		$this->assertEqual($e->technical_message, '2 affected properties: a, b');
	}

	function testValidationErrorAdd() {
		$errors = array();

		$e = new ValidationError();
		$this->assertEqual($e->message, '0 validation errors');
		$this->assertEqual($e->technical_message, '');
		$this->assertEqual($e->errors, $errors);

		$this->assertFalse($e->flush());
		$this->assertEqual($e->message, '0 validation errors');
		$this->assertEqual($e->technical_message, '');

		$errors[] = array('property'=>'a', 'message'=>'msg a');
		$e->add('a', 'msg a');
		$this->assertTrue($e->flush());
		$this->assertEqual($e->message, '1 validation error');
		$this->assertEqual($e->technical_message, '1 affected property: a');
		$this->assertEqual($e->errors, $errors);

		$errors[] = array('property'=>'b', 'message'=>'msg b');
		$e->add('b', 'msg b');
		$this->assertTrue($e->flush());
		$this->assertEqual($e->message, '2 validation errors');
		$this->assertEqual($e->technical_message, '2 affected properties: a, b');
		$this->assertEqual($e->errors, $errors);
	}

	function testValidationErrorToStringEquality() {
		// same property
		$this->assertEqual(
			 ValidationError::create('equal', 'equal info').''
			,ValidationError::create('equal', 'equal info').''
		);
	 	// same property, message ignored in __toString()
		$this->assertEqual(
			 ValidationError::create('equal', 'bbb').''
			,ValidationError::create('equal', 'different').''
		);
		// different properties
		$this->assertNotEqual(
			 ValidationError::create('a'        , 'equal info').''
			,ValidationError::create('different', 'equal info').''
		);
	}

	function testNetworkError() {
		$e = new NetworkError('https://the.url.com', 0,'socket timeout', '<foo><card_number>420000000000</card_number></foo>');
		$this->assertIsA($e, 'Hypercharge\Errors\NetworkError');
		$this->assertIdentical($e->status_code, 10);
		$this->assertIdentical($e->http_status, 0);
		$this->assertEqual($e->message, 'Connection to Payment Gateway failed.');
		$this->assertEqual($e->technical_message, 'socket timeout');
		$this->assertEqual($e->__toString(), "NetworkError {status_code: 10, technical_message: 'socket timeout', message: 'Connection to Payment Gateway failed.'}");
		$this->assertEqual($e->url, 'https://the.url.com');
		$this->assertEqual($e->body, '<foo><card_number>xxxxxxxxxxxxxxxxxxx</card_number></foo>');
	}

	function testXmlParsingError() {
		$e = new XmlParsingError('the msg <foo><card_number>420000000000</card_number></foo>', 20, 7);
		$this->assertIsA($e, 'Hypercharge\Errors\XmlParsingError');
		$this->assertIdentical($e->status_code, 60);
		$this->assertEqual($e->message, 'the msg <foo><card_number>xxxxxxxxxxxxxxxxxxx</card_number></foo>');
		$this->assertEqual($e->technical_message, 'at line 20 column 7');
		$this->assertEqual($e->__toString(), "XmlParsingError {status_code: 60, technical_message: 'at line 20 column 7', message: 'the msg <foo><card_number>xxxxxxxxxxxxxxxxxxx</card_number></foo>'}");
		$this->assertEqual($e->line, 20);
		$this->assertEqual($e->column, 7);
	}

	function testJsonResponseInputDataInvalidError() {
		$data = json_decode(\Hypercharge\JsonSchemaFixture::response('scheduler_error.json'));
		$this->assertIsA($data, 'stdClass');
		$this->assertIdentical(340, $data->error->code);
		$e = errorFromResponseHash($data->error);
		$this->assertIsa($e, 'Hypercharge\Errors\InputDataInvalidError');
		$this->assertEqual('Please check input data for errors!', $e->message);
		$this->assertEqual("Validation failed: Amount InputDataInvalidError: 'recurring_schedule[amount]' is invalid", $e->technical_message);
	}

	function testJsonResponseWorkflowError() {
		$data = json_decode(\Hypercharge\JsonSchemaFixture::response('scheduler_workflow_error.json'));
		$this->assertIsA($data, 'stdClass');
		$this->assertIdentical(400, $data->error->code);
		$e = errorFromResponseHash($data->error);
		$this->assertIsa($e, 'Hypercharge\Errors\WorkflowError');
		$this->assertEqual('Something went wrong, please contact support!', $e->message);
		$this->assertEqual("transaction already has a schedule.", $e->technical_message);
	}

	function testResponseFormatErrorStripsCc() {
		$e = new ResponseFormatError('the message', array('cvv'=>'123', 'card_number'=>'4200000000000000', 'foo'=>'bar'));
		$this->assertEqual('the message', $e->message);
		$this->assertEqual(
'Array
(
    [cvv] => xxx
    [card_number] => xxxxxxxxxxxxxxxxxxx
    [foo] => bar
)
'
			,$e->technical_message
		);

	}

}