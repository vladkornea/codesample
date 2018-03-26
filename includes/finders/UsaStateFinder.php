<?php

class UsaStateFinder extends BaseFinder {
	use UsaStateTraits;

	public function find (array $resource_fields = ['usa_state_id', 'code', 'name']): mysqli_result {
		$valid_desired_fields = $this->getValidDesiredFields($resource_fields ?: [static::$primaryKeyName]);
		$from = static::$tableName;
		$where = [];
		$where_clause = $where ? "where " .implode(" and ", $where) : '';
		$columns = parent::getSelectClause($valid_desired_fields);
		$query = "select $columns from $from $where_clause order by state_name";
		$result = parent::query($query);
		return $result;
	} // find

	protected static function getAdHocFields (): array {
		return ['code' => 'state_code', 'name' => 'state_name'];
	} // getAdHocFields

	public static function getAllUsaStates (): array {
		$usaStateFinder = new static;
		$usaStateFinder->setPageSize(0);
		$countries_resource = $usaStateFinder->find(['code', 'name']);
		$countries_array = DB::getTable($countries_resource);
		return $countries_array;
	} // getAllUsaStates

	public static function isTableEmpty (): bool {
		return !DB::getCell('select exists(select true from ' .static::$tableName .' limit 1)');
	} // isTableEmpty
} // UsaStateFinder

