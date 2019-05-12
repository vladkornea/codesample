<?php

require_once $_SERVER['DOCUMENT_ROOT'] .'/includes/config.php';

$pageShell = new AjaxPageShell;

$action = $_GET['action'];
switch ($action) {
	case 'create_account':
		handle_create_account();
		break;
	default:
		$pageShell->error("Undefined action: $action");
		break;
} // switch

return; // functions below

function handle_create_account (): void {
	global $pageShell;

	$user_data = [
		 'username'         => $_POST['username'] ?? null
		,'unverified_email' => $_POST['email'] ?? null
		,'password'         => $_POST['password'] ?? null
		,'mbti_type'        => $_POST['mbti_type'] ?? null
		,'gender'           => $_POST['gender'] ?? null
		,'orientation'      => $_POST['orientation'] ?? null
		,'birth_month'      => $_POST['birth_month'] ?? null
		,'birth_day'        => $_POST['birth_day'] ?? null
		,'birth_year'       => $_POST['birth_year'] ?? null
		,'country'          => $_POST['country'] ?? null
		,'city'             => $_POST['city'] ?? null
		,'state'            => $_POST['state'] ?? null
		,'zip_code'         => $_POST['zip_code'] ?? null
		,'latitude'         => $_POST['latitude'] ?? null
		,'longitude'        => $_POST['longitude'] ?? null
	];
	['user_id'=>$user_id, 'error_messages'=>$error_messages] = UserModel::create($user_data);
	if ($error_messages) {
		$pageShell->error($error_messages);
	}

	$search_criteria_data = [];
	$search_criteria_data['user_id'] = $user_id;
	$search_criteria_id = SearchCriteriaModel::create($search_criteria_data);
	if (!is_numeric($search_criteria_id)) {
		$error_messages = $search_criteria_id;
		$pageShell->error($error_messages);
	}

	$userModel = new UserModel($user_id);
	$userModel->logIn('create');
	$userModel->sendEmailVerificationEmail();
	$pageShell->success(['verification_email_sent' => true]);
} // handle_create_account

