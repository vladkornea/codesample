<?php

interface BaseModelInterface {
	static function create (array $form_data);
	function update (array $form_data);
	function getId ();
	function getRecord ();
} // BaseModelInterface

/** @class BaseModel Contains methods common to models. */
abstract class BaseModel implements BaseModelInterface {
	/** @var int Value of record's primary key field. */
	protected $id = null;
	/** @var array The record's row loaded from the database using loadRecord() */
	protected $record = [];

	function __construct (int $id) {
		$this->id = (int)$id;
	} // __construct

	/**
	 * Loads record's data from database into member properties.
	 * @param string $where To replace default where clause (which select by primary key).
	 * @throws Exception Thrown if no records found.
	 */
	protected function loadRecord ($where = null): void {
		if ($where === null) {
			$where = DB::where([static::$primaryKeyName => $this->id]);
		}
		$query = '
			select ' .implode(', ', static::$columnNames) .'
			from ' .static::$tableName .'
			where ' .$where;
		$this->record = DB::getRow($query);
		if (!$this->record) {
			throw new Exception("Error loading model record: Query returned empty result: $query");
		}
	} // loadRecord

	/**
	 * Inserts row into database and returns its insert ID. Extending
	 * classes should make this method public and return either an array
	 * of error messages or the insert ID returned by parent::create()
	 * @param array $form_data Associative array of fields to insert.
	 * @return int ID of inserted row.
	 * @throws InvalidArgumentException Thrown if the primary key field is set in the provided data.
	 */
	public static function create (array $form_data) {
		if (array_key_exists(static::$primaryKeyName, $form_data))  {
			throw new InvalidArgumentException(
				"Cannot create record from data whose primary key is already set ("
				.static::$primaryKeyName ."=" .$form_data[static::$primaryKeyName] .")."
			);
		}
		$insert = [];
		foreach ($form_data as $column_name => $value) {
			if (in_array($column_name, static::$columnNames)) {
				$insert[$column_name] = $value;
			} else {
				trigger_error("Unknown column: $column_name", E_USER_WARNING);
			}
		}
		if (!array_key_exists('inserted', $form_data)) {
			if (in_array('inserted', static::$columnNames)) {
				$insert['inserted'] = DB::verbatim('now()');
			}
		}
		return DB::insert(static::$tableName, $insert);
	} // create

	/**
	 * Update the object's fields in the database.
	 * @param array $form_data Associative array of new field values.
	 * @return int Affected rows.
	 * @throws InvalidArgumentException Thrown if primary key is set and contradicts this object's ID.
	 */
	public function update (array $form_data) {
		if ( array_key_exists(static::$primaryKeyName, $form_data) ) { // The new data has its primary key set.
			if ( $this->id != $form_data[static::$primaryKeyName] ) {
				throw new InvalidArgumentException("Cannot update record from data whose primary key (" .static::$primaryKeyName
					."={$form_data[static::$primaryKeyName]}) contradicts my ID ({$this->id}).");
			}
		}
		$this->record = null; // discard obsolete data
		$update = [];
		foreach ($form_data as $column_name => $value) {
			if (in_array($column_name, static::$columnNames)) {
				$update[$column_name] = $value;
			} else {
				trigger_error("Unknown column: $column_name", E_USER_WARNING);
			}
		}
		if (!$update) {
			trigger_error("Called update with no valid fields in the end.", E_USER_WARNING);
			return 0;
		}
		$where = [static::$primaryKeyName => $this->id];
		return DB::update(static::$tableName, $update, $where);
	} // update

	// Returns a row straight up from the database, unaltered.
	public function getRecord (array $fields = null): array {
		if (!$this->record) {
			$this->loadRecord();
		}
		if (!$fields) {
			return $this->record;
		}
		$filtered_record = [];
		foreach ($fields as $field_name) {
			$filtered_record[$field_name] = $this->record[$field_name];
		}
		return $filtered_record;
	} // getRecord

	// Returns the record's primary key value
	public function getId (): int {
		return $this->id;
	} // getId

	/**
	 * This method is intended to be called by the object's other methods. Do not make it public
	 * since it leads to bad practices. For example, the user should never be permitted to write:
	 * <code>
	 * if ($myObject->commonGet('status') == 2) {} // 2 = active
	 * </code>
	 * But should be forced to add a new method to the model:
	 * <code>
	 * if ($myObject->isActive()) {}
	 * </code>
	 * @param string $field_name
	 * @return mixed scalar
	 */
	protected function commonGet (string $field_name) {
		if (!$this->record) {
			$this->loadRecord();
		}
		return $this->record[$field_name];
	} // commonGet
} // BaseModel
