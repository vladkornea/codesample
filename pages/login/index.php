<?php

require_once $_SERVER['DOCUMENT_ROOT'] .'/includes/config.php';

if (Session::getUserId()) {
	HttpPageShell::redirect('/');
} else {
	require $_SERVER['DOCUMENT_ROOT'] .'/index.php';
}

