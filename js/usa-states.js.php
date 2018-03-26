<?php
/**
 * We need `usa-states.js` for forms, but we also need usa_states data in the database.
 * When `/js/usa-states.js` is requested and doesn't exist this file is served in its stead and creates it.
 * It reads the current usa_states data from the database.
 */

require_once $_SERVER['DOCUMENT_ROOT'] .'/includes/config.php';

define('REMOTE_FILE', '/js/usa-states.js');
define('DATA_FILE', $_SERVER['DOCUMENT_ROOT'] .REMOTE_FILE);

recreate_datafile_from_database();
header('Content-Type: text/javascript');
readfile(DATA_FILE);

return; // functions below

function recreate_datafile_from_database () {
	$usa_states_data = UsaStateFinder::getAllUsaStates();
	$usa_states_json = json_encode($usa_states_data);
	$usa_states_js_code = "window.states = $usa_states_json";
	file_put_contents(DATA_FILE, $usa_states_js_code);
	Email::sendEmailToDeveloperViaSendmail(['subject' => "Recreated " .DATA_FILE,
		'attachments' => [ ['file_location' => DATA_FILE, 'file_name' => basename(DATA_FILE).'.txt'] ]
	]);
} // recreate_datafile_from_database

