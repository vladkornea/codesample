<?php

require_once $_SERVER['DOCUMENT_ROOT'] .'/includes/config.php';

$pageShell = new AjaxPageShell;

$action = $_GET['action'];
switch ($action) {
	case 'get_settings':
		handle_get_settings();
		break;
	case 'save_settings':
		handle_save_settings();
		break;
	default:
		$pageShell->error("Undefined action: $action");
		break;
} // switch

return; // functions below

function handle_get_settings (): void {
	global $pageShell;
	$valid_settings = GlobalSettings::$validSettings;
	$settings = (object)GlobalSettings::getSettings();
	$pageShell->success(['valid_settings' => $valid_settings, 'settings' => $settings]);
} // handle_get_settings

function handle_save_settings (): void {
	global $pageShell;
	$new_settings = $_POST['settings'];
	foreach ($new_settings as $setting_name => $setting_value) {
		GlobalSettings::setSetting($setting_name, (bool)$setting_value);
	}
	$pageShell->success();
} // handle_save_settings

