<?php

trait DbUpgradeModelTraits {
	protected static $tableName      = 'db_upgrades';
	protected static $primaryKeyName = 'db_upgrade_id';
	protected static $columnNames    = ['db_upgrade_id', 'upgrade_method', 'query', 'inserted'];
} // DbUpgradeModelTraits

trait HistoricEventTraits {
	protected static $tableName      = 'historic_events';
	protected static $primaryKeyName = 'historic_event_id';
	protected static $columnNames    = ['historic_event_id', 'event_synopsis', 'table_name', 'entity_id', 'sql_query', 'connection_id', 'inserted'];
} //  HistoricEventTraits

trait UserTraits {
	protected static $tableName      = 'users';
	protected static $primaryKeyName = 'user_id';
	protected static $columnNames    = ['user_id', 'username', 'verified_email', 'unverified_email', 'email_bouncing', 'spammer', 'last_visit', 'verification_code', 'password_hash', 'mbti_type', 'gender', 'orientation', 'birth_date', 'body_type', 'height_in_in', 'weight_in_kg', 'country', 'city', 'state', 'zip_code', 'latitude', 'longitude', 'share_keywords', 'self_described', 'lover_described', 'virtrades', 'have_children', 'want_children', 'would_relocate', 'photo_order', 'primary_thumbnail_width', 'primary_thumbnail_height', 'deactivated', 'deleted_by_admin', 'admin_note', 'updated', 'inserted'];
} //  UserTraits

trait SearchCriteriaTraits {
	protected static $tableName      = 'search_criteria';
	protected static $primaryKeyName = 'search_criteria_id';
	protected static $columnNames    = ['search_criteria_id', 'user_id', 'country', 'max_distance', 'exclude_contacted', 'gender', 'match_shared_negatives', 'mbti_types', 'min_age', 'max_age', 'must_have_description', 'must_have_picture', 'must_like_my_gender', 'newer_than_days', 'logged_in_within_days', 'updated', 'inserted'];
} // SearchCriteriaTraits

trait PhotoTraits {
	protected static $tableName      = 'photos';
	protected static $primaryKeyName = 'photo_id';
	protected static $columnNames    = ['photo_id','user_id','caption','thumbnail_width','thumbnail_height','standard_width','standard_height','original_width','original_height','deleted','updated','inserted'];
} // PhotoTraits

trait UserMessageTraits {
	protected static $tableName      = 'user_messages';
	protected static $primaryKeyName = 'user_message_id';
	protected static $columnNames    = ['user_message_id', 'from_user_id', 'to_user_id', 'message_text', 'deleted', 'inserted'];
} // UserMessageTraits

trait LoginTraits {
	protected static $tableName      = 'logins';
	protected static $primaryKeyName = 'login_id';
	protected static $columnNames    = ['login_id', 'user_id', 'login_method', 'login_timestamp', 'logout_timestamp', 'cookie_password', 'user_agent', 'ip_address', 'screen_width', 'screen_height', 'color_depth', 'window_width', 'window_height', 'utc_offset', 'http_referer', 'server_protocol', 'http_host', 'request_uri', 'script_filename'];
} //  LoginTraits

trait GlobalSettingTraits {
	protected static $tableName      = 'global_settings';
	protected static $primaryKeyName = 'global_setting_id';
	protected static $columnNames    = ['global_setting_id', 'setting_name', 'setting_value', 'updated', 'inserted'];
} // GlobalSettingTraits

trait SqsMessageTraits {
	protected static $tableName      = 'sqs_messages';
	protected static $primaryKeyName = 'sqs_message_id';
	protected static $columnNames    = ['sqs_message_id', 'is_deleted', 'message_id', 'receipt_handle', 'md5_of_body', 'body', 'raw_message', 'updated', 'inserted'];
} //  SqsMessageTraits

trait EmailTraits {
	protected static $tableName      = 'emails';
	protected static $primaryKeyName = 'email_id';
	protected static $columnNames    = ['email_id', 'status', 'to_email', 'from_string', 'subject', 'message_id', 'request_id', 'raw_source', 'updated', 'inserted'];
} //  EmailTraits

trait CountryTraits {
	protected static $tableName      = 'countries';
	protected static $primaryKeyName = 'country_id';
	protected static $columnNames    = ['country_id', 'country_code', 'country_name', 'inserted'];
} // CountryTraits

trait UsaStateTraits {
	protected static $tableName      = 'usa_states';
	protected static $primaryKeyName = 'usa_state_id';
	protected static $columnNames    = ['usa_state_id', 'state_code', 'state_name', 'inserted'];
} // UsaStateTraits

