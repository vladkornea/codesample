<?php
/**
 * Base class providing functionality common to `JavascriptErrorHandler`,
 * `ErrorHandler`, and `ExceptionHandler`.
 */
abstract class ProblemHandler {
	const MAX_ATTACHMENT_SIZE = 150000;
	protected $problemType  = ''; // `Error`, `Warning`, `Uncaught Exception`, etc
	protected $errorMessage = '';
	protected $errorPage    = ''; // calculated differently for JS errors
	protected $errorFile    = '';
	protected $errorLine    = null;
	protected $errorCode    = null;
	protected $memoryUsed   = null; // ->measureMemoryUsage()
	protected $backtrace    = []; // JS errors don't have backtraces
	protected $omitRequestArguments = false; // $_POST, $_GET, and $_FILES
	protected $errorLoggedAlready = false; // Shutdown errors always get logged by PHP so we don't need to log them again
	protected $problemIsInEmailClass = false;
	protected $ajaxRequestUrl = '';
	protected $httpErrorMessage = '';
	protected $errorFunctions = '';

	function __construct() {} // __construct

	function __destruct () {} // __destruct

	/** Exception handlers need to set the problem details and then call this method. */
	protected function reportError (): void {
		if (error_reporting()) { // this accommodates error suppression operator (@)
			$this->logError();
			$this->displayError();
			$this->notifyErrorRecipient();
		}
	} // reportError


	/** Write error to log file if logging is enabled. */
	protected function logError (): void {
		if (!ini_get('log_errors')) {
			return;
		}
		if ($this->errorLoggedAlready) {
			return;
		}
		$log_message = $this->getLogEntry();
		$log_errors_max_len = ini_get('log_errors_max_len');
		if ($log_errors_max_len) {
			$log_message = substr($log_message, 0, $log_errors_max_len);
		}
		error_log($log_message);
	} // logError


	/** Echo error if `display_errors` is enabled. */
	protected function displayError (): void {
		if (!ini_get('display_errors')) {
			return;
		}
		$displayed_message = $this->getDisplayedError();
		if (ini_get('html_errors')) {
			$displayed_message = '<div class="error" style="white-space:pre-wrap; color:#C40000; background-color:#EFE44F; padding:4px 8px; z-index:999; display:inline-block; max-width:480px; min-width:125px; font-size:13px; line-height:17px; border:1px solid #C40000; margin:1px;">' .htmlspecialchars($displayed_message) .'</div>';
		}
		echo ini_get('error_prepend_string');
		echo $displayed_message;
		echo ini_get('error_append_string');
	} // displayError


	/** Send problem notification email. */
	protected function notifyErrorRecipient (): void {
		if ($this->problemIsInEmailClass) {
			return;
		}
		$email_params = [
			 'from'        => [ DEFAULT_FROM => SERVER_ROLE .' ' .get_called_class() ]
			,'reply-to'    => DEFAULT_REPLY_TO
			,'to'          => ERROR_RECIPIENT
			,'subject'     => $this->getEmailSubject()
			,'html'        => $this->getEmailBody()
			,'attachments' => [] // added to below
		];
		$timestamp = date('Y-m-d_H-i-s');
		array_push($email_params['attachments'],
			['file_name' => "SERVER_$timestamp.txt", 'file_content' => print_r($_SERVER, true)]
		);
		array_push($email_params['attachments'],
			['file_name' => "COOKIE_$timestamp.txt", 'file_content' => print_r($_COOKIE, true)]
		);
		array_push($email_params['attachments'],
			['file_name' => "SESSION_$timestamp.txt", 'file_content' => print_r($_SESSION, true)]
		);
		if (!$this->omitRequestArguments) {
			if (isset($_GET)) {
				array_push($email_params['attachments'],
					['file_name' => "GET_$timestamp.txt", 'file_content' => print_r($_GET, true)]
				);
			}
			if (isset($_POST)) {
				array_push($email_params['attachments'],
					['file_name' => "POST_$timestamp.txt", 'file_content' => print_r($_POST, true)]
				);
			}
			if (isset($_FILES)) {
				array_push($email_params['attachments'],
					['file_name' => "FILES_$timestamp.txt", 'file_content' => print_r($_FILES, true)]
				);
			}
		}
		if ($this->backtrace) {
			$attachment = is_string($this->backtrace) ? $this->backtrace : print_r($this->backtrace, true);
			$attachment_size = strlen($attachment);
			if ($attachment_size > self::MAX_ATTACHMENT_SIZE) {
				$attachment = 'Omitting attachment content because its size is ' .number_format($attachment_size) .' bytes.';
			}
			array_push($email_params['attachments'],
				['file_name' => "backtrace_$timestamp.txt", 'file_content' => $attachment]
			);
		}
		if (defined('STDIN')) {
			array_push($email_params['attachments'], ['file_name' => "STDIN_$timestamp.txt", 'file_stream' => STDIN]);
		}
		Email::sendEmailToDeveloperViaSendmail($email_params);
	} // notifyErrorRecipient


	protected function getEmailSubject (): string {
		return $this->problemType .' on line ' .$this->errorLine .' of ' .basename($this->errorFile);
	} // getEmailSubject


