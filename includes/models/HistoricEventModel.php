<?php

require_once 'BaseModel.php';

interface HistoricEventModelInterface extends BaseModelInterface {
} // HistoricEventModelInterface

class HistoricEventModel extends BaseModel implements HistoricEventModelInterface {
	use HistoricEventTraits;

	public static function create (array $form_data) {
		if (!array_key_exists('connection_id', $form_data)) {
			$form_data['connection_id'] = DB::verbatim('connection_id()');
		}
		if (!empty($form_data['event_synopsis'])) {
			error_log($form_data['event_synopsis']);
		}
		return parent::create($form_data);
	} // create
} // HistoricEventModel

