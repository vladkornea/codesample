<?php
/**
 * We need `countries.js` for forms, but we also need countries data in the database.
 * When `/js/countries.js` is requested and doesn't exist this file is served in its stead and creates it.
 * It reads the current countries data from the database.
 */

require_once $_SERVER['DOCUMENT_ROOT'] .'/includes/config.php';

define('REMOTE_FILE', '/js/countries.js');
define('DATA_FILE', $_SERVER['DOCUMENT_ROOT'] .REMOTE_FILE);

recreate_datafile_from_database();
header('Content-Type: text/javascript');
readfile(DATA_FILE);

return; // functions below

function recreate_datafile_from_database () {
	$countries_data = CountryFinder::getAllCountries();
	$countries_json = json_encode($countries_data);
	$countries_js_code = "window.countries = $countries_json";
	file_put_contents(DATA_FILE, $countries_js_code);
	Email::sendEmailToDeveloperViaSendmail(['subject' => "Recreated " .DATA_FILE,
		'attachments' => [ ['file_location' => DATA_FILE, 'file_name' => basename(DATA_FILE).'.txt'] ]
	]);
} // recreate_datafile_from_database

