<?php

require_once $_SERVER['DOCUMENT_ROOT'] .'/includes/config.php';

$pageShell = new StandardPageShell("Forgot Password");
$pageShell->addJsFile('/pages/forgot-password/forgot-password.js');

$verification_code = trim($_GET['verification_code'] ?? '');
if ($verification_code) {
	$user_id_of_verification_code = UserFinder::getIdFromVerificationCode($verification_code);
	if ($user_id_of_verification_code) {
		$userModel = new UserModel($user_id_of_verification_code);
		$userModel->verifyEmailAddress($verification_code);
		$pageShell->addJsVar('verificationCodeIsValid', true);
		if (Session::getUserId() != $user_id_of_verification_code) {
			Session::logOut();
		}
	}
}

