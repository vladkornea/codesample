<?php

require_once 'BaseModel.php';

interface LoggedModelInterface extends BaseModelInterface {
	static function create (array $form_data, string $event_synopsis = '');
	function update (array $form_data, string $event_synopsis = '');
} // LoggedModelInterface

/**
 * LoggedModel is an optional layer between BaseModel and the final model classes.
 * Extend LoggedModel rather than BaseModel to cause all updates to automatically
 * be logged in the `historic_events` table.
 */
abstract class LoggedModel extends BaseModel implements LoggedModelInterface {
	/**
	 * @param array $form_data
	 * @param string $event_synopsis Pass something to log this query in `historic_events`.
	 * @return number affected rows
	 */
	public function update (array $form_data, string $event_synopsis = '') {
		$affected_rows = parent::update($form_data);
		if ($affected_rows and $event_synopsis !== false) {
			$query_executed = DB::getLastQuery();
			if (!$event_synopsis) {
				$event_synopsis = get_called_class() .'::' .__FUNCTION__ ." updated";
			}
			$historic_event_row = [
				'event_synopsis' => $event_synopsis
				,'entity_id'     => $this->getId()
				,'table_name'    => static::$tableName
				,'sql_query'     => $query_executed
			];
			HistoricEventModel::create($historic_event_row);
		}
		return $affected_rows;
	} // update


	public static function create (array $form_data, string $event_synopsis = '', bool $log_query = true) {
		$insert_id = parent::create($form_data);
		$query_executed = DB::getLastQuery();
		if (!$event_synopsis) {
			$event_synopsis = get_called_class() . '::' .__FUNCTION__ ."()";
		}
		$historic_event_row = [
			'event_synopsis' => $event_synopsis
			,'entity_id'     => $insert_id
			,'table_name'    => static::$tableName
			,'sql_query'     => $log_query ? $query_executed : null
		];
		HistoricEventModel::create($historic_event_row);
		return $insert_id;
	} // create
} // LoggedModel

