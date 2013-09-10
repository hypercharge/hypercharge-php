<?php
namespace Hypercharge;
require_once dirname(__DIR__).'/test_helper.php';

class PaginatedCollectionTest extends \UnitTestCase {

	function testConstructorCalculatesPagesCount() {

		$c = new PaginatedCollection();
		$this->assertEqual(0, $c->getPagesCount());

		$c = new PaginatedCollection(1,10,1);
		$this->assertEqual(1, $c->getPagesCount());

		$c = new PaginatedCollection(1,10,10);
		$this->assertEqual(1, $c->getPagesCount());

		$c = new PaginatedCollection(1,10,11);
		$this->assertEqual(2, $c->getPagesCount());

		$c = new PaginatedCollection(1,10,19);
		$this->assertEqual(2, $c->getPagesCount());

		$c = new PaginatedCollection(1,10,20);
		$this->assertEqual(2, $c->getPagesCount());

		$c = new PaginatedCollection(1,10,21);
		$this->assertEqual(3, $c->getPagesCount());
	}

	function testConstructorPagesCountBecomesInt() {

		$c = new PaginatedCollection();
		$this->assertIsA($c->getPagesCount(), 'int');
		$this->assertEqual(0, $c->getPagesCount());

		$c = new PaginatedCollection('1','10','1');
		$this->assertIsA($c->getPagesCount(), 'int');
		$this->assertEqual(1, $c->getPagesCount());

		$c = new PaginatedCollection('1','10','1', '1');
		$this->assertIsA($c->getPagesCount(), 'int');
		$this->assertEqual(1, $c->getPagesCount());

		$c = new PaginatedCollection('1','10','25');
		$this->assertIsA($c->getPagesCount(), 'int');
		$this->assertEqual(3, $c->getPagesCount());

		$c = new PaginatedCollection('1','10','25', '3');
		$this->assertIsA($c->getPagesCount(), 'int');
		$this->assertEqual(3, $c->getPagesCount());
	}

	function testConstructYieldsWithObject() {
		$res = json_decode(JsonSchemaFixture::response('scheduler_page_2.json'));
		$this->assertIsA($res, 'StdClass');
		$c = new PaginatedCollection($res, function($entry) {
			return new Transaction($entry);
		});
		$this->assertIdentical(10, $c->getTotalCount());
		$this->assertIdentical( 2, $c->getPage());
		$this->assertIdentical( 2, $c->getPagesCount());
		$this->assertIdentical( 7, $c->getPerPage());
		$this->assertIdentical(3, count($c->getEntries()));

		$uids = array();
		foreach($c as $trx) {
			$uids[] = $trx->unique_id;
			$this->assertIsA($trx, 'Hypercharge\Transaction');
			$this->assertIsA($trx->amount, 'int');
			$this->assertIsA($trx->active, 'boolean');
		}

		$this->assertEqual(
			array(
				'b39cf4adcdea97eb55903eab47518272'
				,'912f9a230e6f5166f2708616cf7ee805'
				,'c14bb3479c669ecd5393ba700a01392b'
			)
			, $uids
		);
	}

}