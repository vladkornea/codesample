<?php

require_once $_SERVER['DOCUMENT_ROOT'] .'/includes/config.php';

$pageShell = new StandardPageShell("My Account");
HttpPageShell::requireSessionLogin();
$pageShell->addCssFile('/pages/account/account.css');
$pageShell->addJsFile('/pages/account/account.js');
$userModel = Session::getUserModel();
$js_vars = [
	'unverifiedEmail' => $userModel->getUnverifiedEmail()
	,'verifiedEmail'  => $userModel->getVerifiedEmail()
	,'deactivated'    => $userModel->getIsDeactivated()
	,'email_bouncing' => $userModel->getEmailBouncing()
];
$pageShell->addJsVar('defaultFormData', $js_vars);

echo '<div id="account-form-container"></div>';

