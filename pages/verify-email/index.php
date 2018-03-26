<?php

require_once $_SERVER['DOCUMENT_ROOT'] .'/includes/config.php';

$pageShell = new StandardPageShell("Verify Email");
$pageShell->addJsFile('/pages/verify-email/verify-email.js');

