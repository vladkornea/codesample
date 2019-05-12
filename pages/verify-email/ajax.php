<?php

require_once $_SERVER['DOCUMENT_ROOT'] .'/includes/config.php';

$pageShell = new AjaxPageShell;

$action = $_GET['action'];
switch ($action) {
	case 'verify_email':
		handle_verify_email();
		break;
	default:
		$pageShell->error("Undefined action: $action");
		break;
} // switch

return; // functions below

function handle_verify_email (): void {
	global $pageShell;
	$verification_code = $_POST['verification_code'] ?? null;
	if (!$verification_code) {
		$pageShell->error(['verification_code' => "Empty verification code."]);
	}
	$user_id = UserFinder::getIdFromVerificationCode($verification_code);
	if (!$user_id) {
		$pageShell->error("Invalid verification code (perhaps it expired or was used).");
	}
	$userModel = new UserModel($user_id);
	$userModel->logIn('email');
	$success = $userModel->verifyEmailAddress($verification_code);
	if ($success) {
		$userModel->setVerificationCode(null);
		$message_for_client = "Your email address has been verified.";
		$pageShell->success($message_for_client);
	} else {
		$is_email_address_verified = $userModel->getUnverifiedEmail() ? false : true;
		if ($is_email_address_verified) {
			$message_for_client = "Your email address is verified.";
			$pageShell->success($message_for_client);
		} else {
			$message_for_webmaster = "Error verifying email address. This should be impossible.";
			trigger_error($message_for_webmaster, E_USER_WARNING);
			$message_for_client = "Error verifying email address.";
			$pageShell->error($message_for_client);
		}
	}
} // handle_verify_email

