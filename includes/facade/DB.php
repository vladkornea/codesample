<?php
/**
 * @author Vladimir Kornea
 * @license http://www.kornea.com Please do not copy verbatim without attribution.
 * @uses PHP 7.1+ (void return types, nullable return types)
 * @uses PHP 7 (scalar type declarations, return type declarations)
 *
 * To use the DB class, set the environment variables referenced in `getConnection()`: DB_USER, DB_PASSWD, DB_NAME, DB_HOST
 *
 * <code>
 * $row = DB::getRow($query);
 *
 * $result = DB::query($query);
 * while ($row = DB::getRow($result)) {}
 *
 * $scalar = DB::getCell($query);
 *
 * $insert_id = DB::insert('table_name', $key_value_map);
 *
 * $key_value_map = [
 *     'my_column' => $_POST['my_field'],    // automatically escaped
 *     'inserted'  => DB::verbatim('now()')  // suppress auto-escaping
 * ];
 *
 * $affected_rows = DB::update('table_name', $key_value_map, $where);
 *
 * $where = ['email' => $_REQUEST['email']];                // array autoescaped by DB::update()
 * $where = 'email="' .DB::escape($_REQUEST['email']) .'"'; // strings require manual escaping
 * DB::where(['email' => $_REQUEST['email']]);              // get an escaped string from an array
 * </code>
 */

/** Interface DB_Interface lists the main public methods of the DB class. */
interface DB_Interface {
	static function escape (string $string): string;
	static function query ($query_or_queries);
	static function getRow ($query_or_result): ?array;
	static function getRowValues ($query_or_result): ?array;
	static function getColumnNames ($result_or_table): ?array;
	static function getCell ($query_or_result);
	static function getColumn ($query_or_result): ?array;
	static function getTable ($query_or_result): ?array;
	static function getKeyValueMap ($query_or_result): ?array;
	static function getIndexedTable ($query_or_result, string $index_field_name = null): ?array;
	static function insert (string $table_name, array $key_value_map): int;
	static function update (string $table_name, array $key_value_map, $where): int;
	static function select (string $table_name, $columns, $where = null): mysqli_result;
	static function verbatim (string $string): stdClass;
	static function datetime ($time_string_or_timestamp = 'now', string $component = 'datetime'): string;
	static function where (array $key_value_map): string;
	static function not ($var): stdClass;
	static function getNumRows ($query_or_result): int;
	static function getInsertId (string $query = null): int;
	static function getAffectedRows (string $query = null): int;
	static function getConnectionId (): int;
	static function getLastQuery (): ?string;
	static function log (bool $enable_logging = true): ?array;
	static function startTransaction (): void;
	static function endTransaction (bool $commit = true): void;
} // DB_Interface


class DB implements DB_Interface {
	/**
	 * @var string $lastQuery Retrieve by calling `DB::getLastQuery()`.
	 * @see DB::getLastQuery(), DB::query()
	 */
	protected static $lastQuery = '';


	/**
	 * @var array|null $log Call `DB::log()` to toggle logging and retrieve logs.
	 * @see DB::log(), DB::query()
	 */
	protected static $log = null;


	/**
	 * @var mysqli $connection The connection established by `static::getConnection()`.
	 * @see DB::getConnection()
	 * @link http://www.php.net/manual/en/class.mysqli.php
	 */
	protected static $connection = null;


	/**
	 * @return mysqli Connection object.
	 * @throws RuntimeException If cannot connect.
	 * @see DB::query()
	 * @link http://www.php.net/manual/en/class.mysqli.php
	 */
	protected static function getConnection (): mysqli {
		if (!static::$connection) {
			$host   = getenv('DB_HOST') ?: '127.0.0.1';
			$user   = getenv('DB_USER');
			$passwd = getenv('DB_PASSWD');
			$dbname = getenv('DB_NAME');
			/** @link http://www.php.net/manual/en/mysqli.construct.php **/
			static::$connection = @new mysqli($host, $user, $passwd, $dbname);
			if (static::$connection->connect_error) {
				throw new RuntimeException(static::$connection->connect_error, static::$connection->connect_errno);
			}
			static::$connection->set_charset('utf8mb4');
		}
		return static::$connection;
	} // getConnection


