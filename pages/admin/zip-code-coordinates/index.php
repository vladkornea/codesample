<?php

require_once $_SERVER['DOCUMENT_ROOT'] .'/includes/config.php';

$pageShell = new AdminPageShell("Zip Code Coordinates");

if (isset($_POST['submitted'])) {
	$data_file = __DIR__ .'/2016_Gaz_zcta_national.txt';
	$handle = fopen($data_file, 'r');
	$header_line = trim(fgets($handle));
	$header_fields = explode("\t", $header_line);
	$header_field_indexes = array_flip($header_fields);
	set_time_limit(600);
	echo '<pre>';
	do {
		$line = fgets($handle);
		if ($line === false) {
			break;
		}
		$line = trim($line);
		if (!$line) {
			continue;
		}
		$fields = explode("\t", $line);
		$row = [
			'zip_code' => trim($fields[$header_field_indexes['GEOID']]),
			'latitude' => trim($fields[$header_field_indexes['INTPTLAT']]),
			'longitude' => trim($fields[$header_field_indexes['INTPTLONG']]),
		];
		DB::insert('zip_code_coordinates', $row);
		echo "\n" .trim(DB::getLastQuery());
	} while (true);
	echo '</pre>';
	echo "<p>Done importing.</p>";
} else {
	$count = DB::getCell('select count(*) from zip_code_coordinates');
	if ($count) {
		echo "<p>Table contains $count rows.</p>";
	} else {
		?>
		<form method="post">
			<input type="hidden" name="submitted" value="1">
			<input type="submit" value="Import Zip Code Coordinates">
		</form>
		<?php
	}
}

