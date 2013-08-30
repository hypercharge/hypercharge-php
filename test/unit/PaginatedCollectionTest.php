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

}