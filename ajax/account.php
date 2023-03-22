<?php

require_once $_SERVER['DOCUMENT_ROOT'] .'/includes/config.php';

$pageShell = new AjaxPageShell;

if ( empty( $_GET['action'] ) ) {
	$pageShell->error("Empty action parameter.");
}

$action = $_GET['action'];
switch ($action) {
	case 'log_in':
		handle_login();
		break;
	case 'log_out':
		handle_logout();
		break;
	case 'forgot_password':
		handle_forgot_password();
		break;
	case 'get_zip_code_coordinates':
		handle_get_zip_code_coordinates();
		break;
	default:
		$pageShell->error("Undefined action: $action");
		break;
}

return; // functions below

function handle_login (): void {
	global $pageShell;
	if ( empty( $_POST['password'] ) or empty( $_POST['user'] ) ) {
		$pageShell->error(['user' => "Invalid credentials."]);
	}
	$password = $_POST['password'];
	$remember_me = empty($_POST['remember_me']) ? false : true;
	$username_or_email = $_POST['user'];
	$user_id = UserFinder::getIdFromUsernameOrEmail($username_or_email);
	if (!$user_id) {
		$pageShell->error(['user' => "User not found."]);
	}
	$userModel = new UserModel($user_id);
	if (!$userModel->getIsPasswordValid($password)) {
		$pageShell->error(['password' => "Incorrect password."]);
	} else {
		$userModel->logIn('form', $remember_me);
		$pageShell->success();
	}
} // handle_login

function handle_logout (): void {
	global $pageShell;
	Session::logOut();
	$pageShell->success();
} // handle_logout

function handle_forgot_password (): void {
	global $pageShell;
	$username_or_email = $_POST['user'];
	$user_id = UserFinder::getIdFromUsernameOrEmail($username_or_email);
	if (!$user_id) {
		$pageShell->error(['user' => "User not found."]);
	}
	$userModel = new UserModel($user_id);
	$userModel->sendForgotPasswordEmail();
	$pageShell->success();
} // handle_forgot_password

function handle_get_zip_code_coordinates (): void {
	global $pageShell;

	if (empty($_REQUEST['zip_code'])) {
		$pageShell->error(['zip_code' => "Zip code is empty."]);
	}
	$zip_code = $_REQUEST['zip_code'];
	$query = 'select zip_code, latitude, longitude from zip_code_coordinates where ' .DB::where(['zip_code' => $zip_code]);
	$row = DB::getRow($query);
	if (!$row) {
		$pageShell->error(['zip_code', "No coordinates found for zip code."]);
	}
	$pageShell->success($row);
} // handle_get_zip_code_coordinates

