<?php
namespace Hypercharge;

/**
* base class for paginated requests with daterange as options
*/
abstract class PaginatedByDateRequest implements IRequest {

	/**
	* string yyyy-mm-dd  in UTC timezone
	*/
	public $start_date = '1970-01-01';
	/**
	* string yyyy-mm-dd optional  in UTC timezone
	*/
	public $end_date;
	/**
	* int 1-based
	*/
	public $page = 1;

	/**
	* @see Hypercharge\ReconcileByDateRequest#__construct
	*
	* @param array $p optional format {start_date:optional String, end_date:optional String, period: optional String or DateInterval, page:optional int}
	* @throws Exception if period is invalid DateInterval string representation.
	*/
	function __construct(array $p = null) {
		if($p) Helper::assign($this, $p);

		// calculate end_date with start_date and period
		if(isset($this->period)) {
			$period = $this->period;
			unset($this->period);
			if(!$period instanceof \DateInterval) $period = new \DateInterval($period);
			$start = new \DateTime($this->start_date, new \DateTimeZone('UTC'));
			$start->add($period);
			$this->end_date = $start->format('Y-m-d');
		}
	}

	/**
	* should be used in #createResponse() to create PaginatedCollectio
	* @param array $attr the pagination attributes {page:int, per_page:int, total_count:int, pages_count:int}
	*
	*/
	protected function createPaginatedCollection($attr) {
		return new PaginatedCollection($attr['page'], $attr['per_page'], $attr['total_count'], $attr['pages_count']);
	}

	/**
	* @param array $data
	* @return Hypercharge\PaginatedCollection containing instances of Hypercharge\Transaction
	*/
	abstract function createResponse($data);

	/**
	* Note: PagianatedByDateRequest#creteResponse($data) implementation must return a PaginatedCollection
	* @param Hypercharge\IUrl $url
	* @param function $callback function($entity) $entity is each instance contained in PaginatedCollection returned by #creteResponse($data) implementation
	*/
	function each($url, $callback) {
		$this->validate();
		$webservice = Config::getFactory()->createWebservice();
		while($page = $webservice->call($url, $this)) {
			foreach($page as $entity) {
				$callback($entity);
			}
			if(!$page->hasNextPage()) break;
			$this->page = $page->getNextPage();
		}
	}

	/**
	* @throws Hypercharge\Errors\ValidationError if not valid
	*/
	function validate() {
		$errors = new Errors\ValidationError();
		$dateFormat = '/^\d\d\d\d-\d\d-\d\d$/';
		if(!preg_match($dateFormat, $this->start_date))
			$errors->add('start_date', 'must be yyyy-mm-dd');
		if(!empty($this->end_date) && !preg_match($dateFormat, $this->end_date  ))
			$errors->add('end_date', 'must be yyyy-mm-dd');
		if(is_int($this->page) && $this->page <= 0)
			$errors->add('page', 'must be an integer greater or equal 1');

		if($errors->flush()) throw $errors;
	}
}