	/**
	 * Executes query and (for SELECT, SHOW, DESCRIBE, and EXPLAIN queries) returns result object to be passed to `DB::getRow()` etc.
	 * Multiple queries can be executed by passing an array of query strings, in which case the return value is null.
	 * <code>
	 * $result = DB::query($query);
	 * while ($row = DB::getRow($result)) {}
	 * </code>
	 * @param string|array $query_or_queries
	 * @return mysqli_result|null Result object or `null` for queries like `insert` and `update`.
	 * @throws TableDoesNotExistException If table does not exist.
	 * @throws RuntimeException If query fails for another reason such as syntax errors.
	 * @see DB::getRow(), DB::getCell(), DB::insert(), DB::update()
	 * @link http://www.php.net/manual/en/class.mysqli-result.php
	 * @link http://www.php.net/manual/en/mysqli.query.php
	 */
	public static function query ($query_or_queries): ?mysqli_result {
		if (is_array($query_or_queries)) {
			$queries = $query_or_queries;
			foreach ($queries as $query) {
				static::query($query);
			}
			return null;
		}
		$query = $query_or_queries;
		static::$lastQuery = $query;
		if (is_array(static::$log)) {
			static::$log[] = $query;
		}
		$result = static::getConnection()->query($query);
		if ($result === false) {
			$error_message = static::getConnection()->error ." in query::: $query";
			$error_code    = static::getConnection()->errno;
			if ($error_code == 1146) {
				throw new TableDoesNotExistException($error_message, $error_code);
			} else {
				throw new RuntimeException($error_message, $error_code);
			}
		}
		if ($result === true) {
			return null;
		}
		return $result;
	} // query


	/**
	 * Returns a DB row as an associative array.
	 * <code>
	 * $row = DB::getRow($query);
	 * // or
	 * $result = DB::query($query);
	 * while ($row = DB::getRow($result)) {}
	 * </code>
	 * @param string|mysqli_result $query_or_result
	 * @return array DB row as an associative array.
	 * @see DB::getRowValues(), DB::query(), DB::getColumn(), DB::getCell(), DB::getTable()
	 * @link http://www.php.net/manual/en/mysqli-result.fetch-assoc.php
	 */
	public static function getRow ($query_or_result): ?array {
		$query = is_string($query_or_result) ? $query_or_result : null;
		$result = $query ? static::query($query) : $query_or_result;
		$row = $result->fetch_assoc();
		if ($query) {
			$result->free();
		}
		return $row;
	} // getRow


	/**
	 * Same as `DB::getRow()`, except the values aren't keyed by column names.
	 * <code>
	 * $result = DB::query($query);
	 * while ($csv_fields = DB::getRowValues($result)) {
	 *     fputcsv($handle, $csv_fields);
	 * }
	 * </code>
	 * @param string|mysqli_result $query_or_result
	 * @return array DB row as an enumerated array.
	 * @see DB::getRow()
	 * @link http://us1.php.net/manual/en/mysqli-result.fetch-row.php
	 */
	public static function getRowValues ($query_or_result): ?array {
		$query = is_string($query_or_result) ? $query_or_result : null;
		$result = $query ? static::query($query) : $query_or_result;
		$row_values = $result->fetch_row();
		if ($query) {
			$result->free();
		}
		return $row_values;
	} // getRowValues


