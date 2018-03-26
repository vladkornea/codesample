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
		 'username'         => @$_POST['username']
		,'unverified_email' => @$_POST['email']
		,'password'         => @$_POST['password']
		,'mbti_type'        => @$_POST['mbti_type']
		,'gender'           => @$_POST['gender']
		,'orientation'      => @$_POST['orientation']
		,'birth_month'      => @$_POST['birth_month']
		,'birth_day'        => @$_POST['birth_day']
		,'birth_year'       => @$_POST['birth_year']
		,'country'          => @$_POST['country']
		,'city'             => @$_POST['city']
		,'state'            => @$_POST['state']
		,'zip_code'         => @$_POST['zip_code']
		,'latitude'         => @$_POST['latitude']
		,'longitude'        => @$_POST['longitude']
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

