<?php

require_once $_SERVER['DOCUMENT_ROOT'] .'/includes/config.php';

$pageShell = new AdminPageShell("Test");
$pageShell->addJsFile('/pages/admin/test/test.js');
$pageShell->addJsFiles(['/js/lib/moment/2.19.2/moment-with-locales.js', '/js/lib/moment/2.19.2/moment-timezone-with-data.js']);

$db_connection_ok = is_array(DB::getRow('show tables')) ? true : false;
if (!$db_connection_ok) {
	$error_message = "DB Connection is not OK.";
	echo "<p>$error_message</p>";
	trigger_error($error_message, E_USER_WARNING);
} else {
	echo "<p>DB Connection is OK.</p>";
}

DB::test();

{ // test new email function
	Email::sendEmailToDeveloperViaSendmail([
		 'to'      => ERROR_RECIPIENT
		,'subject' => 'sendmail mail test'
		,'text'    => 'test of new email function'
	]);
	echo "<p>Email sent via new class using Mail_Mime.</p>";
}

{ // send email with SES
	$error_message = Email::sendEmailToClientViaAmazonSES([
		 'to'      => ERROR_RECIPIENT
		,'subject' => "testing email sending via SES"
		,'text'    => 'test email body'
	]);
	if ($error_message) {
		echo "<p>Credible email not sent: $error_message</p>";
		trigger_error($error_message, E_USER_WARNING);
	} else {
		echo "<p>Credible email sent.</p>";
	}
}

$userModel = new UserModel(1);
$userModel->sendEmailVerificationEmail();
echo '<p>Email verification email sent.</p>';
$userModel->sendForgotPasswordEmail();
echo '<p>Forgot password email sent.</p>';
$user_message_data = ['message_text'=>'test message', 'from_user_id'=>1, 'to_user_id'=>1];
['user_message_id'=>$user_message_id, 'error_messages'=>$error_messages] = UserMessageModel::create($user_message_data);
$userMessageModel = new UserMessageModel($user_message_id);
$error_message = $userMessageModel->send();
if ($error_message) {
	echo '<p class="error-message">', $error_message, '</p>';
} else {
	echo '<p>Message from user to user sent.</p>';
}

trigger_error("Testing warnings.", E_USER_WARNING);
//testWithBacktraces();
testWithNestedBacktraces();

//throw new Exception("Testing exceptions.");

//DB::getTable('nosuch');

return; // functions below


function testWithBacktraces () {
	trigger_error("Testing warnings with backtraces.", E_USER_WARNING);
	throw new Exception("Testing exceptions with backtraces.");
} // testWithBacktraces


function testWithNestedBacktraces () {
	testWithBacktraces();
} // testWithNestedBacktraces

