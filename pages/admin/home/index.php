<?php

require_once $_SERVER['DOCUMENT_ROOT'] .'/includes/config.php';

$pageShell = new AdminPageShell("Admin Home");
$pageShell->addCssFile('/pages/admin/home/home.css');
$pageShell->addJsFile('/pages/admin/home/home.js');

