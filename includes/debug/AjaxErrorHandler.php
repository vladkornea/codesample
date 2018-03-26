<?php

require_once 'RemoteProblemHandler.php';

class AjaxErrorHandler extends RemoteProblemHandler {
	function __construct (array $error_details) {
		$this->problemType = 'AJAX Error';
		if (isset($error_details['ajax_request_url'])) {
			$this->ajaxRequestUrl = $error_details['ajax_request_url'];
		}
		if (isset($error_details['http_error_message'])) {
			$this->httpErrorMessage = $error_details['http_error_message'];
		}
		if (isset($error_details['error_functions'])) {
			$this->errorFunctions = $error_details['error_functions'];
		}
		parent::__construct($error_details);
	} // __construct

	protected function getEmailSubject (): string {
		$error_message = $this->problemType;
		if ($this->ajaxRequestUrl) {
			$error_message .= ' requesting ' .$this->ajaxRequestUrl;
		}
		if ($this->errorPage) {
			$error_message .= ' on ' .explode($_SERVER['HTTP_HOST'], $this->errorPage)[1];
		}
		return $error_message;
	} // getEmailSubject
} // AjaxErrorHandler

