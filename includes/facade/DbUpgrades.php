<?php
/**
 * Hopefully every query that alters the database is performed using this class like so:
 * 1. Append to this class a new method whose name concisely summarizes what change it makes to the database.
 * 2. Add the name of said method to the `DbUpgrades::$upgradeMethods` array.
 * 3. Call `DbUpgrades::performUpgrades()` to run all upgrade methods that have not already been run.
 *    A list of those already run is kept in the `db_upgrades` table.
 */
interface DbUpgradesInterface {
	static function performUpgrades (): void;
	static function getUpgradesSummary (): array;
	static function isInstalled (): bool;
} // DbUpgradesInterface

class DbUpgrades implements DbUpgradesInterface {
	protected static $upgradeMethods = [
//		'createDbUpgradesTable',
//		'createHistoricEventsTable',
//		'createUsersTable',
//		'createLoginsTable',
//		'createGlobalSettingsTable',
//		'createSqsMessagesTable',
//		'createEmailsTable',
//		'createZipCodeCoordinatesTable',
//		'createSearchCriteriaTable',
//		'createPhotosTable',
//		'createNegativeKeywordsTable',
//		'createPositiveKeywordsTable',
//		'createUserMessagesTable',
//		'createBlockedUsersTable',
//		'createReportedUsersTable',
//		'makeUtcOffsetSigned',
//		'addAdminNoteColumn',
//		'addDeletedByAdminColumn',
//		'changeLastVisitColumnTypeToTimestamp',
//		'increaseSizeOfSqlQueryColumnInHistoricEventsTable',
//		'dropLastInactivityWarningColumn',
//		'addEmailAddressIndexToEmailsTable',
//		'addLoggedInWithinDaysColumnToSearchCriteriaTable',
//		'addCountryColumnToSearchCriteriaTable',
//		'addDeletedColumnToUserMessagesTable',
//		'createCountriesTable',
//		'createUsaStatesTable',
//		'addRotateAngleColumnToPhotosTable',
		'addPrimaryThumbnailRotateAngleColumnToUsersTable',
//		'updatePrimaryThumbnailRotateAnglesFromFirstPhotoRotateAngle',
	];

	public static function isInstalled (): bool {
		return (bool)DB::getNumRows('show tables like "db_upgrades"');
	} // isInstalled

	public static function performUpgrades (): void {
		$pending_upgrades = static::getPendingUpgrades();
		foreach ($pending_upgrades as $upgrade_method) {
			DB::log();
			DbUpgrades::$upgrade_method();
			$queries = DB::log(false);
			if (!$queries) {
				throw new Exception("Upgrade method $upgrade_method ran no queries.");
			}
			foreach ($queries as $query) {
				DbUpgradeModel::create(['upgrade_method' => $upgrade_method, 'query' => $query]);
			}
		}
	} // performUpgrades

	protected static function getPendingUpgrades (): array {
		if (!static::isInstalled()) {
			return static::$upgradeMethods;
		}
		$pending_upgrades = [];
		foreach (static::$upgradeMethods as $upgrade_method) {
			if (!method_exists(__CLASS__, $upgrade_method)) {
				throw new Exception("Upgrade method $upgrade_method not found.");
			}

			$is_upgrade_performed = DB::getNumRows("select true from db_upgrades where upgrade_method = '$upgrade_method'");
			if (!$is_upgrade_performed) {
				$pending_upgrades[] = $upgrade_method;
			}
		}
		return $pending_upgrades;
	} // getPendingUpgrades

	public static function getUpgradesSummary (): array {
		$query = 'select upgrade_method, query, inserted from db_upgrades order by db_upgrade_id desc limit 100';
		return DB::getTable($query);
	} // getUpgradesSummary

	////////////// UPGRADE METHODS BELOW ////////////////////////////////

	static function createDbUpgradesTable (): void {
		DB::query('
			create table db_upgrades (
				db_upgrade_id mediumint unsigned not null auto_increment primary key
				,upgrade_method varchar(255) not null
				,query text
				,inserted timestamp not null default current_timestamp
				,key upgrade_method_key (upgrade_method)
			) engine=InnoDB default charset=utf8mb4 collate utf8mb4_unicode_ci'
		);
	} // createDbUpgradesTable

	static function createHistoricEventsTable (): void {
		DB::query('
			create table historic_events (
				historic_event_id bigint unsigned not null auto_increment primary key
				,table_name varchar(255) not null default ""
				,entity_id bigint unsigned null default null
				,event_synopsis varchar(2000) not null default ""
				,sql_query text
				,connection_id bigint not null
				,inserted timestamp not null default current_timestamp
				,key entity (table_name, entity_id)
			) engine=InnoDB default charset=utf8mb4 collate utf8mb4_unicode_ci'
		);
	} // createHistoricEventsTable