	/**
	 * For queries that return a single value like counts and yes/no queries.
	 * <code>
	 * $query = 'select count(*) from users where company_id = 20';
	 * $company_user_count = DB::getCell($query);
	 * </code>
	 * Note that a query that returns a `null` value is indistinguishable from one
	 * that returns no rows; therefore a pattern like this is possible:
	 * <code>
	 * $query = 'select might_be_null from users where email = "someone@example.com"';
	 * $result = DB::query($query);
	 * if (0 == DB::getNumRows($result)) {
	 *     die("No such email address.");
	 * }
	 * $might_be_null = DB::getCell($result);
	 * </code>
	 * @param string|mysqli_result $query_or_result
	 * @return mixed Scalar value of first field in the result.
	 * @see DB::getRow(), DB::getColumn(), DB::getTable()
	 */
	public static function getCell ($query_or_result) {
		$query = is_string($query_or_result) ? $query_or_result : null;
		$result = $query ? static::query($query) : $query_or_result;
		$row = $result->fetch_row();
		$scalar = $row ? $row[0] : null;
		if ($query) {
			$result->free();
		}
		return $scalar;
	} // getCell


	/**
	 * Get a single column's values. Useful for selecting lists of IDs, values to implode(), etc.
	 * <code>
	 * $company_users = DB::getColumn('select user_id from users where company_id = 20');
	 * </code>
	 * @param string|mysqli_result $query_or_result
	 * @return array First column's values.
	 * @see DB::getKeyValueMap(), DB::getRow(), DB::getCell(), DB::getTable()
	 */
	public static function getColumn ($query_or_result): ?array {
		$column = []; // return value
		$query = is_string($query_or_result) ? $query_or_result : null;
		$result = $query ? static::query($query) : $query_or_result;
		while ($row = $result->fetch_row()) {
			$column[] = $row[0];
		}
		if ($query) {
			$result->free();
		}
		return $column;
	} // getColumn


	/**
	 * Use `query()` and `getRow()` instead whenever possible (memory efficiency).
	 * Useful for fetching data to pass to parts of the system that can't work with
	 * MySQL result objects, such as AJAX scripts.
	 * @param string|mysqli_result $query_or_result
	 * @return array Entire result set as array of rows.
	 * @see DB::getIndexedTable(), DB::getRow()
	 */
	public static function getTable ($query_or_result): ?array {
		$table = []; // return value
		$query = is_string($query_or_result) ? $query_or_result : null;
		$result = $query ? static::query($query) : $query_or_result;
		while ($row = $result->fetch_assoc()) {
			$table[] = $row;
		}
		if ($query) {
			$result->free();
		}
		return $table;
	} // getTable


	/**
	 * Accepts a query that selects exactly two columns and returns an associative
	 * array of the second column's values indexed by the first column's values,
	 * same format as `$_GET` and as expected by `http_build_query()`.
	 * <code>
	 * $cc = DB::getKeyValueMap('select email, name from users where company_id = 20')
	 * </code>
	 * @param string|mysqli_result $query_or_result Should select two columns.
	 * @return array Second column's values indexed by first column's values.
	 * @see DB::getColumn(), DB::getIndexedTable()
	 */
	public static function getKeyValueMap ($query_or_result): ?array {
		$key_value_map = []; // return value
		$query = is_string($query_or_result) ? $query_or_result : null;
		$result = $query ? static::query($query) : $query_or_result;
		while ($row = $result->fetch_row()) {
			$key_value_map[$row[0]] = $row[1];
		}
		if ($query) {
			$result->free();
		}
		return $key_value_map;
	} // getKeyValueMap


