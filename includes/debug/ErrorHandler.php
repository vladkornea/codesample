<?php
require_once 'LocalProblemHandler.php';

class ErrorHandler extends LocalProblemHandler {
	/** Set `ErrorHandler::handleError()` as the error handler. */
	public static function register (): void {
		set_error_handler( [__CLASS__, 'handleError'], E_ALL );
	} // register

	/**
	 * Called by PHP.
	 * @param int $error_level
	 * @param string $error_message
	 * @param string $error_file
	 * @param int $error_line
	 * @link http://php.net/manual/en/function.set-error-handler.php
	 */
	public static function handleError (int $error_level, string $error_message, string $error_file, int $error_line): void {
		new self($error_level, $error_message, $error_file, $error_line);
	} // handleError

	// Converts a PHP error level to a user-friendly string.
	public static function getProblemType (int $error_level): string {
		switch($error_level) {
			case E_COMPILE_ERROR:
				$error_level_type = 'PHP E_COMPILE_ERROR';
				break;
			case E_CORE_ERROR:
				$error_level_type = 'PHP E_CORE_ERROR';
				break;
			case E_ERROR:
				$error_level_type = 'PHP E_ERROR';
				break;
			case E_USER_ERROR:
				$error_level_type = 'PHP E_USER_ERROR';
				break;
			case E_COMPILE_WARNING:
				$error_level_type = 'PHP E_COMPILE_WARNING';
				break;
			case E_CORE_WARNING:
				$error_level_type = 'PHP E_CORE_WARNING';
				break;
			case E_WARNING:
				$error_level_type = 'PHP E_WARNING';
				break;
			case E_USER_WARNING:
				$error_level_type = 'PHP E_USER_WARNING';
				break;
			case E_NOTICE:
				$error_level_type = 'PHP E_NOTICE';
				break;
			case E_USER_NOTICE:
				$error_level_type = 'PHP E_USER_NOTICE';
				break;
			case E_STRICT:
				$error_level_type = 'PHP E_STRICT';
				break;
			case E_RECOVERABLE_ERROR:
				$error_level_type = 'PHP E_RECOVERABLE_ERROR';
				break;
			case E_PARSE:
				$error_level_type = 'PHP E_PARSE';
				break;
			default:
				$error_level_type = "Level $error_level Error";
				break;
		}
		return $error_level_type;
	} // getProblemType


	function __construct (int $error_level, string $error_message, string $error_file, int $error_line) {
		parent::__construct();
		$this->problemType  = static::getProblemType($error_level);
		$this->errorMessage = $error_message;
		$this->errorFile    = $error_file;
		$this->errorLine    = $error_line;
		$this->errorPage    = static::getRequestUrl();

		(function(array $full_backtrace): void { // process backtrace
			foreach ($full_backtrace as $stack_frame) {
				if ( isset($stack_frame['class']) ) {
					if ( $stack_frame['class'] === __CLASS__ ) {
						continue;
					}
					if ( $stack_frame['class'] === 'Email' ) {
						$this->problemIsInEmailClass = true;
					}
				}
				$this->backtrace[] = $stack_frame;
			}
		})( debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS | ~DEBUG_BACKTRACE_PROVIDE_OBJECT, 50) );

		$this->reportError();

		if ( $this->problemType === 'Fatal Error' or $this->problemType === 'PHP Fatal error' ) {
			die;
		}
	} // __construct
} // ErrorHandler

