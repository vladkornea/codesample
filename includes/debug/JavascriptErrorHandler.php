<?php

require_once 'RemoteProblemHandler.php';

/** Call `JavascriptErrorHandler::handleError()` to report JS errors via `ProblemHandler`. */
class JavascriptErrorHandler extends RemoteProblemHandler {

	public static function register (HtmlPageShell $pageShell): void {
		$user_agent_string = $_SERVER['HTTP_USER_AGENT'] ?? '';
		$is_microsoft_browser = strpos($user_agent_string, 'Trident') !== false; // Note that Edge claims to be itself while emulating IE
		// https://cdnjs.com/libraries/stacktrace.js/1.3.1
		if ($is_microsoft_browser) {
			$pageShell->addJsFile('/js/lib/stacktrace/1.3.1/stacktrace-with-promises-and-json-polyfills.min.js');
		} else {
			$pageShell->addJsFile('/js/lib/stacktrace/1.3.1/stacktrace.min.js');
		}
		$pageShell->addJsFile('/js/error-handler.js');
	} // register


	function __construct (array $error_details) {
		$this->problemType  = 'Javascript Error';
		parent::__construct($error_details);
	} // __construct

} // JavascriptErrorHandler

