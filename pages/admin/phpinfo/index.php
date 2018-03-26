<?php

require_once $_SERVER['DOCUMENT_ROOT'] .'/includes/config.php';

HttpPageShell::requireBasicHttpAuth();

phpinfo();

