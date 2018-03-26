<?php

interface GlobalSettingsInterface {
	static function setSetting (string $setting_name, $setting_value): void;
	static function getSetting (string $setting_name): ?string;
	static function setSettings (array $new_settings): void;
	static function getSettings (array $settings = null): array;
} // GlobalSettingsInterface

class GlobalSettings implements GlobalSettingsInterface {
	use GlobalSettingTraits;

	const RECEIVED_QUEUE_PROCESSING = 'process_received_queue';
	const SENT_QUEUE_PROCESSING     = 'process_sent_queue';
	const QUEUED_EMAIL_SENDING      = 'queued_email_sending';

	static $validSettings = [self::SENT_QUEUE_PROCESSING, self::RECEIVED_QUEUE_PROCESSING, self::QUEUED_EMAIL_SENDING];

	public static function feedbackQueueProcessing (bool $whether = null): bool {
		if ($whether !== null) {
			static::setSetting(self::SENT_QUEUE_PROCESSING, $whether);
		}
		return (bool)static::getSetting(self::SENT_QUEUE_PROCESSING);
	} // feedbackQueueProcessing


	public static function inboundQueueProcessing (bool $whether = null): bool {
		if ($whether !== null) {
			static::setSetting(self::RECEIVED_QUEUE_PROCESSING, $whether);
		}
		return (bool)static::getSetting(self::RECEIVED_QUEUE_PROCESSING);
	} // inboundQueueProcessing


	public static function queuedEmailSending (bool $whether = null): bool {
		if ($whether !== null) {
			static::setSetting(self::QUEUED_EMAIL_SENDING, $whether);
		}
		return (bool)static::getSetting(self::QUEUED_EMAIL_SENDING);
	} // queuedEmailSending


	public static function getSettings (array $settings = null): array {
		$where = $settings ?
			"setting_name in (" .DB::getInClause($settings) .')'
			: null;
		$result = DB::select(static::$tableName, ['setting_name', 'setting_value'], $where);
		$all_settings = DB::getKeyValueMap($result);
		return $all_settings;
	} // getSettings


	public static function getSetting (string $setting_name): ?string {
		$settings = static::getSettings([$setting_name]);
		$setting_value = $settings[$setting_name] ?? null;
		return $setting_value;
	} // getSetting


	/**
	 * false -> '0', true -> '1', null -> ''
	 * @param string $setting_name
	 * @param mixed $setting_value
	 */
	public static function setSetting (string $setting_name, $setting_value): void {
		if (is_bool($setting_value)) { // values are strings, and we don't want "true" and "false" as string literals
			$setting_value = (int)$setting_value;
		}
		if ($setting_value === null) {
			$setting_value = '';
		}
		$original_setting_value = static::getSetting($setting_name);
		if ($original_setting_value === null) {
			$insert_id = DB::insert(static::$tableName, ['setting_name' => $setting_name, 'setting_value' => $setting_value, 'inserted' => DB::verbatim('now()')]);
			$last_query = DB::getLastQuery();
			$event_synopsis = "Changed global setting $setting_name: $setting_value";
			HistoricEventModel::create(['event_synopsis' => $event_synopsis, 'sql_query' => $last_query, 'table_name' => static::$tableName, 'entity_id' => $insert_id]);
		} else {
			$affected_rows = DB::update(static::$tableName, ['setting_value' => $setting_value], ['setting_name' => $setting_name]);
			if ($affected_rows) {
				$last_query = DB::getLastQuery();
				$event_synopsis = "Changed global setting $setting_name: $setting_value";
				HistoricEventModel::create(['event_synopsis' => $event_synopsis, 'sql_query' => $last_query, 'table_name' => static::$tableName]);
			}
		}
	} // setSetting


	public static function setSettings (array $new_settings): void {
		foreach ($new_settings as $setting_name => $new_setting_value) {
			$is_setting_name_valid = in_array($setting_name, static::$validSettings);
			if (!$is_setting_name_valid) {
				trigger_error("Invalid setting name: $setting_name", E_USER_WARNING);
				continue;
			}
			static::setSetting($setting_name, $new_setting_value);
		}
	} // setSettings


	public static function insertDefaultSettings (): void {
		static::inboundQueueProcessing(false);
		static::feedbackQueueProcessing(false);
		static::queuedEmailSending(false);
	} // insertDefaultSettings
} // GlobalSettings