	protected function getEmailBody (): string {
		$markup = ''; // return value
		$problem_details = [];
		$problem_details['User'] = !Session::getUserId() ? 'not logged in' : Session::getUserModel()->getEmail() .' (' .Session::getUserModel()->getUsername() .')';
		if (!$this->errorMessage and !$this->errorPage and !$this->errorFile) {
			$problem_details['Meta Error'] = "Missing error message, page, and file. This is probably not a real error but someone accessing the error reporter URL directly (perhaps a bot ignoring robots.txt).";
		}
		if ($this->errorPage) {
			$problem_details['Page'] = $this->errorPage;
		}
		if ($this->errorFile and $this->errorLine) {
			$problem_details['PhpStorm'] = str_replace(realpath($_SERVER['DOCUMENT_ROOT']), '', realpath($this->errorFile)) .':' .$this->errorLine;
		}
		if ($this->backtrace) {
			$backtrace = $this->getUserFriendlyBacktrace();
			$problem_details['Backtrace'] = $backtrace;
		}
		if ($this->errorFile) {
			$problem_details['File'] = $this->errorFile;
		}
		if ($this->errorLine) {
			$problem_details['Line'] = $this->errorLine;
		}
		if ($this->errorCode) {
			$problem_details['Code'] = $this->errorCode;
		}
		if ($this->ajaxRequestUrl) {
			$problem_details['AJAX Request'] = $this->ajaxRequestUrl;
		}
		if ($this->httpErrorMessage) {
			$problem_details['HTTP Error'] = $this->httpErrorMessage;
		}
		if ($this->errorFunctions) {
			$problem_details['Functions'] = $this->errorFunctions;
		}
		if (isset($this->memoryUsed)) {
			$memory_limit = convert_shorthand_byte_notation_to_bytes( ini_get('memory_limit') );
			$percentage = round($this->memoryUsed / $memory_limit * 100, 2);
			$problem_details['Memory'] = number_format($this->memoryUsed) .' of ' .number_format($memory_limit) ." bytes ($percentage%)";
		}
		if (isset($_SERVER['REQUEST_TIME_FLOAT'])) {
			$execution_time = microtime(true) - (float)$_SERVER['REQUEST_TIME_FLOAT'];
			$problem_details['Execution Time'] = round($execution_time, 2) .' seconds';
		}
		$problem_details['Reported'] = date('Y-m-d H:i:s T');
		if (isset($_SERVER['HTTP_USER_AGENT'])) {
			$problem_details['Agent'] = htmlspecialchars($_SERVER['HTTP_USER_AGENT']);
		}
		if (isset($_SERVER['REMOTE_ADDR'])) {
			$problem_details['Remote'] = $_SERVER['REMOTE_ADDR'];
		}
		$markup .= '<table style="max-width:1000px; border:5px solid #fc8; border-radius:7px; border-spacing:1px; border-collapse:separate; font-size:13px; line-height:19px; color:#111; background-color:#fc8;">';
		$markup .= '<tr><td></td><th style="text-align:left; font-size:14px; font-weight:bold; padding:0 5px;">' .htmlspecialchars($this->problemType) .'</th></tr>';
		$markup .= '<tr><td></td><td style="padding:0 5px 3px 5px; white-space:pre-wrap;">' .htmlspecialchars($this->errorMessage) .'</td></tr>';
		foreach ($problem_details as $label => $detail) {
			$markup .= '<tr><th style="vertical-align:top; text-align:right; font-weight:normal; color:#666; font-size:11px; padding:1px 4px 1px 1px; white-space:pre !important;">'.htmlspecialchars($label).'</th><td style="white-space:pre-wrap; background-color:#eeb; padding-left:7px; padding-right:7px;">'.htmlspecialchars($detail).'</td></tr>';
		}
		$markup .= '</table>';
		return $markup;
	} // getEmailBody


	protected function getUserFriendlyBacktrace (): string {
		if (is_string($this->backtrace)) {
			return $this->backtrace;
		}
		$backtrace_paragraphs = [];
		foreach ($this->backtrace as $current_step) {
			$absolute_filename = $current_step['file'] ?? null;
			if (!$absolute_filename) {
				$backtrace_paragraphs[] = "Unknown file.";
				continue;
			}
			if (!file_exists($absolute_filename)) {
				$backtrace_paragraphs[] = $absolute_filename;
				continue;
			}
			$relative_filename = str_replace(realpath($_SERVER['DOCUMENT_ROOT']), '', realpath($absolute_filename));
			$step_paragraph = $relative_filename;
			$line_number = $current_step['line'] ?? null;
			if ($line_number) {
				$step_paragraph .= ":$line_number";
				$code_line = static::getLineFromFile($absolute_filename, $line_number);
				if ($code_line) {
					$step_paragraph .= "\n$code_line";
				}
			}
			$backtrace_paragraphs[] = $step_paragraph;
		}
		$backtrace_text = implode("\n\n", $backtrace_paragraphs);
		return $backtrace_text;
	} // getUserFriendlyBacktrace


	protected static function getLineFromFile (?string $file, ?int $line_number): ?string {
		if ( !$line_number || !$file ) {
			return null;
		}
		if ( !file_exists($file) ) {
			return "( File does not exist )";
		}
		$handle = fopen($file, 'r');
		if (false === $handle) {
			return "( Error opening file $file )";
		}
		for ($i = 1; $i < $line_number; $i++) {
			fgets($handle);
		}
		$line = fgets($handle);
		$line_length = strlen($line);
		if ($line_length > 500) {
			return "( Line is $line_length characters long )";
		}
		return rtrim($line, "\r\n");
	} // getLineFromFile


	protected function getLogEntry (): string {
		return "{$this->problemType} on line {$this->errorLine} of {$this->errorFile}: {$this->errorMessage}";
	} // getLogEntry


	protected function getDisplayedError (): string {
		return "Line {$this->errorLine} of ".basename($this->errorFile) ." - {$this->errorMessage}";
	} // getDisplayedError
} // ProblemHandler

