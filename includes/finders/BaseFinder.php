<?php

interface BaseFinderInterface {
	function find ();
	function setPageNumber ($page_number);
	function setPageSize ($page_size);
	function getFoundRows ();
	function getFoundPages ();
	function getSearchQuery ();
	function getPageSize ();
	function getCurrentPage ();
} // BaseFinderInterface

abstract class BaseFinder implements BaseFinderInterface  {
	/** @var $pageSize int Set via `setPageSize()` */
	protected $pageSize = 10;

	/** @var $pageNumber int Set via `setPageNumber()` */
	protected $pageNumber = 1;

	/** @var $searchQuery string Get via getSearchQuery(). Set in find(). */
	protected $searchQuery;

	/** @var $searchResult mysqli_result */
	protected $searchResult;

	/** @var $foundRows int `getFoundRows()`. Requires `sql_calc_found_rows`, automated by `BaseFinder::query()`. */
	protected $foundRows;


	/** @return int The current page, set via setPageNumber() */
	public function getCurrentPage () {
		return $this->pageNumber;
	} // getCurrentPage

	/**
	 * Must be called after find().
	 * @return Number of rows that would have been returned if it weren't for `limit` clause.
	 * @throws Exception If sql_calc_found_rows wasn't used in search query.
	 */
	public function getFoundRows () {
		return $this->foundRows;
	} // getFoundRows

	/**
	 * @return string
	 * @see setPageNumber(), setPageSize()
	 */
	protected function getLimitClause () {
		if (!$this->pageSize) {
			return '';
		}
		$limit_offset = $this->pageNumber * $this->pageSize - $this->pageSize;
		return "limit $limit_offset, {$this->pageSize}";
	} // getLimitClause

	/**
	 * Must be called after find(). Assumes that the query in the extending class's find()
	 * method included 'sql_calc_found_rows' in its select clause.
	 * @return int
	 * @throws Exception
	 */
	public function getFoundPages () {
		if ($this->pageSize == 0) {
			return $this->getFoundRows() ? 1 : 0;
		}
		return ceil($this->getFoundRows() / $this->pageSize);
	} // getFoundPages

	/** @return int */
	public function getPageSize () {
		return (int)$this->pageSize;
	} // getPageSize

	/**
	 * @return string The query used by the extending class's `find()` method, useful for debugging.
	 * @throws Exception If extending class's `find()` method did not set `$this->searchQuery`.
	 */
	public function getSearchQuery () {
		if (empty($this->searchQuery)) {
			throw new Exception('Extending class never set $this->searchQuery.');
		}
		return $this->searchQuery;
	} // getSearchQuery

	/**
	 * @param array $alias_column_map DB fields indexed by user-friendly field names.
	 * @return string Contents of `select` clause, without the word `select`.
	 */
	protected static function getSelectClause ($alias_column_map) {
		$select = [];
		foreach ($alias_column_map as $desired_name => $db_name) {
			$select[] = "$db_name as $desired_name";
		}
		$select = implode("\n\t,", $select);
		return $select;
	} // getSelectClause

	/**
	 * @param array $desired_fields
	 * @return array
	 */
	protected function getValidDesiredFields (array $desired_fields): array {
		$available_fields = static::getAvailableFields();
		$valid_desired_fields = [];
		foreach ($desired_fields as $loop_field) {
			if (!isset($available_fields[$loop_field])) {
				trigger_error("Unrecognized field: $loop_field.", E_USER_WARNING);
				continue;
			}
			$valid_desired_fields[$loop_field] = $available_fields[$loop_field];
		}
		if (!$valid_desired_fields) {
			throw new InvalidArgumentException('No valid fields specified.');
		}
		return $valid_desired_fields;
	} // getValidDesiredFields


	/**
	 * This method returns a combination of regular and ad hoc fields.
	 * @return array SQL columns indexed by field names like: ['user_id' => 'users.user_id']
	 */
	protected static function getAvailableFields () {
		$available_fields = []; // return value
		foreach (static::$columnNames as $column_name) {
			$available_fields[$column_name] = static::$tableName .'.' .$column_name;
		}
		foreach (static::getAdHocFields() as $column_name => $column_definition) {
			$available_fields[$column_name] = $column_definition;
		}
		return $available_fields;
	} // getAvailableFields

	/**
	 * Not all fields returned by Finders have to actually exist in the database. For example, if
	 * your database has `users.first_name` and `users.last_name`, but you want to select the full
	 * name, you could select `concat(users.first_name, " ", users.last_name) as full_name`. In this
	 * case the `full_name` is an ad hoc field, it is really a concat command with an alias. To
	 * define ad hoc fields in your Finder, define this method. It should return an array of ad hoc
	 * field definitions indexed by their aliases; same format as `getAvailableFields()`. Your
	 * method will be called by `getAvailableFields()` and merged into its return value.
	 * @return array
	 */
	protected static function getAdHocFields () {
		return [];
	} // getAdHocFields


	public abstract function find (array $resource_fields = []): mysqli_result;

	/**
	 * Combine with setPageSize() to control `limit` clause.
	 * If a page has 50 items, and you want to get the second page, you'd call:
	 * $jobFinder->setPageSize(50);
	 * $jobFinder->setPageNumber(2);
	 * This would return items 51 through 100.
	 * @param int $page_number
	 * @see setPageSize(), getLimitClause()
	 */
	public function setPageNumber ($page_number = 1) {
		$this->pageNumber = (int)$page_number;
	} // setPageNumber

	/**
	 * Combine with setPageNumber() to control `limit` clause.
	 * If a page has 50 items, and you want to get the second page, you'd call:
	 * $jobFinder->setPageSize(50);
	 * $jobFinder->setPageNumber(2);
	 * This would return items 51 through 100.
	 * @param int $page_size
	 * @see setPageNumber(), getLimitClause()
	 */
	public function setPageSize ($page_size = 10) {
		$this->pageSize = (int)$page_size;
	} // setPageSize

	/**
	 * Intended to be called instead of DB::query() by $this->find(). It's a wrapper that sets relevant
	 * object properties and modifies the query to include `sql_calc_found_rows` and a limit clause.
	 * @param string $query
	 * @return mysqli_result
	 * @throws Exception
	 */
	protected function query (string $query) {
		$query = preg_replace('/\bselect\b/i', 'select sql_calc_found_rows', $query, 1);
		if ($this->pageSize) {
			$query .= "\n\t" .$this->getLimitClause();
		}
		$this->searchQuery = $query;
		$this->searchResult = DB::query($query);
		$this->foundRows = (int)DB::getCell('select found_rows()');
		return $this->searchResult;
	} // query
} // BaseFinder

