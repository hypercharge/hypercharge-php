<?php
namespace Hypercharge;

class PaginatedCollection implements \Iterator {
	private $position = 0;
	private $entries = array();

	protected $page, $per_page, $total_count, $pages_count;

	/**
	* 2 way to call:
	* a) new PaginatedCollection($jsonResponseObject, function($entry) { return new Foobar($entry); });
	* c) new PaginatedCollection($response['page'], $response['per_page'], $response['total_count'], $response['pages_count'])
	*
	*/
	public function __construct($page=1, $per_page=10, $total_count=0, $pages_count=null) {
		if(is_object($page)) {
			if(!$page->type == 'PaginatedCollection') {
				throw new Errors\ResponseFormatError('"type" must be "PaginatedCollection"', $page);
			}
			// (a)
			$this->page        = (int) $page->current_page;
			$this->per_page    = (int) $page->per_page;
			$this->total_count = (int) $page->total_entries;

			// fill entries using callback as converter
			if(is_a($per_page, 'Closure')) {
				foreach($page->entries as $entry) {
					// yield $per_page as callback
					$this->entries[] = $per_page($entry);
				}
			}

		} else {
			// (b)
			$this->page = (int) $page;
			$this->per_page = (int) $per_page;
			$this->total_count = (int) $total_count;
		}

		// calculate pages_count if not given
		if($pages_count==null) $pages_count = ceil($this->total_count / max($this->per_page, 1));
		$this->pages_count = (int) $pages_count;
	}
	/* ********
	* BEGIN php Iterator methods
	* iterates over entries
	*/
	function rewind() {
		$this->position = 0;
	}

	function current() {
		return $this->entries[$this->position];
	}

	function key() {
		return $this->position;
	}

	function next() {
		++$this->position;
	}

	function valid() {
		return isset($this->entries[$this->position]);
	}
	/*
	* END php Iterator methods
	* **********/

	function getCount() {
		return count($this->entries);
	}

	/* ********
	* BEGIN ruby-style Pagination methods
	*/

	function getPage() {
		return $this->page;
	}

	function getPerPage() {
		return $this->per_page;
	}

	function getTotalCount() {
		return $this->total_count;
	}

	function getPagesCount() {
		return $this->pages_count;
	}

	function getEntries() {
		return $this->entries;
	}

	function hasNextPage() {
		return $this->page < $this->pages_count;
	}
	/**
	* @return int next page number or null if no next page
	*/
	function getNextPage() {
		if($this->hasNextPage()) return $this->page + 1;
	}
	/*
	* END ruby Pagination methods
	* **********/

	function push($e) {
		array_push($this->entries, $e);
	}

}