<?php

require_once $_SERVER['DOCUMENT_ROOT'] .'/includes/config.php';

$pageShell = new StandardPageShell("Test");
HttpPageShell::requireSessionLogin();
$pageShell->addJsFile('/pages/test/test.js');

