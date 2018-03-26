<?php

require_once 'BaseFinder.php';

interface HistoricEventFinderInterface extends BaseFinderInterface {
	function getRecentEvents ($current_page = 1, $page_size = 100);
} // HistoricEventFinderInterface

class HistoricEventFinder extends BaseFinder implements HistoricEventFinderInterface {
	use HistoricEventTraits;

	protected $orderBy = null;
	protected $tableFilter = null;

	/**
	 * @param int $current_page
	 * @param int $page_size
	 * @return mysqli_result
	 */
	public function getRecentEvents ($current_page = 1, $page_size = 100) {
		$this->setPageSize($page_size);
		$this->setPageNumber($current_page);
		$this->orderBy = static::$primaryKeyName .' desc';
		return $this->find();
	} // getRecentEvents


	public function setTableFilter ($table_name) {
		$this->tableFilter = $table_name;
	} // setTableFilter


	public function find (array $resource_fields = null): mysqli_result {
		$valid_desired_fields = $this->getValidDesiredFields($resource_fields ?: [static::$primaryKeyName]);

		$where = [];
		if ($this->tableFilter) {
			$where['table_name'] = $this->tableFilter;
		}

		$where_clause = $where ? 'where '.DB::where($where) : '';
		$order_by_clause = $this->orderBy ? "order by $this->orderBy" : '';
		$query = "select " .implode(', ', $valid_desired_fields) ." from " .static::$tableName ." $where_clause $order_by_clause";
		$result = $this->query($query);
		return $result;
	} // find
} // HistoricEventFinder

