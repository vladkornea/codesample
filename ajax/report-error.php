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
		'error_message'   => @$_POST['error_message']
		,'error_page'     => @$_POST['page_url']
		,'error_file_url' => @$_POST['error_file_url']
		,'line_number'    => @$_POST['line_number']
		,'stack_trace'    => @$_POST['stack_trace']
	];
	new JavascriptErrorHandler($error_details);
	$pageShell->success();
} // handle_report_javascript_error


function handle_report_ajax_error (): void {
	global $pageShell;
	$error_details = [ // bots access this URL without providing arguments
		'error_page'          => @$_POST['page_url']
		,'ajax_request_url'   => @$_POST['ajax_request_url']
		,'http_error_message' => @$_POST['http_error_message']
		,'error_functions'    => @$_POST['error_functions']
		,'error_message'      => @$_POST['error_message']
	];
	new AjaxErrorHandler($error_details);
	$pageShell->success();
} // handle_report_ajax_error

