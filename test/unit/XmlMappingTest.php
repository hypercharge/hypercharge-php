<?php
require_once dirname(__DIR__).'/test_helper.php';

class XmlMappingTest extends UnitTestCase {

	function testGetRootName() {
		$a = array(); // dummy
		$m = new Hypercharge\XmlMapping();
		$this->assertEqual('payment', $m->getRootName(new Hypercharge\PaymentRequest(array('type'=>'WpfPayment'))));
		$this->assertEqual('payment_transaction', $m->getRootName(new Hypercharge\TransactionRequest(array('transaction_type'=>'sale'))));
	}

	function testGetRootNameThrowsException() {
		$m = new Hypercharge\XmlMapping();
		$this->expectException(new PatternExpectation('/class "Address" has no root-name mapping/'));
		$m->getRootName(new Hypercharge\Address(array()));
	}

	function testGetClass() {
		$m = new Hypercharge\XmlMapping();
		$this->assertEqual('Hypercharge\Address', $m->getClass('billing_address'));
		$this->assertEqual('Hypercharge\Address', $m->getClass('shipping_address'));
		$this->assertEqual('Hypercharge\RiskParams', $m->getClass('risk_params'));
		$this->assertEqual('Hypercharge\Transaction', $m->getClass('payment_transaction'));
		$this->assertEqual('Hypercharge\MpiParams', $m->getClass('mpi_params'));
		$this->assertEqual('Hypercharge\Scheduler', $m->getClass('recurring_schedule'));
	}


	function testGetConverter() {
		$m = new Hypercharge\XmlMapping();
		$this->assertIsA($m->getConverter('transaction_types'), 'Hypercharge\TransactionTypes');
		$this->assertIsA($m->getConverter('payment_methods')  , 'Hypercharge\PaymentMethods'  );
	}
}