<?php

function get_current_page_url (): string {
	$protocol = !empty($_SERVER['HTTPS']) ? 'https' : 'http';
	$page_url = $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	return $page_url;
} // get_current_page_url


function get_random_string (int $length = 26, string $allowed_characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890'): string {
	$random_string = ''; // return value
	$max_array_index = strlen($allowed_characters) - 1;
	for ($i = 0; $i < $length; $i++) {
		$array_index = rand(0, $max_array_index);
		$random_string .= $allowed_characters[$array_index];
	}
	return $random_string;
} // get_random_string


function ob_get_end (): string {
	$ob_contents = ob_get_contents();
	ob_end_clean();
	return $ob_contents;
} // ob_get_end


function get_html_table_markup (array $table): string {
	$markup = ''; // return value
	$markup .= '<table class="keywords-table"><thead>';
	$markup .= '<tr>';
	$columns = array_keys($table[0]);
	foreach ($columns as $column) {
		$markup .= '<th>' .htmlspecialchars($column) .'</th>';
	}
	$markup .= '</tr>';
	$markup .= '</thead><tbody>';
	foreach ($table as $row) {
		$markup .= '<tr>';
		foreach ($row as $value) {
			$markup .= '<td>' .htmlspecialchars($value) .'</td>';
		}
		$markup .= '</tr>';
	}
	$markup .= '</tbody></table>';
	return $markup;
} // get_html_table_markup


/**
 * Quoting from the official documentation 9/28/2017
 * @see http://php.net/manual/en/faq.using.php#faq.using.shorthandbytes
 * "The available options are K (for Kilobytes), M (for Megabytes) and G (for Gigabytes; available since PHP 5.1.0),
 * and are all case-insensitive. Anything else assumes bytes. 1M equals one Megabyte or 1048576 bytes. 1K equals
 * one Kilobyte or 1024 bytes. These shorthand notations may be used in php.ini and in the ini_set() function."
 * @param string $shorthand_byte_notation like '100K', '20M', '1G' (anything else is interpreted as a byte)
 * @return int bytes
 */
function convert_shorthand_byte_notation_to_bytes (string $shorthand_byte_notation): int {
	$formula = str_replace(['K', 'M', 'G'], ['*1024', '*1048576', '*1073741824'], strtoupper($shorthand_byte_notation));
	return eval("return intval($formula);");
} // convert_shorthand_byte_notation_to_bytes


function get_mimetype_from_filename (string $filename): string {
	$file_chunks = explode('.', $filename);
	$file_extension = strtolower(array_pop($file_chunks));
	return [
		 'json' => 'application/json'
		,'doc'  => 'application/msword'
		,'pdf'  => 'application/pdf'
		,'gz'   => 'application/x-gzip'
		,'tgz'  => 'application/x-gzip'
		,'swf'  => 'application/x-shockwave-flash'
		,'tar'  => 'application/x-tar'
		,'zip'  => 'application/zip'
		,'mp1'  => 'audio/mpeg'
		,'mp2'  => 'audio/mpeg'
		,'mp3'  => 'audio/mpeg'
		,'mpga' => 'audio/mpeg'
		,'midi' => 'audio/midi'
		,'mid'  => 'audio/midi'
		,'kar'  => 'audio/midi'
		,'wav'  => 'audio/x-wav'
		,'bmp'  => 'image/bmp'
		,'gif'  => 'image/gif'
		,'jpeg' => 'image/jpeg'
		,'jpg'  => 'image/jpeg'
		,'jpe'  => 'image/jpeg'
		,'jfif' => 'image/jpeg'
		,'png'  => 'image/png'
		,'tiff' => 'image/tiff'
		,'tif'  => 'image/tiff'
		,'tga'  => 'image/x-targa'
		,'psd'  => 'image/vnd.adobe.photoshop'
		,'css'  => 'text/css'
		,'csv'  => 'text/csv'
		,'soa'  => 'text/dns'
		,'zone' => 'text/dns'
		,'htm'  => 'text/html'
		,'html' => 'text/html'
		,'js'   => 'text/javascript'
		,'asc'  => 'text/plain'
		,'txt'  => 'text/plain'
		,'text' => 'text/plain'
		,'pm'   => 'text/plain'
		,'el'   => 'text/plain'
		,'c'    => 'text/plain'
		,'h'    => 'text/plain'
		,'cc'   => 'text/plain'
		,'hh'   => 'text/plain'
		,'cxx'  => 'text/plain'
		,'hxx'  => 'text/plain'
		,'f90'  => 'text/plain'
		,'tsv'  => 'text/tab-separated-values'
		,'xml'  => 'text/xml'
		,'mpg4' => 'video/mp4'
		,'mp4'  => 'video/mp4'
		,'mpeg' => 'video/mpeg'
		,'mpg'  => 'video/mpeg'
		,'mpe'  => 'video/mpeg'
		,'ogv'  => 'video/ogg'
		,'mov'  => 'video/quicktime'
		,'qt'   => 'video/quicktime'
		,'wmv'  => 'video/x-ms-wmv'
		,'avi'  => 'video/x-msvideo'
	][$file_extension] ?? 'application/octet-stream';
} // get_mimetype_from_filename


// $birth_date is in 2017-11-18 format
function get_age_from_birth_date (string $birth_date): ?int {
	if (!$birth_date) {
		return null;
	}
	$birth_date_chunks = explode('-', $birth_date);
	if (count($birth_date_chunks) < 3) {
		return null;
	}
	[$birth_year, $birth_month, $birth_day] = $birth_date_chunks;
	[$now_year, $now_month, $now_day] = explode('-', date('Y-m-d'));
	$years_since = $now_year - $birth_year;
	$months_since = $now_month - $birth_month;
	$age = $years_since;
	if ($months_since < 0) {
		$age--;
	} elseif ($months_since = 0) {
		$days_since = $now_day - $birth_day;
		if ($days_since < 0) {
			$age--;
		}
	}
	return $age;
} // get_age_from_birth_date