	/**
	 * Returns rows indexed by the first column (which is excluded from the rows). Useful for selecting rows indexed by
	 * their IDs. Specify the optional $index_field_name parameter to index by a field other than the first column.
	 * @param string|mysqli_result $query_or_result
	 * @param string|null $index_field_name optional default first column
	 * @return array
	 * @see DB::getTable(), DB::getKeyValueMap()
	 */
	public static function getIndexedTable ($query_or_result, string $index_field_name = null): ?array {
		$indexed_table = []; // return value
		$query = is_string($query_or_result) ? $query_or_result : null;
		$result = $query ? static::query($query) : $query_or_result;
		if (!$index_field_name) {
			$index_field_name = static::getColumnNames($result)[0];
		}
		while ($row = $result->fetch_assoc()) {
			$index_value = $row[$index_field_name];
			unset($row[$index_field_name]);
			$indexed_table[$index_value] = $row;
		}
		if ($query) {
			$result->free();
		}
		return $indexed_table;
	} // getIndexedTable


	/**
	 * Returns same thing as `array_keys(DB::getRow($result))`, but without having to actually
	 * fetch a row and forward the internal result pointer. Useful when quickly printing HTML
	 * table header rows of arbitrary queries during debugging. Can also be a table name to get
	 * a list of its columns. It makes no sense for this method to accept queries.
	 * <code>
	 * foreach (DB::getColumnNames($result) as $field_name) {
	 *     echo "<th>$field_name</th>";
	 * }
	 * </code>
	 * @param mysqli_result|string $result_or_table
	 * @return array
	 * @link http://www.php.net//manual/en/mysqli-result.fetch-field-direct.php
	 */
	public static function getColumnNames ($result_or_table): ?array {
		if (is_string($result_or_table)) {
			return static::getColumn("show columns from `" . static::escape($result_or_table) . "`");
		} else {
			$result = $result_or_table;
			$column_names = [];
			for ($i = 0; $i < $result->field_count; $i++) {
				$column_names[] = $result->fetch_field_direct($i)->name;
			}
			return $column_names;
		}
	} // getColumnNames


	/**
	 * Secures a string from SQL injection attacks.
	 * `DB::insert()` and `DB::update()` escape arrays automatically.
	 * If you are writing a `SELECT` query that contains no `OR` or `LIKE` conditions,
	 * you can pass your array of conditions to `DB::where()` in order to get
	 * an auto-escaped string; otherwise you must remember to escape manually.
	 * <code>
	 * $query = 'select user_id from users where email="' .DB::escape($_POST['email']) .'"';
	 * </code>
	 * @param string $string
	 * @return string SQL-escaped string.
	 * @see DB::verbatim()
	 * @link http://www.php.net/manual/en/mysqli.real-escape-string.php
	 */
	public static function escape (string $string): string {
		return static::getConnection()->real_escape_string($string);
	} // escape


	/**
	 * Wraps your string into an object that keeps it from being auto-escaped by
	 * methods like `insert()` and `update()`.
	 * <code>
	 * $new_row['inserted'] = DB::verbatim('now()');
	 * </code>
	 * @param string $string Typically 'now()' or such.
	 * @return stdClass Special object that suppresses autoescaping for your string.
	 * @throws InvalidArgumentException If argument is not a string.
	 * @see DB::insert(), DB::update(), DB::escape(), DB::datetime()
	 */
	public static function verbatim (string $string): stdClass {
		if (!is_string($string)) {
			throw new InvalidArgumentException("Invalid argument type: " .gettype($string));
		}
		return (object)['verbatimString' => $string];
	} // verbatim


	/**
	 * <code>
	 * DB::datetime() === date('Y-m-d H:i:s');
	 * DB::datetime($timestamp) === date('Y-m-d H:i:s', $timestamp);
	 * DB::datetime('-6 months') === date('Y-m-d H:i:s', strtotime('-6 months'));
	 * DB::datetime('now', 'date') === date('Y-m-d');
	 * DB::datetime('now', 'time') === date('H:i:s');
	 * </code>
	 * @param string|int $time_string_or_timestamp A string understood by strtotime() (defaults to 'now'), or a unix timestamp.
	 * @param string $component 'datetime', 'date', 'time'
	 * @return string MySQL datetime, or date or time depending on $component
	 */
	public static function datetime ($time_string_or_timestamp = 'now', string $component = 'datetime'): string {
		$timestamp = is_numeric($time_string_or_timestamp) ? $time_string_or_timestamp : strtotime($time_string_or_timestamp);
		$format = ['datetime'=>'Y-m-d H:i:s', 'date'=>'Y-m-d', 'time'=>'H:i:s'][$component];
		return date($format, $timestamp);
	} // datetime


