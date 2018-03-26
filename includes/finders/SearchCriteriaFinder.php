<?php

require_once 'BaseFinder.php';

interface SearchCriteriaFinderInterface extends BaseFinderInterface {
	function setUserId ($user_id);
	function setSearchCriteriaId ($search_criteria_id);
	static function getSearchCriteriaIdFromUserId ($user_id);
} // SearchCriteriaFinderInterface

class SearchCriteriaFinder extends BaseFinder implements SearchCriteriaFinderInterface {
	use SearchCriteriaTraits;

	/* @see setUserId() */ protected $userId;
	/* @see setSearchCriteriaId() */ protected $searchCriteriaId;


	public function find (array $resource_fields = null): mysqli_result {
		$where = [];
		if ($this->userId) {
			$where[static::$tableName .'.user_id'] = $this->userId;
		}
		if ($this->searchCriteriaId) {
			$where[static::$tableName .'.search_criteria_id'] = $this->searchCriteriaId;
		}
		$where_clause = $where ? ' where ' .DB::where($where) : '';

		$desired_fields = $this->getValidDesiredFields($resource_fields ?: [static::$primaryKeyName]);

		$query = '
			select ' .implode(', ', $desired_fields) .'
			from ' .static::$tableName .'
			' .$where_clause;
		$result = DB::query($query);
		return $result;
	} // find


	/** @param integer $user_id */
	public function setUserId ($user_id) {
		$this->userId = (int)$user_id;
	} // setUserId

	/** @param $search_criteria_id */
	public function setSearchCriteriaId ($search_criteria_id) {
		$this->searchCriteriaId = (int)$search_criteria_id;
	} // setSearchCriteriaId

	/**
	 * @param integer $user_id
	 * @return int
	 */
	public static function getSearchCriteriaIdFromUserId ($user_id) {
		return (int)DB::getCell('select ' .static::$primaryKeyName .' from ' .static::$tableName .' where ' .DB::where(['user_id' => $user_id]));
	} // getSearchCriteriaIdFromUserId
} // SearchCriteriaFinder

