<?php

require_once $_SERVER['DOCUMENT_ROOT'] .'/includes/config.php';

$pageShell = new StandardPageShell("Create Account");
if (Session::getUserId()) {
	StandardPageShell::redirect('/profile');
}
$pageShell->addCssFile('/pages/create-account/create-account.css');

$next_allowed_account_creation_time_of_ip_address = LoginFinder::getNextAllowedAccountCreationTimeOfIpAddress($_SERVER['REMOTE_ADDR']);
if ($next_allowed_account_creation_time_of_ip_address) {
	$pageShell->includeMomentJsLib();
	$pageShell->addJsVar('nextAllowedAccountCreationTime', $next_allowed_account_creation_time_of_ip_address);
}

$pageShell->addJsFiles(['/js/countries.js', '/js/usa-states.js', '/pages/create-account/create-account.js']); // create-account.js relies on countries.js and usa-states.js

