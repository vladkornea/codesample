<?php

require_once $_SERVER['DOCUMENT_ROOT'] .'/includes/config.php';

$pageShell = new HttpPageShell;

$report_date = date('Y-m-d', strtotime('yesterday'));

$report_data = [];
$report_data['users_logged_in'] = LoginFinder::getCountOfUsersLoggedIn($report_date);
$report_data['users_registered'] = UserFinder::getCountOfUsersRegistered($report_date);
$report_data['messages_sent'] = UserMessageFinder::getCountOfMessagesSent($report_date);
$report_data['emails_sent'] = EmailFinder::getCountOfEmailsSent($report_date);

$report_text = print_r($report_data, true);

Email::sendEmailToDeveloperViaSendmail(['subject'=>"TypeTango Daily Summary for $report_date",'text'=>$report_text]);

