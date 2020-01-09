<?php

require_once $_SERVER['DOCUMENT_ROOT'] .'/includes/config.php';

$pageShell = new AdminAjaxPageShell;

$action = $_GET['action'];
switch ($action) {
	case 'get_all':
		get_all_global_settings();
		break;
	case 'save':
		save_settings();
		get_all_global_settings();
		break;
	default:
		$pageShell->error("Undefined action: $action");
		break;
} // switch

return; // functions below

function save_settings (): void {
	$new_settings = [];
	$valid_settings = [
		GlobalSettings::RECEIVED_QUEUE_PROCESSING,
		GlobalSettings::SENT_QUEUE_PROCESSING,
		GlobalSettings::QUEUED_EMAIL_SENDING,
	];
	foreach ($valid_settings as $valid_setting) {
		if (isset($_REQUEST[$valid_setting])) {
			$new_settings[$valid_setting] = $_REQUEST[$valid_setting];
		}
	}
	GlobalSettings::setSettings($new_settings);
} // save_settings


function get_all_global_settings (): void {
	global $pageShell;
	$all_global_settings = GlobalSettings::getSettings();
	if (!$all_global_settings) {
		$pageShell->error("Error getting all global settings.");
	} else {
		$pageShell->success(['settings' => $all_global_settings]);
	}
} // get_all_global_settings

