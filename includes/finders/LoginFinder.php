<?php

require_once 'BaseFinder.php';

interface LoginFinderInterface extends BaseFinderInterface {
	function setCookiePassword (string $cookie_password): LoginFinder;
	function setIsStillValid (bool $whether = true): LoginFinder;
	static function getCountOfUsersLoggedIn ($time_string_or_timestamp = 'yesterday'): int;
	static function getIdFromCookiePassword (string $cookie_password): ?int;
	static function getNextAllowedAccountCreationTimeOfIpAddress (string $ip_address): ?string;
} // LoginFinderInterface

class LoginFinder extends BaseFinder implements LoginFinderInterface {
	use LoginTraits;

	protected $cookiePassword = '';
	protected $isStillValid = null;
	protected $knownFields = [];


	public static function getNextAllowedAccountCreationTimeOfIpAddress (string $ip_address): ?string {
		$query = '
			select unix_timestamp(login_timestamp) from ' .static::$tableName .'
			where ' .DB::where(['ip_address' => $ip_address, 'login_method' => 'create']) .'
			order by login_timestamp desc limit 1';
		$last_login_timestamp = DB::getCell($query);
		if (!$last_login_timestamp) {
			return null;
		}
		$next_allowed_creation_timestamp = strtotime('+20 hours', $last_login_timestamp);
		$current_timestamp = time();
		if ($next_allowed_creation_timestamp < $current_timestamp) {
			return null;
		}
		return date('c', $next_allowed_creation_timestamp);
	} // getNextAllowedAccountCreationTimeOfIpAddress


	public function setCookiePassword (string $cookie_password): LoginFinder {
		$this->cookiePassword = $cookie_password;
		return $this;
	} // setCookiePassword


	public function setIsStillValid (bool $whether = true): LoginFinder {
		$this->isStillValid = $whether;
		return $this;
	} // setIsStillValid


	public static function getIdFromCookiePassword (string $cookie_password): ?int {
		$loginFinder = new self;
		$loginFinder->setCookiePassword($cookie_password);
		$result = $loginFinder->find();
		$something_found = DB::getNumRows($result);
		if (!$something_found) {
			return null;
		}
		$id = DB::getCell($result);
		return $id;
	} // getIdFromCookiePassword


	public static function getCountOfUsersLoggedIn ($time_string_or_timestamp = 'yesterday'): int {
		$when_timestamp = is_numeric($time_string_or_timestamp) ? $time_string_or_timestamp : strtotime($time_string_or_timestamp);
		$when_date = date('Y-m-d', $when_timestamp);
		$from_datetime = "$when_date 00:00:00";
		$to_datetime = "$when_date 23:59:59";
		$count_of_users_logged_in = DB::getCell("select count(distinct user_id) as users_logged_in from logins where login_timestamp>='$from_datetime' and login_timestamp<='$to_datetime'");
		return $count_of_users_logged_in;
	} // getCountOfUsersLoggedIn


	/**
	 * By default fields come from the `static::$columnNames` trait; overwrite this function in the
	 * extending class if you want to add ad-hoc fields. Used by `static::getValidDesiredFields()`.
	 * @return array SQL columns indexed by field names like:
	 * ['user_id' => 'u.user_id', 'some_ad_hoc_field' => DB::verbatim('some command')]
	 */
	static protected function getAvailableFields (): array {
		$available_fields = []; // return value
		foreach (static::$columnNames as $column_name) {
			$available_fields[$column_name] = static::$tableName .'.' .$column_name;
		}
		return $available_fields;
	} // getAvailableFields


	public function find (array $resource_fields = null): mysqli_result {
		$tables = [];
		$tables[] = static::$tableName;

		$valid_desired_fields = $this->getValidDesiredFields($resource_fields ?: [static::$primaryKeyName]);

		$where = [];
		if ($this->cookiePassword) {
			$where[] = 'logins.cookie_password = "' .DB::escape($this->cookiePassword) .'"';
		}
		if ($this->isStillValid) {
			$where[] = 'logins.logout_timestamp is null';
			$where[] = 'logins.login_timestamp > date_sub(now(), interval 6 month) ';
		}
		$query = "select " .implode(", ", $valid_desired_fields) ."\nfrom " .implode("\n", $tables);
		if ($where) {
			$query .= "\nwhere " .implode("\nand\t", $where);
		}
		return $this->query($query);
	} // find
} // LoginFinder