	/**
	 * PHP special values like `null`, `true`, and `false` return corresponding MySQL keywords.
	 * Strings get escaped and enclosed in quotes. Arrays become escaped comma-separated strings (sets).
	 * Integers and floats remain unaltered. This method understands `DB::verbatim()`, and methods
	 * like `insert()` and `update()` rely on it.
	 * @param string|int|float|object|null|bool $php_value
	 * @return string|int|float
	 * @throws InvalidArgumentException If argument type is unrecognized.
	 * @see DB::verbatim(), DB::getSetClause(), DB::where(), DB::getInClause()
	 */
	public static function getSqlValue ($php_value) {
		if ( is_string($php_value) ) {
			return '"' .static::escape($php_value) .'"';
		}
		if ( is_int($php_value) or is_float($php_value) ) {
			return $php_value;
		}
		if ($php_value === null) {
			return 'null';
		}
		if ($php_value === true) {
			return 'true';
		}
		if ($php_value === false) {
			return 'false';
		}
		if ( is_object($php_value) ) {
			$specialObject = $php_value;
			if (!empty($specialObject->verbatimString)) { /* @see DB::verbatim() */
				return $specialObject->verbatimString;
			}
		}
		if ( is_array($php_value) ) {
			$escaped_set_values = [];
			foreach ($php_value as $set_value) {
				$escaped_set_values[] = static::escape($set_value);
			}
			$escaped_set_values_string = '"' .implode(',', $escaped_set_values) .'"';
			return $escaped_set_values_string;
		}
		throw new InvalidArgumentException("Unexpected argument type: " .gettype($php_value));
	} // getSqlValue


	/**
	 * Creates a `SET` clause string from an associative array. Escapes values automatically,
	 * except for those that have been passed through `DB::verbatim()`.
	 * Used by `DB::insert()` and `DB::update()`.
	 * @param array $key_value_map
	 * @return string Set clause without the word `SET`.
	 * @see DB::insert(), DB::update(), DB::verbatim()
	 */
	public static function getSetClause (array $key_value_map): string {
		$set = [];
		foreach ($key_value_map as $column_name => $field_value) {
			$set[] = static::escape($column_name) .' = ' .static::getSqlValue($field_value);
		}
		$set = implode("\n\t,", $set);
		return $set;
	} // getSetClause


	/**
	 * Inserts row and returns its ID. Escapes values automatically except for those that
	 * have been passed through `DB::verbatim()`. It is possible to insert multiple rows,
	 * by passing an array of rows, such as returned by `DB::getTable()`. In this case the
	 * returned insert ID is that of the first row inserted. Since all rows are inserted
	 * using a single query, the subsequent rows are guaranteed to have sequential insert
	 * IDs (note that all rows must define the same columns, in the same order).
	 * @param string $table_name
	 * @param array $key_value_map
	 * @param bool $replace_duplicates
	 * @return int Insert ID.
	 * @see DB::verbatim(), DB::update()
	 */
	public static function insert (string $table_name, array $key_value_map, $replace_duplicates = false): int {
		$command = $replace_duplicates ? 'replace' : 'insert';
		$has_multiple_rows = isset($key_value_map[0]);
		if ($has_multiple_rows) {
			$escaped_column_names = [];
			foreach (array_keys($key_value_map[0]) as $loop_column) {
				$escaped_column_names[] = static::escape($loop_column);
			}
			$escaped_column_names = implode(', ', $escaped_column_names);
			$escaped_values_lists = [];
			foreach ($key_value_map as $loop_row) {
				$escaped_values_list = [];
				foreach ($loop_row as $loop_field_value) {
					$escaped_values_list[] = static::getSqlValue($loop_field_value);
				}
				$escaped_values_list = implode("\n\t,", $escaped_values_list);
				$escaped_values_lists[] = "($escaped_values_list)";
			}
			$escaped_values_lists = implode(', ', $escaped_values_lists);
			$query = "$command into " .static::escape($table_name) ." ($escaped_column_names) values $escaped_values_lists";
		} else {
			$query = "$command into " .static::escape($table_name) .' set ' .static::getSetClause($key_value_map);
		}
		return static::getInsertId($query);
	} // insert


