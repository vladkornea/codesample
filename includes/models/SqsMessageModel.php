<?php

require_once 'LoggedModel.php';

interface SqsMessageModelInterface extends LoggedModelInterface {
	static function getLocalDbIdFromAwsMessageId (string $message_id): ?int;
	function markDeleted (string $event_synopsis = null): void;
	function getNotification (): array;
	function getNotificationType (): string;
} // SqsMessageModelInterface

class SqsMessageModel extends LoggedModel implements SqsMessageModelInterface {
	use SqsMessageTraits;

	protected $decodedBody = ''; // call $sqsMessage->getDecodedBody()

	public static function create (array $form_data, string $event_synopsis = '', bool $log_query = false) {
		return parent::create($form_data, $event_synopsis, $log_query);
	} // create

	public function getNotification (): array {
		$notification = $this->getDecodedBody();
		if (!$notification) {
			trigger_error("Error extracting SNS notification from SQS message.", E_USER_WARNING);
		}
		return $notification;
	} // getNotification

	public function getNotificationType (): string {
		return $this->getNotification()['notificationType'];
	} // getNotificationType

	public function markDeleted (string $event_synopsis = null): void {
		if (!isset($event_synopsis)) {
			$event_synopsis = "SQS message deleted.";
		}
		$logged_event_synopsis = "{$this->commonGet('message_id')}: $event_synopsis";
		$this->update(['is_deleted' => true], $logged_event_synopsis);
	} // markDeleted

	protected function getDecodedBody (): array {
		if (!$this->decodedBody) {
			$encoded_body = $this->commonGet('body');
			$this->decodedBody = json_decode($encoded_body, true);
		}
		return $this->decodedBody;
	} // getDecodedBody

	public static function getLocalDbIdFromAwsMessageId (string $message_id): ?int {
		$query = 'select ' .static::$primaryKeyName .' from ' .static::$tableName .' where ' .DB::where(['message_id' => $message_id]);
		$local_db_sqs_message_id = DB::getCell($query);
		return $local_db_sqs_message_id;
	} // getLocalDbIdFromAwsMessageId

	/**
	 * @return string|bool event synopsis or true on success, boolean false on error
	 * @throws EmailException
	 */
	public function processSqsMessage () {
		$notification = $this->getNotification();
		if (!$notification) {
			trigger_error("Error getting notification.", E_USER_WARNING);
			return false;
		}
		$notification_type = $this->getNotificationType();
		if (!in_array($notification_type, ['Received', 'Delivery', 'Bounce', 'Complaint', 'AmazonSnsSubscriptionSucceeded'])) {
			trigger_error("Unknown notification type: $notification_type", E_USER_WARNING);
			return false;
		}

		if ($notification_type == 'AmazonSnsSubscriptionSucceeded') {
			$event_synopsis = "Deleted useless AmazonSnsSubscriptionSucceeded notification.";
			return $event_synopsis;
		} elseif ($notification_type == 'Received') {
			$mail_message = $notification['mail'];
			$destination = $mail_message['destination'][0];
			$mail_message_id = $mail_message['messageId'];
			if ($mail_message_id == 'AMAZON_SES_SETUP_NOTIFICATION') {
				$event_synopsis = "Processed AMAZON_SES_SETUP_NOTIFICATION";
				return $event_synopsis;
			}

			$useful_mail_headers = [];
			$all_mail_headers = $mail_message['headers'];
			foreach ($all_mail_headers as $mail_header) {
				$header_name = strtolower($mail_header['name']);
				$header_value = $mail_header['value'];
				if (in_array($header_name, ['to', 'from', 'subject', 'content-type', 'date', 'content-transfer-encoding', 'mime-version'])) {
					$useful_mail_headers[$header_name] = $header_value;
				}
			}
			$from_email = Email::extractEmailAddress($useful_mail_headers['from']);
			if (!$from_email) {
				trigger_error("Failed to extract email address from string: {$useful_mail_headers['from']}", E_USER_WARNING);
				return false;
			}
			$subject = $useful_mail_headers['subject'] ?? '';
			$notification_content = base64_decode($notification['content']);
			$original_raw_email_body = preg_split('/\r\n\r\n/', $notification_content, 2)[1];
			$email_params = [];
			if (isset($useful_mail_headers['content-type'])) { // we made sure all keys were lowercase
				$original_content_type = $useful_mail_headers['content-type'];
				$preg_matches = [];
				$match_found = preg_match('/charset=([-a-z0-9_.]+)/i', $original_content_type, $preg_matches);
				if ($match_found) {
					$alien_charset = $preg_matches[1];
					if ('utf-8' != strtolower($alien_charset)) {
						$utf8_encoded = mb_convert_encoding($original_raw_email_body, 'UTF-8', $alien_charset);
						$new_content_type = str_replace($alien_charset, 'utf-8', $original_content_type);
					} else {
						$utf8_encoded = $original_raw_email_body;
						$new_content_type = $original_content_type;
					}
					$email_params['raw_email_body'] = $utf8_encoded;
					$email_params['content-type'] = $new_content_type;
				} else { // would this ever happen, and if so what to do?
					$email_params['raw_email_body'] = $original_raw_email_body;
					$email_params['content-type'] = $original_content_type;
				}
			}
			if (isset($useful_mail_headers['content-transfer-encoding'])) {
				$email_params['content-transfer-encoding'] = $useful_mail_headers['content-transfer-encoding'];
			}
			{ // Forward email to ERROR_RECIPIENT
				HistoricEventModel::create(['event_synopsis' => "$from_email: Received email for {$useful_mail_headers['to']}: $subject"]);
				$from_header_value = Email::getRelaySender($useful_mail_headers['from'], DEFAULT_FROM);
				$email_params['from']     = $from_header_value;
				$email_params['reply-to'] = $useful_mail_headers['from'];
				$email_params['to']       = ERROR_RECIPIENT;
				$email_params['subject']  = $subject;
				Email::sendEmailToClientViaAmazonSES($email_params);
				return true;
			}
		} elseif (in_array($notification_type, ['Delivery', 'Bounce', 'Complaint'])) {
			$mail = $notification['mail'];
			$mail_message_id = $mail['messageId'];
			$email_id = EmailModel::getIdFromMessageId($mail_message_id);
			if (!$email_id) {
				trigger_error("Error (database reinstalled?) getting local DB email ID from AWS mail message ID: $mail_message_id", E_USER_WARNING);
				if (SERVER_ROLE == 'dev') {
					return true;
				}
				return false;
			}
			$emailModel = new EmailModel($email_id);
			switch ($notification_type) {
				case 'Complaint':
					$emailModel->setStatusComplained();
					break;
				case 'Bounce':
					$emailModel->setStatusBounced();
					break;
				case 'Delivery':
					if (!$emailModel->isUserComplained() and !$emailModel->isAlreadyBounced()) {
						$emailModel->setStatusDelivered();
					}
					break;
				default:
					trigger_error("Unknown notification type: $notification_type", E_USER_WARNING);
					return false;
			}
			return true;
		}
		return true;
	} // processSqsMessage
} // SqsMessageModel

