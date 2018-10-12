<?php

require_once $_SERVER['DOCUMENT_ROOT'] .'/includes/config.php';

Session::logOut();

HttpPageShell::redirect('/');