	/**
	 * Returns the ID of the row which was inserted by the previous query.
	 * `DB::insert()` returns this automatically.
	 * If the optional $query argument is provided, the query will be executed first.
	 * @param null|string $query optional default null
	 * @return int "ID generated by a query on a table with a column having the AUTO_INCREMENT attribute. (php.net)"
	 * @link http://www.php.net/manual/en/mysqli.insert-id.php
	 */
	public static function getInsertId (string $query = null): int {
		if ($query) {
			static::query($query);
		}
		return (int)static::getConnection()->insert_id;
	} // getInsertId


	/**
	 * @param array $values
	 * @return string `IN` clause without the word `in`.
	 * @see DB::verbatim()
	 */
	public static function getInClause (array $values): string {
		$in = [];
		foreach ($values as $loop_value) {
			$in[] = static::getSqlValue($loop_value);
		}
		$in = implode(', ', $in);
		return $in;
	} // getInClause


	/**
	 * Most `WHERE` clauses do not contain `LIKE` or `OR` conditions but merely a number of `AND`
	 * conditions. Such `WHERE` clauses can be expressed as associative arrays and passed through
	 * this method to get the corresponding `WHERE` clause string (without the word `WHERE`). This
	 * method escapes values automatically unless they have been passed through `DB::verbatim()`.
	 * This method permits `DB::update()` to accept an array as its `WHERE` clause; it can also be
	 * used as a generic helper when constructing queries. Arrays are treated as `IN` clauses.
	 * Null values evaluate to `is null`, but if you need `is not null`, use `DB::not(null)`.
	 * <code>
	 * $where = ['column' => $_GET['field']];
	 * $query = 'select * from table where ' .DB::where($where);
	 * </code>
	 * @param array $key_value_map
	 * @return string MySQL where clause without the word `WHERE`.
	 * @see DB::update(), DB::verbatim()
	 */
	public static function where (array $key_value_map): string {
		$where = [];
		foreach ($key_value_map as $column_name => $field_value) {
			if ($field_value === null) {
				$where[] = static::escape($column_name) .' is null';
				continue;
			}
			if (is_array($field_value)) {
				$where[] = static::escape($column_name) .' in (' .static::getInClause($field_value) .')';
				continue;
			}
			if (is_object($field_value)) {
				$specialObject = $field_value;
				if (!empty($specialObject->notValueIsSet)) {
					$not_value = $specialObject->notValue;
					if ($not_value === null) {
						$where[] = static::escape($column_name) .' is not null';
					} elseif (is_array($not_value)) {
						if ($not_value) {
							$where[] = static::escape($column_name) .' not in (' .static::getInClause($not_value) .')';
						}
					} else {
						$where[] = static::escape($column_name) .' != ' .static::getSqlValue($not_value);
					}
				}
				if (!empty($specialObject->verbatimString)) {
					$where[] = static::escape($column_name) .' = ' .$specialObject->verbatimString;
				}
				continue;
			}
			$where[] = static::escape($column_name) .' = ' .static::getSqlValue($field_value);
		} // foreach $key_value_map
		$where = implode("\n\tand ", $where);
		return $where;
	} // where


