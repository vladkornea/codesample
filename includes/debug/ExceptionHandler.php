<?php

require_once 'LocalProblemHandler.php';

/**
 * @class ExceptionHandler
 * Call `ExceptionHandler::register()` to set `ExceptionHandler::handleException()` as the exception handler.
 */
class ExceptionHandler extends LocalProblemHandler {
	/** Sets `ExceptionHandler::handleException()` as the exception handler. */
	public static function register (): void {
		set_exception_handler( [__CLASS__, 'handleException'] );
	} // register


	/**
	 * Called by PHP.
	 * @param throwable $exception_or_error
	 * @link http://php.net/manual/en/function.set-exception-handler.php
	 */
	public static function handleException (throwable $exception_or_error): void {
		new self($exception_or_error);
	} // handleException


	function __construct (throwable $exception_or_error) {
		parent::__construct();
		$this->problemType  = 'Uncaught ' .get_class($exception_or_error);
		$this->errorMessage = $this->getMessage($exception_or_error);
		$this->errorFile    = $exception_or_error->getFile();
		$this->errorLine    = $exception_or_error->getLine();
		$this->errorPage    = static::getRequestUrl();
		$this->backtrace    = $exception_or_error->getTrace();
		$this->errorCode    = $exception_or_error->getCode();
		$this->reportError();
		die;
	} // __construct


	protected function getUserFriendlyBacktrace (): string {
		$backtrace_string = parent::getUserFriendlyBacktrace();
		if ($this->errorFile and $this->errorLine) {
			$relative_path = str_replace(realpath($_SERVER['DOCUMENT_ROOT']), '', realpath($this->errorFile));
			$error_line = $this->errorLine;
			$code_string = ($this->errorFile and $this->errorLine) ? static::getLineFromFile($this->errorFile, $this->errorLine) : '';
			$this_trace = "$relative_path:$error_line\n$code_string";
			$backtrace_string = $backtrace_string ? "$this_trace\n\n$backtrace_string" : $this_trace;
		}
		return $backtrace_string;
	} // getUserFriendlyBacktrace


	protected function getMessage (throwable $exception_or_error): string {
		$message = ''; // return value
		do {
			if ($message) {
				$message .= "\n";
			}
			$message .= $exception_or_error->getMessage();
		} while ($exception_or_error = $exception_or_error->getPrevious());
		return $message;
	} // getMessage
} // ExceptionHandler

