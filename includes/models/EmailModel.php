<?php

require_once 'LoggedModel.php';

interface EmailModelInterface extends LoggedModelInterface {
	static function sendQueuedEmails (): ?string;
	static function getIdFromMessageId (string $message_id): int;
	function setStatusBounced (): void;
	function setStatusComplained (): void;
	function setStatusDelivered (): void;
	function setStatusError (string $error_message): void;
	function setStatusSent (string $message_id, string $request_id): void;
	function getRawSource (): string;
	function getToEmailAddress (): string;
	function isAlreadyBounced (): bool;
	function isUserComplained (): bool;
} // EmailModelInterface

class EmailModel extends LoggedModel implements EmailModelInterface {
	use EmailTraits;


	/** @return null|string error message */
	protected function sendEmail (): ?string {
		$ses = new SES;
		$outcome = $ses->sendRawEmail($this->getRawSource());
		[
			 'message_id'    => $message_id
			,'request_id'    => $request_id
			,'error_message' => $error_message
		] = $outcome;
		if ($error_message) {
			$this->setStatusError($error_message);
			return $error_message;
		}
		$this->setStatusSent($message_id, $request_id);
		return null;
	} // sendEmail


	/** @return null|string error message */
	public static function sendQueuedEmails (): ?string {
		for ($i = 0; $i < 10; $i++) {
			if (!GlobalSettings::queuedEmailSending()) {
				$error_message = "Queued email sending is disabled.";
				return $error_message;
			}
			$queued_email_id = EmailModel::lockForSending();
			if (!$queued_email_id) {
				return null;
			}
			$emailModel = new EmailModel($queued_email_id);
			$error_message = $emailModel->sendEmail();
			if ($error_message) {
				trigger_error($error_message, E_USER_WARNING);
				return $error_message;
			}
		}
		return null;
	} // sendQueuedEmails


	public static function deleteQueuedEmails (string $email_address): void {
		$update = ['status'=> 'deleted'];
		$where  = ['to_email'=> $email_address, 'status'=> 'queued'];
		$affected_rows = DB::update(static::$tableName, $update, $where);
		if ($affected_rows) {
			$query = DB::getLastQuery();
			$event_synopsis = "$email_address: Deleted queued emails.";
			HistoricEventModel::create(['sql_query' => $query, 'event_synopsis' => $event_synopsis, 'table_name' => static::$tableName]);
		}
	} // deleteQueuedEmails


	/** @return int|null email_id */
	protected static function lockForSending (): ?int {
		$query = '
			update ' .static::$tableName .'
			set status = "sending"
			where
				status = "queued"
				and last_insert_id(' .static::$primaryKeyName .')
			order by ' .static::$primaryKeyName .'
			limit 1'
		;
		$affected_rows = DB::getAffectedRows($query);
		if (!$affected_rows) {
			return null;
		}
		$historic_event_query = DB::getLastQuery();
		$email_id = DB::getInsertId();
		HistoricEventModel::create(['event_synopsis' => "Locked email for sending.", 'sql_query' => $historic_event_query, 'table_name' => static::$tableName, 'entity_id' => $email_id]);
		return $email_id;
	} // lockForSending


	public function setStatusBounced (): void {
		$to_email_address = $this->getToEmailAddress();
		$this->update(['status' => 'bounced'], "$to_email_address: Email bounced.");
		$user_id = UserFinder::getIdFromEmail($to_email_address);
		if (!$user_id) {
			trigger_error("Cannot find user for bounced email address $to_email_address", E_USER_WARNING);
			return;
		}
		$userModel = new UserModel($user_id);
		$userModel->setIsBouncing();
	} // setStatusBounced


	public function setStatusComplained (): void {
		$to_email_address = $this->getToEmailAddress();
		$this->update(['status' => 'complaint'], "$to_email_address: User complained.");
		$user_id = UserFinder::getIdFromEmail($to_email_address);
		if (!$user_id) {
			trigger_error("Cannot find user for complained email address $to_email_address", E_USER_WARNING);
			return;
		}
		$userModel = new UserModel($user_id);
		$userModel->setUserComplained();
	} // setStatusComplained


	public function setStatusDelivered (): void {
		$to_email_address = $this->getToEmailAddress();
		$this->update(['status' => 'delivered'], "$to_email_address: Email delivered.");
		$user_id = UserFinder::getIdFromEmail($to_email_address);
		if (!$user_id) {
			trigger_error("Cannot find user for delivered email address $to_email_address", E_USER_WARNING);
			return;
		}
		$userModel = new UserModel($user_id);
		$userModel->setIsBouncing(false);
	} // setStatusDelivered


	public function setStatusError (string $error_message): void {
		trigger_error($error_message, E_USER_WARNING);
		$this->update(['status' => 'error'], $error_message);
	} // setStatusError


	public function setStatusSent (string $message_id, string $request_id): void {
		$this->update([
			'status'           => 'sent'
			,'message_id'      => $message_id
			,'request_id'      => $request_id
		], "{$this->getToEmailAddress()}: Email sent.");
	} // setStatusSent


	protected function getStatus (): string {
		return $this->commonGet('status');
	} // getStatus


	public function getRawSource (): string {
		return $this->commonGet('raw_source');
	} // getRawSource


	public function getToEmailAddress (): string {
		return $this->commonGet('to_email');
	} // getToEmailAddress


	public static function getIdFromMessageId (string $message_id): int {
		return DB::getCell("select " .static::$primaryKeyName ." from " .static::$tableName ." where " .DB::where(['message_id' => $message_id]));
	} // getIdFromMessageId


	public function isAlreadyBounced (): bool {
		return $this->getStatus() == 'bounced';
	} // isAlreadyBounced


	public function isUserComplained (): bool {
		return $this->getStatus() == 'complaint';
	} // isUserComplained
} // EmailModel

