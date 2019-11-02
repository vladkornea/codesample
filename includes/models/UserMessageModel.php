<?php

require_once 'BaseModel.php';

interface UserMessageModelInterface extends BaseModelInterface {
	static function create (array $form_data): array;
	function send (): ?string;
} // UserMessageModelInterface

class UserMessageModel extends BaseModel implements UserMessageModelInterface {
	use UserMessageTraits;

	public static function create (array $form_data): array {
		// init
		$error_messages = [];
		$db_row = [];

		// message_text
		$message_text = trim($form_data['message_text'] ?? '');
		if (!$message_text) {
			$error_messages['message_text'] = "Empty message text.";
		} else {
			$db_row['message_text'] = $message_text;
		}

		// from_user_id
		if (empty($form_data['from_user_id'])) {
			$error_messages['from_user_id'] = "Empty from_user_id.";
		} else {
			$from_user_id = $form_data['from_user_id'];
			if (!is_numeric($from_user_id)) {
				$error_messages['from_user_id'] = "Non-Numeric from_user_id.";
			} else {
				$db_row['from_user_id'] = (int)$from_user_id;
			}
		}

		// to_user_id
		if (empty($form_data['to_user_id'])) {
			$error_messages['to_user_id'] = "Empty to_user_id.";
		} else {
			$to_user_id = $form_data['to_user_id'];
			if (!is_numeric($to_user_id)) {
				$error_messages['to_user_id'] = "Non-Numeric to_user_id.";
			} else {
				$db_row['to_user_id'] = (int)$to_user_id;
			}
		}

		// return
		if ($error_messages) {
			return ['error_messages' => $error_messages, 'user_message_id' => null];
		} else {
			$user_message_id = parent::create($db_row);
			return ['user_message_id' => $user_message_id, 'error_messages' => null];
		}
	} // create


	public function update (array $form_data) {
		trigger_error("Not implemented.", E_USER_WARNING);
	} // update


	public function send (): ?string {
		$from_user_id = $this->commonGet('from_user_id');
		$to_user_id   = $this->commonGet('to_user_id');
		$message_text = $this->commonGet('message_text');

		$fromUserModel = new UserModel($from_user_id);
		if (!$fromUserModel->getWhetherCanSendMessages()) {
			return "User cannot send messages.";
		}
		$toUserModel = new UserModel($to_user_id);
		$to_username = $toUserModel->getUsername();

		$from_username = $fromUserModel->getUsername();
		$from_possessive_pronoun = $fromUserModel->getPossessivePronoun();
		$from_user_profile_url = 'https://' .$_SERVER['HTTP_HOST'] . '/profile?username=' .urlencode($from_username);

		$basics = $fromUserModel->getUserSummary();

		$email_params = [];
		$email_params['reply-to'] = NO_REPLY_EMAIL;
		$email_params['to'] = $toUserModel->getEmail();
		$email_params['subject'] = "New message from $from_username";
		$email_params['text'] = "Hello $to_username,\n\nYou have received a new TypeTango message from $from_username ($basics). To view $from_possessive_pronoun profile and reply to the message, go to the following URL:\n\n$from_user_profile_url\n\nHere is the message:\n\n$message_text";
		Email::sendEmailToClientViaAmazonSES($email_params);
		return null;
	} // send
} // UserMessageModel