	static function createUsersTable (): void {
		DB::query('
			create table users (
				user_id int unsigned not null auto_increment primary key
				,username varchar(29) not null default ""
				,verified_email varchar(255) null
				,unverified_email varchar(255) null
				,email_bouncing enum("ok","bounced","complained") null default null
				,spammer bool not null default false
				,last_visit date null default null
				,last_inactivity_warning date default null
				,verification_code varbinary(255) null
				,password_hash varbinary(255) not null default ""
				,mbti_type enum("ENFJ", "ENFP", "ENTJ", "ENTP", "ESFJ", "ESFP", "ESTJ", "ESTP", "INFJ", "INFP", "INTJ", "INTP", "ISFJ", "ISFP", "ISTJ", "ISTP") null default null
				,gender enum("male", "female") not null
				,orientation enum("straight", "gay", "bi") null default null
				,birth_date date null default null
				,body_type enum("slender","average","athletic","muscular","voluptuous","stocky","plump","large") null default null
				,height_in_in tinyint not null default 0
				,weight_in_kg smallint not null default 0
				,country varchar(255) not null default ""
				,city varchar(255) not null default ""
				,state varchar(255) not null default ""
				,zip_code varchar(255) not null default ""
				,latitude double null default null
				,longitude double null default null
				,share_keywords bool not null default true
				,have_children enum("no","yes","part-time","away") null default null
				,want_children enum("no","undecided","yes") null default null
				,would_relocate enum("no","yes","undecided") null default null
				,photo_order varchar(2000) not null default ""
				,primary_thumbnail_width smallint unsigned not null default 0
				,primary_thumbnail_height smallint unsigned not null default 0
				,self_described text
				,lover_described text
				,virtrades text
				,deactivated boolean not null default false
				,updated timestamp null default current_timestamp on update current_timestamp
				,inserted timestamp null default null
				,unique key verified_email_key (verified_email)
				,unique key unverified_email_key (unverified_email)
				,unique key verification_code_key (verification_code)
				,unique key username_key (username)
			) engine=InnoDB default charset=utf8mb4 collate utf8mb4_unicode_ci'
		);
	} // createUsersTable

	/** @see LoginTraits, LoginModel, LoginFinder */
	static function createLoginsTable (): void {
		DB::query('
			create table logins (
				login_id         bigint unsigned not null auto_increment primary key
				,user_id          int unsigned not null
				,login_method     enum("form","email","create") default null
				,login_timestamp  timestamp not null default current_timestamp
				,logout_timestamp timestamp null default null
				,cookie_password  varbinary(255) not null
				,user_agent       varchar(255) not null
				,ip_address       varchar(100) not null
				,screen_width     smallint unsigned default null
				,screen_height    smallint unsigned default null
				,color_depth      tinyint unsigned default null
				,window_width     smallint unsigned default null
				,window_height    smallint unsigned default null
				,utc_offset       smallint unsigned null default null
				,http_referer     varchar(255) default null
				,server_protocol  varchar(100) default null
				,http_host        varchar(255) default null
				,request_uri      varchar(255) default null
				,script_filename  varchar(255) default null,
				key user_id_index (user_id)
			) engine=InnoDB default charset=utf8mb4 collate utf8mb4_unicode_ci'
		);
	} // createLoginsTable

	static function createGlobalSettingsTable (): void {
		DB::query('
			create table `global_settings` (
				`global_setting_id` int unsigned not null auto_increment primary key
				,`setting_name` varchar(255) not null
				,`setting_value` varchar(255) not null
				,`updated` timestamp null default current_timestamp on update current_timestamp
				,`inserted` timestamp null default null
				,unique key `setting_name_key` (`setting_name`)
			) engine=InnoDB default charset=utf8mb4 collate utf8mb4_unicode_ci'
		);
	} // createGlobalSettingsTable

	static function createSqsMessagesTable (): void {
		DB::query('
			create table `sqs_messages` (
				`sqs_message_id` bigint unsigned not null auto_increment primary key
				,`is_deleted` tinyint not null default false
				,`message_id` varchar(2000) not null default ""
				,`receipt_handle` varchar(2000) not null
				,`md5_of_body` varchar(255) not null
				,`body` mediumtext
				,`raw_message` mediumtext
				,`updated` timestamp not null default current_timestamp on update current_timestamp
				,`inserted` timestamp null default null
			) engine=InnoDB default charset=utf8mb4 collate utf8mb4_unicode_ci'
		);
	} // createSqsMessagesTable

