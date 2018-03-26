<?php

require_once 'ProblemHandler.php';

abstract class RemoteProblemHandler extends ProblemHandler {
	function __construct (array $error_details) {
		$this->errorMessage = $error_details['error_message'];
		$this->errorPage    = $error_details['error_page'];
		{ // setting $this->errorFile from perhaps a URL passed by JS
			$error_file = '';
			if (isset($error_details['error_file_url'])) {
				$error_file = $_SERVER['DOCUMENT_ROOT'] .parse_url($error_details['error_file_url'], PHP_URL_PATH);
			} elseif (isset($error_details['error_file'])) {
				$error_file = $error_details['error_file'];
			}
			if ($error_file and is_file($error_file)) {
				$this->errorFile = realpath($error_file);
				$this->errorLine = $error_details['line_number'];
			}
		}
		if (!empty($error_details['stack_trace'])) {
			$this->backtrace = $this->getBacktraceFromJavascriptStacktrace($error_details['stack_trace']);
		}
		$this->reportError();
	} // __construct


	/** Error handlers need to set the problem details and then call this method. */
	protected function reportError (): void {
		$this->omitRequestArguments = true; // $_POST, $_GET, and $_FILES are irrelevant here
		parent::reportError();
	} // reportError


	protected function getBacktraceFromJavascriptStacktrace (array $stack_trace): array {
		$backtrace = [];
		foreach ($stack_trace as $stack_frame) {
			$function = $stack_frame['function'] ?? '';
			$remote_file = $stack_frame['file'] ?? '';
			$line = $stack_frame['line'] ?? '';
			$url_path = parse_url($remote_file, PHP_URL_PATH);
			$local_filepath = $_SERVER['DOCUMENT_ROOT'] .$url_path;
			$backtrace[] = ['file' => $local_filepath, 'line' => $line, 'function' => $function];
		}
		return $backtrace;
	} // getBacktraceFromJavascriptStacktrace
} // RemoteProblemHandler

