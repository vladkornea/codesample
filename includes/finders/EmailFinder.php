<?php

require_once 'BaseFinder.php';

interface EmailFinderInterface extends BaseFinderInterface {
	static function getCountOfEmailsSent ($time_string_or_timestamp = 'yesterday'): int;
} // EmailFinderInterface

class EmailFinder extends BaseFinder implements EmailFinderInterface {
	use EmailTraits;

	public static function getCountOfEmailsSent ($time_string_or_timestamp = 'yesterday'): int {
		$when_timestamp = is_numeric($time_string_or_timestamp) ? $time_string_or_timestamp : strtotime($time_string_or_timestamp);
		$when_date = date('Y-m-d', $when_timestamp);
		$from_datetime = "$when_date 00:00:00";
		$to_datetime = "$when_date 23:59:59";
		$count_of_messages_sent = DB::getCell("select count(*) as emails_sent from emails where inserted>='$from_datetime' and inserted<='$to_datetime'");
		return $count_of_messages_sent;
	} // getCountOfEmailsSent

	public function find (array $desired_fields = ['']): mysqli_result {
		throw new Exception("Not implemented.");
	} // find
} // EmailFinder

