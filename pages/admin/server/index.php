<?php

require_once $_SERVER['DOCUMENT_ROOT'] .'/includes/config.php';

$pageShell = new AdminPageShell("Server");

echo "<pre>";
print_r($_SERVER);
echo "</pre>";

