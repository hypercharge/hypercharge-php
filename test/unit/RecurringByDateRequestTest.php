<?php
namespace Hypercharge;

require_once dirname(__DIR__).'/test_helper.php';

class RecurringByDateRequestTest extends HyperchargeTestCase {

	function setUp() {
		$this->serializer = new XmlSerializer(new XmlMapping());
	}

	function testCreateResponseThrowsError() {
		$data = $this->parseXml($this->schemaResponse('recurring_schedule_error.xml'));
		$req = new RecurringByDateRequest();
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
		$data = $this->parseXml($this->schemaResponse('recurring_schedules_empty_result.xml'));
		$req = new RecurringByDateRequest();
		$p = $req->createResponse($data);
		$this->assertIsA($p, 'Hypercharge\PaginatedCollection');
		$this->assertEqual($p->getEntries(), array());
		$this->assertEqual($p->getCount(), 0);
		$this->assertEqual($p->getTotalCount(), 0);
		$this->assertEqual($p->getPagesCount(), 1);
		$this->assertEqual($p->getPerPage(), 3);
	}

	function testCreateResponseSingleSchedule() {
		$data = $this->parseXml($this->schemaResponse('recurring_schedules_single_result.xml'));
		$req = new RecurringByDateRequest();
		$p = $req->createResponse($data);
		$this->assertIsA($p, 'Hypercharge\PaginatedCollection');
		$this->assertEqual($p->getCount(), 1);
		$this->assertEqual($p->getTotalCount(), 1);
		$this->assertEqual($p->getPagesCount(), 1);
		$this->assertEqual($p->getPerPage(), 3);
		$r = $p->current();
		$this->assertIsA($r, 'Hypercharge\RecurringScheduler');
		$this->assertEqual($r->recurring_reference_id, '3641b4fbe80051319a60ceeacf15c8c1');
		$this->assertEqual($r->amount , 8800);
		$this->assertEqual($r->start_date , '2012-07-15');
		$this->assertEqual($r->end_date , '2013-06-15');
		$this->assertEqual($r->interval , 'monthly');
		$this->assertEqual($r->active , true);
		$this->assertIsA($r->active , 'boolean');
		$this->assertEqual($r->enabled , true);
		$this->assertIsA($r->enabled , 'boolean');
	}

	function testCreateResponsePage1With3() {
		$data = $this->parseXml($this->schemaResponse('recurring_schedules_page_1.xml'));
		$req = new RecurringByDateRequest();
		$p = $req->createResponse($data);
		$this->assertIsA($p, 'Hypercharge\PaginatedCollection');
		$this->assertEqual($p->getCount(), 3);
		$this->assertEqual($p->getTotalCount(), 4);
		$this->assertEqual($p->getPagesCount(), 2);
		$this->assertEqual($p->getPerPage(), 3);

		$r = $p->current();
		$this->assertIsA($r, 'Hypercharge\RecurringScheduler');
		$this->assertEqual($r->recurring_reference_id, '44177a21403427eb96664a6d7e5d5d48');
		$this->assertEqual($r->amount , 1000);
		$this->assertEqual($r->start_date , '2012-05-15');
		$this->assertEqual($r->end_date , '2013-04-15');
		$this->assertEqual($r->interval , RecurringScheduler::MONTHLY);
		$this->assertEqual($r->active , true);
		$this->assertIsA($r->active , 'boolean');
		$this->assertEqual($r->enabled , true);
		$this->assertIsA($r->enabled , 'boolean');

		$p->next();
		$r = $p->current();
		$this->assertIsA($r, 'Hypercharge\RecurringScheduler');
		$this->assertEqual($r->recurring_reference_id, '5e2cbbad71d2b13432323153c208223a');
		$this->assertEqual($r->amount , 500);
		$this->assertEqual($r->start_date , '2012-06-15');
		$this->assertEqual($r->end_date , '2013-05-15');
		$this->assertEqual($r->interval , RecurringScheduler::QUARTERLY);
		$this->assertEqual($r->active , true);
		$this->assertIsA($r->active , 'boolean');
		$this->assertEqual($r->enabled , true);
		$this->assertIsA($r->enabled , 'boolean');

		$p->next();
		$r = $p->current();
		$this->assertIsA($r, 'Hypercharge\RecurringScheduler');
		$this->assertEqual($r->recurring_reference_id, '3641b4fbe80051319a60ceeacf15c8c7');
		$this->assertEqual($r->amount , 1500);
		$this->assertEqual($r->start_date , '2012-07-15');
		$this->assertEqual($r->end_date , '2013-06-15');
		$this->assertEqual($r->interval , RecurringScheduler::MONTHLY);
		$this->assertEqual($r->active , true);
		$this->assertIsA($r->active , 'boolean');
		$this->assertEqual($r->enabled , true);
		$this->assertIsA($r->enabled , 'boolean');
	}

}