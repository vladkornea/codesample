<?php

require_once $_SERVER['DOCUMENT_ROOT'] .'/includes/config.php';

$pageShell = new AjaxPageShell;

$action = $_GET['action'];
switch ($action) {
	case 'set_password':
		handle_set_password();
		break;
	default:
		$pageShell->error("Undefined action: $action");
		break;
} // switch

return; // functions below

function handle_set_password (): void {
	global $pageShell;
	$verification_code = $_POST['verification_code'] ?? null;
	$user_id = UserFinder::getIdFromVerificationCode($verification_code);
	if (!$user_id) {
		$pageShell->error("Invalid verification code (perhaps it expired or was used).");
	}
	$new_password = $_POST['new_password'] ?? null;
	$userModel = new UserModel($user_id);
	$error_messages = $userModel->setPassword($new_password);
	if ($error_messages) {
		$pageShell->error($error_messages);
	}
	$userModel->setVerificationCode(null);
	$userModel->logIn('email');
	$pageShell->success();
} // handle_set_password

