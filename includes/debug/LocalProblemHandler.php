<?php

require_once 'ProblemHandler.php';

class LocalProblemHandler extends ProblemHandler {
	const PROBLEMS_REPORTED_LIMIT = 20; // Checked against counter static::$problemsReported
	protected static $problemsReported = 0; // Counter checked against static::PROBLEMS_REPORTED_LIMIT


	function __construct () {
		$this->measureMemoryUsage();
		parent::__construct();
	} // __construct


	/**
	 * Error recipient is notified of memory usage, which we need to measure as soon as we enter a problem handler, so that
	 * it does not reflect memory consumed by backtraces and other variables created by the problem handling class itself.
	 * This method sets `$this->memoryUsed` to be subsequently reported by methods like `getEmailBody()`.
	 */
	protected function measureMemoryUsage (): void {
		$this->memoryUsed = memory_get_usage(true);
	} // measureMemoryUsage


	protected function reportError (): void {
		if (!error_reporting()) {
			return;
		}
		if (static::$problemsReported >= static::PROBLEMS_REPORTED_LIMIT) {
			return;
		}
		parent::reportError();
		static::$problemsReported++;
	} // reportError


	/** @return string Complete URL of current page. */
	protected static function getRequestUrl (): string {
		if (!isset($_SERVER['REQUEST_URI'])) {
			global $argv;
			return $argv[0];
		}
		$protocol = !empty($_SERVER['HTTPS']) ? 'https' : 'http';
		$host = $_SERVER['HTTP_HOST'];
		$request_uri = $_SERVER['REQUEST_URI'];
		return "{$protocol}://{$host}{$request_uri}";
	} // getRequestUrl
} // LocalProblemHandler

