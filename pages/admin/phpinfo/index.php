<?php

require_once $_SERVER['DOCUMENT_ROOT'] .'/includes/config.php';

$pageShell = new AdminPageShell("phpinfo");

phpinfo();

