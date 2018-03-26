<?php

require_once 'HttpPageShell.php';

interface AjaxPageShellInterface extends HttpPageShellInterface {
	function success ($data_or_message = null): void;
	function error ($error_message_or_messages = null, array $extra_data = null): void;
} // AjaxPageShellInterface

class AjaxPageShell extends HttpPageShell implements AjaxPageShellInterface {
	/** @param array|string $data_or_message */
	public function success ($data_or_message = null): void {
		$data = is_array($data_or_message) ? $data_or_message : [];
		$message = is_string($data_or_message) ? $data_or_message : '';
		$data['success'] = true;
		if ($message) {
			$data['message'] = $message;
		}
		echo json_encode($data);
		exit;
	} // success

	/**
	 * @param null $error_message_or_messages
	 * @param array|null $extra_data
	 */
	public function error ($error_message_or_messages = null, array $extra_data = null): void {
		$data = ['error' => true];
		$error_message = is_string($error_message_or_messages) ? $error_message_or_messages : '';
		$error_messages = is_array($error_message_or_messages) ? $error_message_or_messages : [];
		if ($error_message) {
			$data['error_message'] = $error_message;
		}
		if ($error_messages) {
			$data['error_messages'] = $error_messages;
		}
		if ($extra_data) {
			$data= array_merge($data, $extra_data);
		}
		echo json_encode($data);
		exit;
	} // error
} // AjaxPageShell