	/**
	 * <code>
	 * $where = DB::where([
	 *     'column_a'  => DB::not('my_string')       // !=
	 *     ,'column_b' => DB::not(null)              // is not null
	 *     ,'column_c' => DB::not(['this', 'that'])  // not in
	 * ]);
	 * // $where now looks like:
	 * // "column_a != 'my_string' and column_b is not null and column_c not in('this', 'that')"
	 * </code>
	 * @param mixed $var
	 * @return stdClass
	 */
	public static function not ($var): stdClass {
		return (object)['notValue' => $var, 'notValueIsSet' => true];
	} // not


	/**
	 * Values will be escaped automatically except for those passed through `DB::verbatim()`.
	 * If the `WHERE` clause is an array, it will be autoescaped the same way;
	 * but if it's a string, remember to perform your own escaping.
	 * <code>
	 * $update = [
	 *   'column_name' => $_POST['field_name'], // automatically escaped
	 *   'updated'     => DB::verbatim('now()') // suppress auto-escaping
	 * ];
	 * $where = ['column_name' => $_POST['field_name']];                // autoescaped
	 * $where = 'column_name="' .DB::escape($_POST['field_name']) .'"'; // also possible
	 * $affected_rows = DB::update('users', $update, $where);
	 * </code>
	 * @param string $table_name
	 * @param array $key_value_map
	 * @param string|array $where
	 * @return int Number of rows affected by the update query.
	 * @see DB::verbatim(), DB::insert()
	 */
	public static function update (string $table_name, array $key_value_map, $where): int {
		if (is_array($where)) {
			$where = static::where($where);
		}
		$query = 'update `' .static::escape($table_name) .'` set ' .static::getSetClause($key_value_map) ."\nwhere $where";
		return static::getAffectedRows($query);
	} // update


	/**
	 * In the model library we often have table name, columns, and condition as variables. We could
	 * manually construct query strings from variables and pass the strings to DB::query(). This
	 * method is a shortcut way of doing the same thing; it cannot handle complex queries.
	 * @param string $table_name
	 * @param array|string $columns
	 * @param array|string $where
	 * @return mysqli_result
	 */
	public static function select (string $table_name, $columns, $where = null): mysqli_result {
		if (is_string($columns)) {
			$columns = [$columns];
		}
		$columns = implode(', ', $columns);
		$query = "select $columns from $table_name";
		if (is_array($where)) {
			$where = static::where($where);
		}
		if ($where) {
			$query = "$query where $where";
		}
		$result = static::query($query);
		return $result;
	} // select


	/**
	 * Returns the number of rows affected by the last `INSERT`, `UPDATE`, `REPLACE` or
	 * `DELETE` query. `DB::update()` returns this number automatically. If the optional
	 * `$query` argument is provided, the query will be executed first.
	 * <code>
	 * DB::query($query);
	 * $affected_rows = DB::getAffectedRows();
	 * // or
	 * $affected_rows = DB::getAffectedRows($query);
	 * </code>
	 * @param null|string $query optional
	 * @return int "number of rows affected by the last INSERT, UPDATE, REPLACE or DELETE query (php.net)"
	 * @link http://www.php.net/manual/en/mysqli.affected-rows.php
	 */
	public static function getAffectedRows (string $query = null): int {
		if ($query) {
			static::query($query);
		}
		return static::getConnection()->affected_rows;
	} // getAffectedRows


	/**
	 * Gets number of rows within provided result object or returned by provided query.
	 * Accepting query strings permits this method to be used for getting yes/no answers from the database.
	 * <code>
	 * $result = DB::query($query);
	 * $num_rows = DB::getNumRows($result);
	 * // or
	 * $num_rows = DB::getNumRows($query);
	 * </code>
	 * @param mysqli_result|string $query_or_result Such as returned by DB::query().
	 * @return int Number of rows in result.
	 * @link http://www.php.net//manual/en/mysqli-result.num-rows.php
	 */
	public static function getNumRows ($query_or_result): int {
		$query = is_string($query_or_result) ? $query_or_result : null;
		$result = $query ? static::query($query) : $query_or_result;
		$num_rows = $result->num_rows;
		if ($query) {
			$result->free();
		}
		return $num_rows;
	} // getNumRows


