<?php

require_once 'BaseFinder.php';

class CountryFinder extends BaseFinder {
	use CountryTraits;

	public function find (array $resource_fields = ['country_id', 'country_code', 'country']): mysqli_result {
		$valid_desired_fields = $this->getValidDesiredFields($resource_fields ?: [static::$primaryKeyName]);
		$from = static::$tableName;
		$where = [];
		$where_clause = $where ? "where " .implode(" and ", $where) : '';
		$columns = implode(', ', $valid_desired_fields);
		$query = "select $columns from $from $where_clause order by country_name";
		$result = parent::query($query);
		return $result;
	} // find

	public static function getAllCountries (array $fields = ['country_code', 'country_name']): array {
		$countryFinder = new static;
		$countryFinder->setPageSize(0);
		$countries_resource = $countryFinder->find($fields);
		$countries_array = DB::getTable($countries_resource);
		return $countries_array;
	} // getAllCountries

	public static function isTableEmpty (): bool {
		return !DB::getCell('select exists(select true from ' .static::$tableName .' limit 1)');
	} // isTableEmpty
} // CountryFinder

