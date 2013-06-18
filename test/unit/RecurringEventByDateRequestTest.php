<?php
namespace Hypercharge;

require_once dirname(__DIR__).'/test_helper.php';

class RecurringEventByDateRequestTest extends HyperchargeTestCase {

	function setUp() {
		$this->serializer = new XmlSerializer(new XmlMapping());
	}

	function testCreateResponseThrowsError() {
		$data = $this->parseXml($this->schemaResponse('recurring_schedule_error.xml'));
		$req = new RecurringEventByDateRequest();
		try {
			$req->createResponse($data);
			$this->fail('no Exception thrown');
		} catch(Errors\InputDataMissingError $e) {
			$this->assertEqual($e->status_code, 320);
			$this->assertEqual($e->getMessage(), 'Please check input data for errors!');
			$this->assertEqual($e->technical_message, 'Parameter start_date is invalid.');
			return;
		}
		$this->fail('unexpected Exception type');
	}

	function testCreateResponseEmpty() {
		$data = $this->parseXml($this->schemaResponse('recurring_schedule_empty_events.xml'));
		$req = new RecurringEventByDateRequest();
		$p = $req->createResponse($data);
		$this->assertIsA($p, 'Hypercharge\PaginatedCollection');
		$this->assertEqual($p->getEntries(), array());
		$this->assertEqual($p->getCount(), 0);
		$this->assertEqual($p->getTotalCount(), 0);
		$this->assertEqual($p->getPagesCount(), 1);
		$this->assertEqual($p->getPerPage(), 10);
	}

	function testCreateResponseSingleSchedule() {
		$data = $this->parseXml($this->schemaResponse('recurring_schedule_single_event.xml'));
		$req = new RecurringEventByDateRequest();
		$p = $req->createResponse($data);
		$this->assertIsA($p, 'Hypercharge\PaginatedCollection');
		$this->assertEqual($p->getCount(), 1);
		$this->assertEqual($p->getTotalCount(), 1);
		$this->assertEqual($p->getPagesCount(), 1);
		$this->assertEqual($p->getPerPage(), 100);
		$r = $p->current();
		$this->assertIsA($r, 'Hypercharge\RecurringEvent');
		$this->assertEqual($r->status , 'approved');
		$this->assertEqual($r->due_date , '2012-04-15');
		$this->assertEqual($r->finalized_at , '2012-04-15');
	}

	function testCreateResponsePage1With3() {
		$data = $this->parseXml($this->schemaResponse('recurring_schedule.xml'));
		$req = new RecurringEventByDateRequest();
		$p = $req->createResponse($data);
		$this->assertIsA($p, 'Hypercharge\PaginatedCollection');
		$this->assertEqual($p->getCount(), 3);
		$this->assertEqual($p->getTotalCount(), 3);
		$this->assertEqual($p->getPagesCount(), 1);
		$this->assertEqual($p->getPerPage(), 100);

		$r = $p->current();
		$this->assertIsA($r, 'Hypercharge\RecurringEvent');
		$this->assertEqual($r->status , 'approved');
		$this->assertEqual($r->due_date , '2012-04-15');
		$this->assertEqual($r->finalized_at , '2012-04-15');

		$p->next();
		$r = $p->current();
		$this->assertIsA($r, 'Hypercharge\RecurringEvent');
		$this->assertEqual($r->status , 'approved');
		$this->assertEqual($r->due_date , '2012-05-15');
		$this->assertEqual($r->finalized_at , '2012-05-16');

		$p->next();
		$r = $p->current();
		$r = $p->current();
		$this->assertIsA($r, 'Hypercharge\RecurringEvent');
		$this->assertEqual($r->status , 'approved');
		$this->assertEqual($r->due_date , '2012-06-15');
		$this->assertEqual($r->finalized_at , '2012-06-15');
	}

}