<?php

ini_set('display_errors', false);

require 'constants.php';

session_start();

{ // error handlers
	require 'debug/ShutdownErrorHandler.php';
	ShutdownErrorHandler::register();
	require 'debug/ErrorHandler.php';
	ErrorHandler::register();
	require 'debug/ExceptionHandler.php';
	ExceptionHandler::register();
}

{ // always used
	require 'functions.php';
	require 'traits.php';
	require 'facade/DB.php';
	require 'facade/Session.php';
	require 'shells/HttpPageShell.php';
}

set_include_path(get_include_path()
	.PATH_SEPARATOR .__DIR__ .'/facade'
	.PATH_SEPARATOR .__DIR__ .'/shells'
	.PATH_SEPARATOR .__DIR__ .'/models'
	.PATH_SEPARATOR .__DIR__ .'/finders'
	.PATH_SEPARATOR .__DIR__ .'/debug'
); // set_include_path

spl_autoload_register(function ($classname) { // PHP asks this function to define undefined classes.
	$possible_filenames = ["$classname.php"];
	$possible_directories = explode(PATH_SEPARATOR, get_include_path());
	foreach ($possible_filenames as $possible_filename) {
		foreach ($possible_directories as $directory) {
			$full_path = "$directory/$possible_filename";
			if (file_exists($full_path)) {
				include_once $full_path;
				if (class_exists($classname)) {
					break;
				}
			}
		}
	}
}); // spl_autoload_register

