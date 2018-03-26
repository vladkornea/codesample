<?php

require_once $_SERVER['DOCUMENT_ROOT'] .'/includes/config.php';

$pageShell = new AjaxPageShell();
EmailModel::sendQueuedEmails();
$pageShell->success("Called EmailModel::sendQueuedEmails()");

