<?php

interface HttpPageShellInterface {
	static function requireAdminAccess (): void;
	static function requireBasicHttpAuth (): void;
	static function requireHttps (): void;
	static function requireSessionLogin (): void;
	static function forbid (string $message): void;
	static function redirect (string $url): void;
	static function movedPermanently (string $url): void;
	static function unauthorized (string $message): void;
} // HttpPageShellInterface

class HttpPageShell implements HttpPageShellInterface {
	protected static $redirecting = false;

	function __construct () {
		ob_start();
		static::requireHttps();
	} // __construct


	// Issues `403 Forbidden`
	public static function forbid (string $message = "Forbidden (HTTP 403)"): void {
		ob_clean();
		header("{$_SERVER['SERVER_PROTOCOL']} 403 Forbidden");
		echo '<p>' .htmlspecialchars($message) .'</p>';
		exit;
	} // forbid


	// This is basic HTTP Auth. Define `ADMIN_HTTP_USERNAME` and `ADMIN_HTTP_PASSWORD` to accepted values.
	protected static function isLoggedInViaBasicHttpAuth (): bool {
		if (
			!empty($_SERVER['PHP_AUTH_USER'])
			and !empty($_SERVER['PHP_AUTH_PW'])
			and defined('ADMIN_HTTP_USERNAME')
			and defined('ADMIN_HTTP_PASSWORD')
			and ADMIN_HTTP_USERNAME == $_SERVER['PHP_AUTH_USER']
			and ADMIN_HTTP_PASSWORD == $_SERVER['PHP_AUTH_PW']
		) {
			return true;
		} else {
			return false;
		}
	} // isLoggedInViaBasicHttpAuth


	public static function movedPermanently (string $url): void {
		static::$redirecting = true;
		header("HTTP/1.1 301 Moved Permanently");
		header("Location: $url");
		exit;
	} // movedPermanently


	public static function redirect (string $url): void {
		static::$redirecting = true;
		header("Location: $url");
		exit;
	} // redirect


	public static function requireAdminAccess (): void {
		static::requireBasicHttpAuth();
		$user_has_admin_ip_address = $_SERVER['REMOTE_ADDR'] == ADMIN_IP_ADDRESS;
		if (!$user_has_admin_ip_address) {
			static::forbid("Admin access required.");
		}
	} // requireAdminAccess


	/** Requires user to authenticate using basic HTTP Auth. */
	public static function requireBasicHttpAuth (): void {
		if (!static::isLoggedInViaBasicHttpAuth()) {
			ob_clean();
			static::unauthorized();
		}
	} // requireBasicHttpAuth


	public static function requireHttps (): void {
		if (!empty($_SERVER['HTTPS'])) {
			return;
		}
		static::movedPermanently('https://' .$_SERVER['HTTP_HOST'] .$_SERVER['REQUEST_URI']);
	} // requireHttps


	public static function requireSessionLogin (): void {
		if (!Session::getUserId()) {
			static::forbid("Login required.");
		}
	} // requireSessionLogin


	public static function unauthorized (string $message = "Basic Auth Needed (HTTP 401)"): void {
		header('WWW-Authenticate: Basic');
		header("{$_SERVER['SERVER_PROTOCOL']} 401 Unauthorized");
		echo "<p>" .htmlspecialchars($message) ."</p>";
		exit;
	} // unauthorized


	function __destruct () {
		if (static::$redirecting) {
			ob_end_clean();
		} else {
			ob_end_flush();
		}
	} // __destruct
} // class HttpPageShell