	static function createEmailsTable (): void {
		DB::query('
			create table `emails` (
				`email_id` bigint unsigned not null auto_increment primary key
				,`status` enum("queued","sending","sent","delivered","bounced","complaint","deleted","error") default "queued"
				,`to_email` varchar(255) not null default ""
				,`from_string` varchar(255) not null default ""
				,`subject` varchar(255) not null
				,`message_id` varbinary(255) not null default ""
				,`request_id` varbinary(255) not null default ""
				,`raw_source` mediumtext
				,`updated` timestamp null default current_timestamp on update current_timestamp
				,`inserted` timestamp null default null
			) engine=InnoDB default charset=utf8mb4 collate utf8mb4_unicode_ci'
		);
	} // createEmailsTable

	static function createZipCodeCoordinatesTable (): void {
		DB::query('
			create table zip_code_coordinates (
				`zip_code` char(5) not null primary key
				,`latitude` char(11) not null
				,`longitude` char(11) not null
				,unique key zip_code_key (zip_code)
			) engine=InnoDB default charset=utf8mb4 collate utf8mb4_unicode_ci'
		);
	} // createZipCodeCoordinatesTable

	static function createSearchCriteriaTable (): void {
		DB::query('
			create table search_criteria (
				search_criteria_id int unsigned not null auto_increment primary key
				,user_id int unsigned not null
				,max_distance smallint unsigned default null
				,exclude_contacted bool not null default false
				,gender enum("male","female") null default null
				,match_shared_negatives bool not null default false
				,mbti_types set("INTJ","ENTJ","INTP","ENTP","ISTJ","ESTJ","ISTP","ESTP","INFJ","ENFJ","INFP","ENFP","ISFJ","ESFJ","ISFP","ESFP") null default null
				,min_age tinyint unsigned null default null
				,max_age tinyint unsigned null default null
				,must_have_description bool not null default false
				,must_have_picture bool not null default false
				,must_like_my_gender bool not null default false
				,newer_than_days smallint unsigned default null
				,updated timestamp null default current_timestamp on update current_timestamp
				,inserted timestamp null default null
				,key user_id_key (user_id)
			) engine=InnoDB default charset=utf8mb4 collate utf8mb4_unicode_ci'
		);
	} // createSearchCriteriaTable

	static function createPhotosTable (): void {
		DB::query('
			create table photos (
				photo_id int unsigned not null auto_increment primary key
				,user_id int unsigned not null
				,caption text
				,thumbnail_width smallint unsigned not null default 0
				,thumbnail_height smallint unsigned not null default 0
				,standard_width smallint unsigned not null default 0
				,standard_height smallint unsigned not null default 0
				,original_width smallint unsigned not null default 0
				,original_height smallint unsigned not null default 0
				,deleted boolean not null default false
				,updated timestamp null default current_timestamp on update current_timestamp
				,inserted timestamp null default null
				,key user_id_key (user_id)
			) engine=InnoDB default charset=utf8mb4 collate utf8mb4_unicode_ci'
		);
	} // createPhotosTable

	static function createNegativeKeywordsTable (): void {
		DB::query('
			create table negative_keywords (
				negative_keyword_id bigint unsigned not null auto_increment primary key
				,user_id int unsigned not null
				,keyword varchar(34) not null
				,weight tinyint unsigned not null default 0
				,inserted timestamp null default current_timestamp
				,unique key user_id_and_keyword_key (user_id, keyword)
				,key keyword_key (keyword)
			) engine=InnoDB default charset=utf8mb4 collate utf8mb4_unicode_ci'
		);
	} // createNegativeKeywordsTable

	static function createPositiveKeywordsTable (): void {
		DB::query('
			create table positive_keywords (
				positive_keyword_id bigint unsigned not null auto_increment primary key
				,user_id int unsigned not null
				,keyword varchar(34) not null
				,weight tinyint unsigned not null default 0
				,inserted timestamp null default current_timestamp
				,unique key user_id_and_keyword_key (user_id, keyword)
				,key keyword_key (keyword)
			) engine=InnoDB default charset=utf8mb4 collate utf8mb4_unicode_ci'
		);
	} // createPositiveKeywordsTable

	static function createUserMessagesTable (): void {
		DB::query('
			create table user_messages (
				user_message_id bigint unsigned not null auto_increment primary key
				,from_user_id int unsigned not null
				,to_user_id int unsigned not null
				,message_text text
				,inserted timestamp null default current_timestamp
				,key from_user_id_key (from_user_id)
				,key to_user_id_key (to_user_id)
			) engine=InnoDB default charset=utf8mb4 collate utf8mb4_unicode_ci'
		);
	} // createUserMessagesTable

	static function createBlockedUsersTable (): void {
		DB::query('
			create table blocked_users (
				blocked_user_id int unsigned not null
				,blocked_by_user_id int unsigned not null
				,inserted timestamp null default current_timestamp
				,unique key users_key (blocked_user_id, blocked_by_user_id)
			) engine=InnoDB default charset=utf8mb4 collate utf8mb4_unicode_ci'
		);
	} // createBlockedUsersTable

