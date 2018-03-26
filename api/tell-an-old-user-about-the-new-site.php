<?php

require_once $_SERVER['DOCUMENT_ROOT'] .'/includes/config.php';

$pageShell = new HttpPageShell;

$minutes = date('i');
$minutes_end_with_0 = $minutes[-1] == '0';
if (!$minutes_end_with_0) {
	trigger_error("Cannot tell an old user about the new site right this minute.");
	return;
}

$user_id = UserFinder::getSomeoneIgnorantOfNewSite();
if (!$user_id) {
	trigger_error("There are no more old users ignorant of the new site.", E_USER_NOTICE);
	return;
}

$userModel = new UserModel($user_id);
$userModel->sendEmailIntroducingNewSite();

