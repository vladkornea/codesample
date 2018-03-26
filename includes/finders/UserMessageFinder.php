<?php

require_once 'BaseFinder.php';

interface UserMessageFinderInterface extends BaseFinderInterface {
	function setOurUserId (int $user_id): void;
	function setTheirUserId (int $user_id): void;
	static function getCountOfMessagesSent ($time_string_or_timestamp = 'yesterday'): int;
} // UserMessageFinderInterface

class UserMessageFinder extends BaseFinder implements UserMessageFinderInterface {
	use UserMessageTraits;

	/* @see setOurUserId() */ protected $ourUserId;
	/* @see setOurUserId() */ protected $theirUserId;


	public function setOurUserId (int $user_id): void {
		$this->ourUserId = $user_id;
	} //   setOurUserId


	public function setTheirUserId (int $user_id): void {
		$this->theirUserId = $user_id;
	} // setTheirUserId


	public function find (array $resource_fields = ['user_message_id', 'from_user_id', 'to_user_id', 'message_text', 'inserted']): mysqli_result {
		if (!$this->ourUserId and !$this->theirUserId) {
			throw new Exception("Need both user IDs.");
		}
		$where = [];
		$where[] = "user_messages.deleted != true";
		$from_us_to_them_where_clause = DB::where(['from_user_id' => $this->ourUserId, 'to_user_id' => $this->theirUserId]);
		$from_them_to_us_where_clause = DB::where(['from_user_id' => $this->theirUserId, 'to_user_id' => $this->ourUserId]);
		$where[] = "(($from_us_to_them_where_clause) or ($from_them_to_us_where_clause))";
		$where_clause = implode(' and ', $where);
		$fields_string = implode(', ', $resource_fields);
		$query = "select $fields_string from " .static::$tableName .' where ' .$where_clause .' order by inserted desc';
		$result_resource = DB::query($query);
		return $result_resource;
	} // find


	public static function getCountOfMessagesSent ($time_string_or_timestamp = 'yesterday'): int {
		$when_timestamp = is_numeric($time_string_or_timestamp) ? $time_string_or_timestamp : strtotime($time_string_or_timestamp);
		$when_date = date('Y-m-d', $when_timestamp);
		$from_datetime = "$when_date 00:00:00";
		$to_datetime = "$when_date 23:59:59";
		$count_of_messages_sent = DB::getCell("select count(*) as messages_sent from user_messages where inserted>='$from_datetime' and inserted<='$to_datetime'");
		return $count_of_messages_sent;
	} // getCountOfMessagesSent
} // UserMessageFinder

