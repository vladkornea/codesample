<?php
/**
 * Application code should delegate to this class all interactions with $_SESSION and $_COOKIE,
 * setting and deleting cookies, logging users in and out, and similar implementation details.
 */
interface SessionInterface {
	static function logIn (int $user_id, string $login_method, bool $remember_me = false): ?array;
	static function logOut (): void;
	static function setCookie (string $name, string $value, $expires = 0): void;
	static function removeCookie (string $name): void;
	static function getLoginMethod (): ?string;
	static function getUserId (): ?int;
	static function getUserModel (): ?UserModel;
	static function getEmail (): string;
} // SessionInterface

class Session implements SessionInterface {
	/** @var null|int $userId
	  * @see Session::getUserId() */ protected static $userId = null;

	/** @var null|UserModel $userModel
	  * @see Session::getUserModel() */ protected static $userModel = null;

	const LOGIN_COOKIE_NAME = 'cookie_password';

	/**
	 * This method performs no authentication, merely sets the relevant data.
	 * @param int $user_id
	 * @param string $login_method One of ('form' 'email' 'create') for the `logins.login_method` field.
	 * @param bool $remember_me Whether cookie should persist after the browser is closed.
	 * @return array|null Error messages.
	 */
	public static function logIn (int $user_id, string $login_method, bool $remember_me = false): ?array {
		$login_data = [
			'user_id'         => $user_id,
			'login_method'    => $login_method,
			'screen_width'    => $_POST['screen_width'] ?? null,
			'screen_height'   => $_POST['screen_height'] ?? null,
			'color_depth'     => $_POST['color_depth'] ?? null,
			'window_width'    => $_POST['window_width'] ?? null,
			'window_height'   => $_POST['window_height'] ?? null,
			'utc_offset'      => $_POST['utc_offset'] ?? null,
			'user_agent'      => $_SERVER['HTTP_USER_AGENT'] ?? null,
			'ip_address'      => $_SERVER['REMOTE_ADDR'],
			'http_referer'    => $_SERVER['HTTP_REFERER'] ?? null,
			'server_protocol' => $_SERVER['SERVER_PROTOCOL'],
			'http_host'       => $_SERVER['HTTP_HOST'],
			'request_uri'     => $_SERVER['REQUEST_URI'],
			'script_filename' => $_SERVER['SCRIPT_FILENAME'],
		];
		['error_messages' => $error_messages, 'login_id' => $login_id] = LoginModel::create($login_data);
		if ($error_messages) {
			return $error_messages;
		}
		$loginModel = new LoginModel($login_id);
		$loginModel->getUserModel()->setLastVisit();
		$_SESSION['login_method'] = $login_method;
		$cookie_expiration = $remember_me ? '+1 year' : 0;
		static::setCookie(static::LOGIN_COOKIE_NAME, $loginModel->getCookiePassword(), $cookie_expiration);
		return null;
	} // logIn


	public static function logOut (): void {
		$cookie_password = $_COOKIE[static::LOGIN_COOKIE_NAME] ?? null;
		if ($cookie_password) {
			$login_id = DB::getCell((new LoginFinder)->setCookiePassword($cookie_password)->find());
			if (!$login_id) {
				trigger_error("Attempted to log out, but there is no such cookie_password ($cookie_password).", E_USER_WARNING);
			} else {
				(new LoginModel($login_id))->logOut();
			}
		}
		static::$userId    = null;
		static::$userModel = null;
		static::removeCookie(static::LOGIN_COOKIE_NAME);
		$_SESSION = [];
	} // logOut


	/** @return null|string One of ('form' 'email' 'create' 'cookie') if logged in. */
	public static function getLoginMethod (): ?string {
		if (isset($_SESSION['login_method'])) {
			return $_SESSION['login_method'];
		}
		return Session::getUserId() ? 'cookie' : null;
	} // getLoginMethod


	/**
	 * @param string $cookie_name
	 * @param string $cookie_value
	 * @param int|string $when_expires unix timestamp or string understood by `strtotime()` (`0` means "when browser closes").
	 */
	public static function setCookie (string $cookie_name, string $cookie_value, $when_expires = 0): void {
		if (!is_numeric($when_expires)) {
			$when_expires = strtotime($when_expires);
		}
		setcookie($cookie_name, $cookie_value, $when_expires, '/', $_SERVER['HTTP_HOST']);
	} // setCookie


	public static function removeCookie (string $cookie_name): void {
		setcookie($cookie_name, '', strtotime('-1 day'), '/', $_SERVER['HTTP_HOST']);
	} // removeCookie


	public static function getUserId (): ?int {
		if (!static::$userId) {
			static::$userId = static::getCookieUser();
		}
		return static::$userId;
	} // getUserId


	public static function getUserModel (): ?UserModel {
		if (!static::$userModel) {
			$user_id = static::getUserId();
			if (!$user_id) {
				return null;
			}
			static::$userModel = new UserModel($user_id);
		}
		return static::$userModel;
	} // getUserModel


	public static function getEmail (): string {
		$userModel = static::getUserModel();
		return $userModel ? $userModel->getEmail() : '';
	} // getEmail


	protected static function getCookieUser (): ?int {
		$cookie_password = $_COOKIE[static::LOGIN_COOKIE_NAME] ?? null;
		if (!$cookie_password) {
			return null;
		}
		try {
			return DB::getCell(
				(new LoginFinder)->setCookiePassword($cookie_password)->setIsStillValid()->find(['user_id'])
			);
		} catch (TableDoesNotExistException $exception) {
			return null;
		}
	} // getCookieUser
} // Session

