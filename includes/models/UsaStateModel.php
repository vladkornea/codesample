<?php

class UsaStateModel extends BaseModel {
	use UsaStateTraits;

	public static function create (array $form_data): array {
		$usa_state_id = parent::create($form_data);
		return ['usa_state_id' => $usa_state_id, 'error_messages' => null];
	} // create

	public function update (array $form_data): ?array {
		parent::update($form_data);
		return null;
	} // update
} // UsaStateModel

