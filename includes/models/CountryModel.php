<?php

require_once 'BaseModel.php';

class CountryModel extends BaseModel {
	use CountryTraits;

	public static function create (array $form_data): array {
		$country_id = parent::create($form_data);
		return ['country_id' => $country_id, 'error_messages' => null];
	} // create

	public function update (array $form_data): ?array {
		parent::update($form_data);
		return null;
	} // update
} // CountryModel