	/**
	 * Useful for locking tables.
	 * @return int
	 */
	public static function getConnectionId (): int {
		return (int)static::getCell('select connection_id()');
	} // getConnectionId


	/**
	 * This includes queries executed by methods such as `DB::insert()` and `DB::update()`.
	 * @return string The last SQL query executed by the DB class.
	 * @see DB::query(), static::$lastQuery
	 */
	public static function getLastQuery (): ?string {
		return static::$lastQuery;
	} // getLastQuery


	/**
	 * Empties the error log and returns its contents, and either enables or disables logging.
	 * <code>
	 * DB::log();
	 * //etc
	 * $log = DB::log(false);
	 * </code>
	 * @param boolean $enable_logging Whether to enable logging.
	 * @return array|null Queries logged so far.
	 * @see DB::$log, DB::query()
	 */
	public static function log (bool $enable_logging = true): ?array {
		$query_log = static::$log;
		static::$log = $enable_logging ? [] : null;
		return $query_log;
	} // log


	public static function startTransaction (): void {
		static::getConnection()->autocommit(false);
	} // startTransaction


	/** @param bool $commit true (default) to commit, false to do a rollback **/
	public static function endTransaction (bool $commit = true): void {
		if ($commit) {
			static::getConnection()->commit();
		} else {
			static::getConnection()->rollback();
		}
		static::getConnection()->autocommit(true);
	} // endTransaction


	/**
	 * Runs various queries, see if something crashes.
	 * @return array queries
	 * @throws Exception
	 */
	public static function test (): array {
		DB::log();

		DB::query('
			create temporary table dbtest (
				dbtest_id   integer unsigned primary key auto_increment,
				some_id     integer unsigned,
				some_string varchar(255),
				some_flag   boolean,
				some_etc    varchar(255) null,
				updated     timestamp null default null on update current_timestamp,
				inserted    timestamp default current_timestamp
			)'
		);

		$insert = [
			'some_id'     => 20,
			'some_string' => 'test string',
			'some_flag'   => true,
			'some_etc'    => null,
			'inserted'    => DB::datetime(),
		];
		$dbtest_id = DB::insert('dbtest', $insert);
		if ($dbtest_id != 1) {
			throw new Exception("Invalid \$dbtest_id: $dbtest_id.");
		}

		$count = DB::getCell('select count(*) from dbtest');
		if ($count != 1) {
			throw new Exception("Invalid \$count: $count.");
		}

		$update = ['some_string' => 'updated test string'];
		$where = ['dbtest_id' => $dbtest_id];
		$affected_rows = DB::update('dbtest', $update, $where);
		if ($affected_rows != 1) {
			throw new Exception("Invalid \$affected_rows: $affected_rows.");
		}

		$query = 'select * from dbtest where dbtest_id = "' .DB::escape($dbtest_id) .'"';
		$row = DB::getRow($query);
		if (!is_array($row) or empty($row)) {
			throw new Exception("Invalid \$row: $row.");
		}

		$row_values = DB::getRowValues($query);
		if (!is_array($row_values) or empty($row_values)) {
			throw new Exception("Invalid \$row_values: $row_values.");
		}

		$query = 'select * from dbtest';
		$result = DB::query($query);
		while ( $row = DB::getRow($result) ) {
			break;
		}

		DB::query('drop temporary table dbtest');

		return DB::log(false);
	} // test
} // DB

class TableDoesNotExistException extends RuntimeException {
	/* This exists to differentiate this exception type, since some code knows how to handle it. */
} // TableDoesNotExistException