	static function createReportedUsersTable (): void {
		DB::query('
			create table reported_users (
				reported_user_id int unsigned not null
				,reported_by_user_id int unsigned not null
				,inserted timestamp null default current_timestamp
				,unique key users_key (reported_user_id, reported_by_user_id)
			) engine=InnoDB default charset=utf8mb4 collate utf8mb4_unicode_ci'
		);
	} // createReportedUsersTable

	static function makeUtcOffsetSigned (): void {
		DB::query('alter table logins change column utc_offset utc_offset smallint null default null');
	} // makeUtcOffsetSigned

	static function addAdminNoteColumn (): void {
		DB::query('alter table users add column admin_note text after deactivated');
	} // addAdminNoteColumn

	static function addDeletedByAdminColumn (): void {
		DB::query('alter table users add column deleted_by_admin boolean not null default false after deactivated');
	} // addDeletedByAdminColumn

	static function changeLastVisitColumnTypeToTimestamp (): void {
		DB::query('alter table users change column last_visit last_visit timestamp null default null');
	} // changeLastVisitColumnTypeToTimestamp

	static function increaseSizeOfSqlQueryColumnInHistoricEventsTable (): void {
		DB::query('alter table historic_events change column sql_query sql_query mediumtext');
	} // increaseSizeOfSqlQueryColumnInHistoricEventsTable

	static function dropLastInactivityWarningColumn (): void {
		DB::query('alter table users drop column last_inactivity_warning');
	} // dropLastInactivityWarningColumn

	static function addEmailAddressIndexToEmailsTable (): void {
		DB::query('alter table emails add index to_email_key (to_email)');
	} // addEmailAddressIndexToEmailsTable

	static function addLoggedInWithinDaysColumnToSearchCriteriaTable (): void {
		DB::query('alter table search_criteria add column logged_in_within_days smallint unsigned null default null after newer_than_days');
	} // addLoggedInWithinDaysColumnToSearchCriteriaTable

	static function addCountryColumnToSearchCriteriaTable (): void {
		DB::query('alter table search_criteria add column country char(2) not null default "" after user_id');
	} // addCountryColumnToSearchCriteriaTable

	static function addDeletedColumnToUserMessagesTable (): void {
		DB::query('alter table user_messages add column deleted boolean default false after message_text');
	} // addDeletedColumnToUserMessagesTable

	static function createCountriesTable (): void {
		DB::query('
			create table countries (
				country_id smallint unsigned not null primary key auto_increment,
				country_code char(2) not null,
				country_name varchar(200) not null,
				inserted timestamp null default current_timestamp,
				unique key country_code_index (country_code)
			) engine=InnoDB default charset=utf8mb4 collate utf8mb4_unicode_ci'
		);
	} // createCountriesTable

	static function createUsaStatesTable () {
		DB::query('
			create table usa_states (
				usa_state_id tinyint unsigned not null primary key auto_increment,
				state_code char(2) not null unique key,
				state_name varchar(100) not null unique key,
				inserted timestamp null default current_timestamp
			)'
		);
	} // createUsaStatesTable

	static function addRotateAngleColumnToPhotosTable (): void {
		DB::query('
			alter table photos add column rotate_angle smallint null default null after original_height'
		); // unlike null, 0 means that the user chose the angle and it's therefore presumably correct
	} // addRotateAngleColumnToPhotosTable

	static function addPrimaryThumbnailRotateAngleColumnToUsersTable (): void {
		DB::query('
			alter table users add column primary_thumbnail_rotate_angle smallint null default null after primary_thumbnail_height'
		); // unlike null, 0 means that the user chose the angle and it's therefore presumably correct
	} // addPrimaryThumbnailRotateAngleColumnToUsersTable

	static function updatePrimaryThumbnailRotateAnglesFromFirstPhotoRotateAngle (): void {
		$users_resource = DB::query(
			'select user_id, photo_order from users where primary_thumbnail_rotate_angle is null and photo_order != "" and last_visit > "2019-11-01"'
		);
		while ( $user_row = DB::getRow( $users_resource ) ) {
			$user_id        = $user_row['user_id'];
			$photo_order    = $user_row['photo_order'];
			$first_photo_id = (int) explode( ',', $photo_order )[0];
			$first_photo_rotate_angle = DB::getCell(
				'select rotate_angle from photos where photo_id = ' . (int) $first_photo_id
			);
			if ( null === $first_photo_rotate_angle ) {
				continue;
			}
			DB::query(
				'update users set primary_thumbnail_rotate_angle = ' . (int) $first_photo_rotate_angle . ' where primary_thumbnail_rotate_angle is null and user_id = ' . (int) $user_id
			);
		}
	} // updatePrimaryThumbnailRotateAnglesFromFirstPhotoRotateAngle

} // DbUpgrades

