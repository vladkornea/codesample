<?php
/**
 * Certain settings need to be toggled via web, which means they need to be stored in the DB.
 * This is the page where settings can be toggled via web.
 * The `GlobalSettings` class is the API to interact with here.
 */
require_once $_SERVER['DOCUMENT_ROOT'] .'/includes/config.php';
$pageShell = new AdminPageShell("K Settings");
$pageShell->addJsFile('/pages/admin/settings/settings.js');

$valid_settings = GlobalSettings::$validSettings;
$settings = (object)GlobalSettings::getSettings();
$pageShell->addJsVar('settings', ['valid_settings' => $valid_settings, 'settings' => $settings]);

echo '<div id="settings-form-container"></div>';

