<?php
namespace Hypercharge;

class PaginatedCollection implements \Iterator {
    private $position = 0;
    private $entries = array();

    protected $page, $per_page, $total_count, $pages_count;

    public function __construct($page=1, $per_page=10, $total_count=0, $pages_count=1) {
        $this->page = (int) $page;
        $this->per_page = (int) $per_page;
        $this->total_count = (int) $total_count;
        $this->pages_count = (int) $pages_count;
    }
    /* ********
    * BEGIN php Iterator methods
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
    * BEGIN ruby Pagination methods
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