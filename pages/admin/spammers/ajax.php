<?php

require_once $_SERVER['DOCUMENT_ROOT'] .'/includes/config.php';

$pageShell = new AjaxPageShell;

$action = $_GET['action'] ?? null;
switch ($action) {
	case 'update_spammers':
		handle_update_spammers();
		break;
	default:
		$pageShell->error("Undefined action: $action");
		break;
}

return; // functions below

function handle_update_spammers () {
	global $pageShell;

	$not_spammers_ids = $_POST['not_spammers_ids'] ?? null;
	if ($not_spammers_ids) {
		foreach ($not_spammers_ids as $user_id) {
			$userModel = new UserModel($user_id);
			$userModel->setIsSpammer(false);
		}
	}

	$new_spammers_ids = $_POST['new_spammers_ids'] ?? null;
	if ($new_spammers_ids) {
		$new_spammers_ids = preg_split('/[^0-9]+/', $new_spammers_ids);
		foreach ($new_spammers_ids as $user_id) {
			$userModel = new UserModel($user_id);
			$userModel->setIsSpammer();
		}
	}

	$pageShell->success();
} // handle_update_spammers

