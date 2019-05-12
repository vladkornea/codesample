<?php

require_once $_SERVER['DOCUMENT_ROOT'] .'/includes/config.php';

$pageShell = new AjaxPageShell;

$action = $_GET['action'];
switch ($action) {
	case 'report_javascript_error':
		handle_report_javascript_error();
		break;
	case 'report_ajax_error':
		handle_report_ajax_error();
		break;
	default:
		$pageShell->error("Undefined action: $action");
		break;
}

return; // functions below

function handle_report_javascript_error (): void {
	global $pageShell;
	$error_details = [ // bots access this URL without providing arguments
		'error_message'   => $_POST['error_message'] ?? null
		,'error_page'     => $_POST['page_url'] ?? null
		,'error_file_url' => $_POST['error_file_url'] ?? null
		,'line_number'    => $_POST['line_number'] ?? null
		,'stack_trace'    => $_POST['stack_trace'] ?? null
	];
	new JavascriptErrorHandler($error_details);
	$pageShell->success();
} // handle_report_javascript_error


function handle_report_ajax_error (): void {
	global $pageShell;
	$error_details = [ // bots access this URL without providing arguments
		'error_page'          => $_POST['page_url'] ?? null
		,'ajax_request_url'   => $_POST['ajax_request_url'] ?? null
		,'http_error_message' => $_POST['http_error_message'] ?? null
		,'error_functions'    => $_POST['error_functions'] ?? null
		,'error_message'      => $_POST['error_message'] ?? null
	];
	new AjaxErrorHandler($error_details);
	$pageShell->success();
} // handle_report_ajax_error

