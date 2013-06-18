<?php
namespace Hypercharge;

require_once dirname(__DIR__).'/test_helper.php';

class RecurringSchedulerTest extends HyperchargeTestCase {

	function testConstructorEmpty() {
		$r = new RecurringScheduler(array());
		$this->assertFalse(isset($r->recurring_reference_id));
		$this->assertFalse(isset($r->amount));
		$this->assertFalse(isset($r->start_date));
		$this->assertFalse(isset($r->end_date));
		$this->assertFalse(isset($r->active));
		$this->assertFalse(isset($r->enabled));
	}

	function testConstructorCastsStringToBool() {
		$r = new RecurringScheduler(array('amount'=>'1230', 'active'=>'true', 'enabled'=>'false'));
		$this->assertEqual($r->amount, 1230);
		$this->assertTrue($r->active);
		$this->assertFalse($r->enabled);
		$this->assertIsA($r->amount, 'integer');
		$this->assertIsA($r->active, 'boolean');
		$this->assertIsA($r->enabled, 'boolean');
	}

	function testConstructorStaysBool() {
		$r = new RecurringScheduler(array('amount'=>2400, 'active'=>true, 'enabled'=>false));
		$this->assertEqual($r->amount, 2400);
		$this->assertTrue($r->active);
		$this->assertFalse($r->enabled);
		$this->assertIsA($r->amount, 'integer');
		$this->assertIsA($r->active, 'boolean');
		$this->assertIsA($r->enabled, 'boolean');
	}

}