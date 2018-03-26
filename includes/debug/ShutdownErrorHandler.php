<?php

require_once 'LocalProblemHandler.php';

class ShutdownErrorHandler extends LocalProblemHandler {
	public static function register (): void {
		register_shutdown_function([__CLASS__, 'handleShutdown']);
	} // register


	function __construct (array $last_error) {
		$this->errorLoggedAlready = true;
		parent::__construct();
		$this->problemType  = ErrorHandler::getProblemType($last_error['type']);
		$this->errorMessage = $last_error['message'];
		$this->errorFile    = $last_error['file'];
		$this->errorLine    = $last_error['line'];
		$this->errorPage     = static::getRequestUrl();
		$this->reportError();
	} // __construct


	/** Called by PHP. */
	public static function handleShutdown (): void {
		$last_error = error_get_last();
		if (!$last_error) {
			return;
		}
		new self($last_error);
	} // handleShutdown
} // ShutdownErrorHandler

