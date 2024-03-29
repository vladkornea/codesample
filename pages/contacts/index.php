<?php

require_once $_SERVER['DOCUMENT_ROOT'] .'/includes/config.php';

$pageShell = new StandardPageShell("Contacts");
HttpPageShell::requireSessionLogin();
$pageShell->addCssFile('/pages/contacts/contacts.css');
$pageShell->addJsFile('/pages/contacts/contacts.js');

// add contacted users data to JS
$userModel = Session::getUserModel();
$contacted_users = $userModel->getContactedUsersData();
$pageShell->addJsVar('contacted_users', $contacted_users);

// add blocked users data to JS
$blocked_users = $userModel->getBlockedUsersData();
$pageShell->addJsVar('blocked_users', $blocked_users);

// add reported users data to JS
$reported_users = $userModel->getReportedUsersData();
$pageShell->addJsVar('reported_users', $reported_users);

// add users waiting to hear from you
$users_waiting_to_hear_from_you = $userModel->getDataOfUsersWaitingToHearFromYou();
$pageShell->addJsVar('users_waiting_to_hear_from_you', $users_waiting_to_hear_from_you);

