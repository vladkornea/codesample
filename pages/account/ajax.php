<?php

require_once $_SERVER['DOCUMENT_ROOT'] .'/includes/config.php';

$pageShell = new AjaxPageShell;
HttpPageShell::requireSessionLogin();

$action = $_GET['action'];
switch ($action) {
	case 'update_account':
		handle_update_account();
		break;
	default:
		$pageShell->error("Undefined action: $action");
		break;
} // switch

return; // functions below

function handle_update_account (): void {
	global $pageShell;

	$error_messages = [];

	$user_id = Session::getUserId();
	$userModel = new UserModel($user_id);

	if (empty($_POST['current_password'])) {
		$pageShell->error(['current_password' => "Current password is required."]);
	} else {
		if (!$userModel->getIsPasswordValid($_POST['current_password'])) {
			$pageShell->error(['current_password' => "Incorrect current password."]);
		}
	}

	$success_data = null;
	if (!empty($_POST['new_email'])) {
		$new_email_address = $_POST['new_email'];
		$original_unverified_email = $userModel->getUnverifiedEmail();
		$error_message = $userModel->setUnverifiedEmail($new_email_address);
		if ($error_message) {
			$error_messages = array_merge($error_messages, $error_message);
		} else {
			$current_unverified_email = $userModel->getUnverifiedEmail();
			$email_address_changed = ($current_unverified_email and ($current_unverified_email != $original_unverified_email));
			if ($email_address_changed) {
				$userModel->sendEmailVerificationEmail();
				$success_data = ['alert' => "Email verification message has been sent to your new email address."];
			}
		}
	}

	if (!empty($_POST['new_username'])) {
		$new_username = $_POST['new_username'];
		$error_message = $userModel->setUsername($new_username);
		if ($error_message) {
			$error_messages = array_merge($error_messages, $error_message);
		}
	}

	if (!empty($_POST['new_password'])) {
		$new_password = $_POST['new_password'];
		$error_message = $userModel->setPassword($new_password);
		if ($error_message) {
			$error_messages = array_merge($error_messages, $error_message);
		}
	}

	if (isset($_POST['deactivated'])) {
		if ($_POST['deactivated']) {
			$userModel->deactivate();
		} else {
			$userModel->activate();
		}
	}

	if ($error_messages) {
		$pageShell->error($error_messages);
	}

	$pageShell->success($success_data);
} // handle_update_account

