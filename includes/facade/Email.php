<?php

interface EmailInterface {
	static function sendEmailToDeveloperViaSendmail (array $email_params): void;
	static function sendEmailToClientViaAmazonSES (array $email_params): ?string;
	static function isAddressValid (string $email_address): bool;
	static function extractEmailAddress (string $header_string_value): string;
	static function getRelaySender (string $original_from_header, string $sender_email): string;
} // EmailInterface

class EmailException extends Exception {
	/* We can't send error notification emails if the problem is in the email class. */
} // EmailException

class Email implements EmailInterface {
	/**
	 * This method may not accept all the $email_params as Email::sendEmailToDeveloperViaSendmail(), for example 'cc' and 'bcc' are not currently supported via SES.
	 * $email_params keys: 'reply-to', 'list-unsubscribe', 'list-owner', 'resent-from', ('raw_email_body', 'content-type'), ('text', 'html', 'files')
	 * @param array $email_params
	 * @return null|string error message
	 * @throws EmailException
	 * @see Email::getRawEmail(), Email::sendEmailToDeveloperViaSendmail()
	 */
	public static function sendEmailToClientViaAmazonSES (array $email_params): ?string {
		try {
			$subject = $email_params['subject'] ?? $_SERVER['HTTP_HOST'];
			$to_email_address = $email_params['to'] ?? ERROR_RECIPIENT;
			$from_header_value = $email_params['from'] ?? 'TypeTango <'.DEFAULT_FROM.'>';

			if (SERVER_ROLE != 'live') {
				$to_email_address = ERROR_RECIPIENT;
			}
			if (is_array($from_header_value)) {
				foreach ($from_header_value as $from_email => $from_name) {
					$from_header_value = "$from_name <$from_email>";
					break;
				}
			}
			$raw_message = static::getRawEmail($subject, $to_email_address, $from_header_value, $email_params);
			$email_model_row = [
				'to_email'     => $to_email_address
				,'from_string' => $from_header_value
				,'subject'     => $subject
				,'raw_source'  => $raw_message
			];
			$ses_email_model_id = EmailModel::create($email_model_row, "$to_email_address: Queued email for sending via SES: $subject");
			if (!is_numeric($ses_email_model_id)) {
				$error_message = $ses_email_model_id;
				trigger_error($error_message, E_USER_WARNING);
				return $error_message;
			}
			new EmailModel($ses_email_model_id);
			EmailModel::sendQueuedEmails();
			return null;
		} catch (Exception $exception) {
			throw( new EmailException($exception->getMessage(), $exception->getCode(), $exception) );
		}
	} // sendEmailToClientViaAmazonSES


	/**
	 * $email_params keys: 'reply-to', 'list-unsubscribe', 'list-owner', 'resent-from', ('raw_email_body', 'content-type'), ('text', 'html', 'files')
	 * @param string $subject
	 * @param string $to_email_address
	 * @param string $from_header_value like "John Galt <john.galt@example.com>"
	 * @param array|string $email_params: String values get converted to ['text' => $email_params].
	 * @return string
	 * @see Email::sendEmailToClientViaAmazonSES()
	 */
	protected static function getRawEmail (string $subject, string $to_email_address, string $from_header_value, $email_params): string {
		if (is_string($email_params)) {
			$email_params = ['text' => $email_params];
		}
		$raw_message = '';
		$raw_message .= "MIME-Version: 1.0\n";
		$raw_message .= "Content-Language: en-US\n";
		$raw_message .= "Accept-Language: en-US\n";
		$raw_message .= "Subject: $subject\n";
		$raw_message .= "To: $to_email_address\n";
		$raw_message .= "From: $from_header_value\n";
		if (!empty($email_params['reply-to'])) {
			$raw_message .= "Reply-To: {$email_params['reply-to']}\n";
		}
		if (!empty($email_params['list-unsubscribe'])) {
			$raw_message .= "List-Unsubscribe: {$email_params['list-unsubscribe']}\n";
		}
		if (!empty($email_params['list-owner'])) {
			$raw_message .= "List-Owner: {$email_params['list-owner']}\n";
		}
		if (!empty($email_params['resent-from'])) {
			$raw_message .= "Resent-From: {$email_params['resent-from']}\n";
		}
		if (!empty($email_params['content-transfer-encoding'])) {
			$raw_message .= "Content-Transfer-Encoding: {$email_params['content-transfer-encoding']}\n";
		}


		if (isset($email_params['raw_email_body'])) {
			if (isset($email_params['content-type'])) {
				$raw_message .= "Content-Type: " .trim($email_params['content-type']) ."\n";
			}
			$raw_message .= "\n";
			$raw_message .= $email_params['raw_email_body'];
			return $raw_message;
		}

		{ // create $parts
			$parts = [];
			if (array_key_exists('text', $email_params)) {
				$text = $email_params['text'];
				$quoted_printable_text = quoted_printable_encode($text);
				$part = "Content-Type: text/plain; charset=UTF-8\n";
				$part .= "Content-Transfer-Encoding: quoted-printable\n";
				$part .= "\n$quoted_printable_text\n";
				$parts[] = $part;
			}
			if (array_key_exists('html', $email_params)) {
				$html = $email_params['html'];
				$quoted_printable_html = quoted_printable_encode($html);
				$part = "Content-type: text/html; charset=UTF-8\n";
				$part .= "Content-Transfer-Encoding: quoted-printable\n";
				$part .= "\n$quoted_printable_html\n";
				$parts[] = $part;
			}
			if (array_key_exists('files', $email_params)) {
				$files = $email_params['files'];
				foreach ($files as $file) {
					if (array_key_exists('content', $file)) {
						$file_content = $file['content'];
						if (!array_key_exists('name', $file)) {
							throw new InvalidArgumentException("Attachment content provided without attachment name.");
						}
						$file_name = $file['name'];
					} elseif (array_key_exists('path', $file)) {
						$file_path = $file['path'];
						$file_content = file_get_contents($file_path);
						$file_name = $file['name'] ?? basename($file_path);
					} elseif (array_key_exists('stream', $file)) {
						$file_content = stream_get_contents($file['stream']);
						if (!array_key_exists('name', $file)) {
							throw new InvalidArgumentException("Attachment stream provided without attachment name.");
						}
						$file_name = $file['name'];
					} else {
						throw new InvalidArgumentException("Attachment missing content/path/stream.");
					}
					$encoded_file_content = base64_encode($file_content);
					$part = "Content-Disposition: attachment; filename=\"$file_name\";\n";
					$part .= "Content-Transfer-Encoding: base64\n";
					$part .= "\n$encoded_file_content\n";
					$parts[] = $part;
				} // foreach
			} // files
		} // we now have $parts

		$has_only_text_section = (1 == count($parts) and array_key_exists('text', $email_params)) ? true : false;
		if ($has_only_text_section) {
			$raw_message .= $parts[0];
		} else {
			$mime_boundary = 'mime-boundary-' . time() . '-' . get_random_string(20, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789');
			$raw_message .= "Content-Type: multipart/mixed; boundary=\"$mime_boundary\"\n";
			$part_separator = "\n--$mime_boundary\n";
			$imploded_parts = implode($part_separator, $parts);
			$raw_message .= "$part_separator$imploded_parts\n$part_separator";
		}

		return $raw_message;
	} // getRawEmail


	/**
	 * @param array $email_params like
	 * [
	 *      'subject' => 'test'
	 *     ,'from'    => 'John Galt <john.galt@example.com>'
	 *     ,'from'    => ['john.galt@example.com' => 'John Galt']
	 *     ,'to'      => 'Howard Roark <howard.roark@example.com>'
	 *     ,'to'      => ["Francisco D'Anconia <frisco@example.com>", "Ragnar Danneskjold <robin.hood@example.com>"]
	 *     ,'to'      => ['frisco@example.com'=>"Francisco D'Anconia", 'robin.hood@example.com>'=>"Ragnar Danneskjold"]
	 *     ,'text'    => 'plaintext body test'
	 *     ,'html'    => '<p>html body test</p>'
	 *     ,'attachments' => [
	 *          [ 'file_name'=>'test1.txt', 'file_content'=>"This file's content was created from a string." ]
	 *         ,[ 'file_name'=>'test2.txt', 'file_location'=>'/path/to/file' ]
	 *         ,[ 'file_name'=>'test3.txt', 'file_stream'=>fopen('/path/to/file', 'r+') ]
	 *         ,[ 'file_name'=>'test4.txt', 'file_stream'=>STDIN]
	 *     ]
	 * ];
	 * When attachment files are passed as a stream, reading continues from the current position,
	 *     so you can fseek($handle, -1000, SEEK_END) to attach the tail end of a huge log file.
	 * @link http://pear.php.net/manual/en/package.mail.mail-mime.example.php
	 * @see Email::sendEmailToClientViaAmazonSES()
	 * @throws Exception
	 */
	public static function sendEmailToDeveloperViaSendmail (array $email_params): void {
		try {
			// http://pear.php.net/package/Mail/docs/1.4.1/Mail/Mail_mail.html
			require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/lib/Mail-1.4.1/Mail/mail.php';

			// http://pear.php.net/package/Mail_Mime/docs/1.10.1/Mail_Mime/_Mail_Mime-1.10.1---Mail---mime.php.html
			require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/lib/Mail_Mime-1.10.1/Mail/mime.php';

			// http://pear.php.net/package/Mail_Mime/docs/1.10.1/Mail_Mime/Mail_mime.html#var$build_params
			$build_params = [
				 'head_charset'  => 'utf8'
				,'head_encoding' => 'base64'
				,'text_charset'  => 'utf8'
				,'text_encoding' => 'base64'
				,'html_charset'  => 'utf8'
				,'html_encoding' => 'base64'
			];

			// http://pear.php.net/package/Mail_Mime/docs/1.10.1/Mail_Mime/Mail_mime.html#method__construct
			$mailMime = new Mail_mime($build_params);

			// Subject:  http://pear.php.net/package/Mail_Mime/docs/1.10.1/Mail_Mime/Mail_mime.html#methodsetSubject
			$mailMime->setSubject($email_params['subject'] ?? $_SERVER['HTTP_HOST']);

			// From:  http://pear.php.net/package/Mail_Mime/docs/1.10.1/Mail_Mime/Mail_mime.html#methodsetFrom
			$mailMime->setFrom((function($from) :string{
				if (is_string($from)) {
					return $from;
				} elseif (is_array($from)) {
					$from_email = key($from);
					$from_name = $from[$from_email];
					return "$from_name <$from_email>";
				} else {
					throw new InvalidArgumentException();
				}
			})($email_params['from'] ?? DEFAULT_FROM));

			// To:  http://pear.php.net/package/Mail_Mime/docs/1.10.1/Mail_Mime/Mail_mime.html#methodaddTo
			(function($to) use($mailMime) :void{
				if (is_string($to)) {
					$to_header_value = $to;
					$mailMime->addTo($to_header_value);
				} elseif (is_array($to)) {
					$array_is_numeric = array_key_exists(0, $to);
					if ($array_is_numeric) {
						foreach ($to as $to_header_value) {
							$mailMime->addTo($to_header_value);
						}
					} else {
						foreach ($to as $to_email => $to_name) {
							$to_header_value = "$to_name <$to_email>";
							$mailMime->addTo($to_header_value);
						}
					}
				} else {
					throw new InvalidArgumentException();
				}
			})($email_params['to'] ?? ERROR_RECIPIENT);

			// Plaintext Body:  http://pear.php.net/package/Mail_Mime/docs/1.10.1/Mail_Mime/Mail_mime.html#methodsetTXTBody
			if (array_key_exists('text', $email_params)) {
				$mailMime->setTXTBody($email_params['text']);
			}

			// HTML Body:  http://pear.php.net/package/Mail_Mime/docs/1.10.1/Mail_Mime/Mail_mime.html#methodsetHTMLBody
			if (array_key_exists('html', $email_params)) {
				$mailMime->setHTMLBody($email_params['html']);
			}

			// Attachments:  http://pear.php.net/package/Mail_Mime/docs/1.10.1/Mail_Mime/Mail_mime.html#methodaddAttachment
			if (array_key_exists('attachments', $email_params)) {
				(function($attachments) use($mailMime) :void{
					foreach ($attachments  as $attachment) {
						if (array_key_exists('file_location', $attachment)) {
							$file_location = $attachment['file_location'];
							$file_name     = $attachment['file_name'] ?? basename($file_location);
							$content_type  = mime_content_type($file_location);
							$mailMime->addAttachment($file_location, $content_type, $file_name, true);
						} elseif (array_key_exists('file_content', $attachment)) {
							$file_content = $attachment['file_content'];
							$file_name    = $attachment['file_name'];
							$content_type = get_mimetype_from_filename($file_name);
							$mailMime->addAttachment($file_content, $content_type, $file_name, false);
						} elseif (array_key_exists('file_stream', $attachment)) {
							$file_stream   = $attachment['file_stream'];
							$file_content  = fread($file_stream, 0);
							$file_name     = $attachment['file_name'];
							$content_type  = get_mimetype_from_filename($file_name);
							$mailMime->addAttachment($file_content, $content_type, $file_name, false);
						} else {
							throw new InvalidArgumentException();
						}
					}
				})($email_params['attachments']);
			} // attachments

			// http://pear.php.net/package/Mail_Mime/docs/1.10.1/Mail_Mime/Mail_mime.html#methodheaders
			$extra_headers = [];
			if (isset($email_params['reply-to'])) {
				$extra_headers['Reply-To'] = $email_params['reply-to'];
			}
			$headers = $mailMime->headers($extra_headers);

			// http://pear.php.net/package/Mail_Mime/docs/1.10.1/Mail_Mime/Mail_mime.html#methodget
			$body = $mailMime->get();

			// http://pear.php.net/package/Mail/docs/1.4.1/Mail/Mail_mail.html#methodsend
			$recipients = (function() use($headers) :string{
				$recipient_lists = [];
				if (isset($headers['To'])) {
					$recipient_lists[] = $headers['To'];
				}
				if (isset($headers['Cc'])) {
					$recipient_lists[] = $headers['Cc'];
				}
				if (isset($headers['Bcc'])) {
					$recipient_lists[] = $headers['Bcc'];
				}
				return implode(', ', $recipient_lists);
			})();

			// http://php.net/manual/en/function.mail.php
			$sendmail_command_line_option_for_envelope_sender = '-f'.ENVELOPE_MAIL_FROM;

			// http://pear.php.net/package/Mail/docs/1.4.1/Mail/Mail_mail.html#method__construct
			$mail = new Mail_mail($sendmail_command_line_option_for_envelope_sender);

			// http://pear.php.net/package/Mail/docs/1.4.1/Mail/Mail_mail.html#methodsend
			$outcome = $mail->send($recipients, $headers, $body);
			$success = $outcome === true ? true : false;
			if ($success) {
				return;
			}
			$pearError = gettype($outcome) == 'PEAR_Error' ? $outcome : null;
			if ($pearError) {
				throw new Exception($pearError->getMessage(), $pearError->getCode());
			}
			throw new Exception("Unexpected mail send outcome: " .print_r($outcome, true));
		} catch (Exception $exception) {
			throw( new EmailException($exception->getMessage(), $exception->getCode(), $exception) );
		}
	} // sendEmailToDeveloperViaSendmail


	/**
	 * @param string $original_from_header like "John Galt <john.galt@example.com>"
	 * @param string $sender_email like "relay@example.com"
	 * @return string from header like "John Galt john.galt at example.com <relay@example.com>"
	 */
	public static function getRelaySender (string $original_from_header, string $sender_email): string {
		return preg_replace(['/[<>"]/', '/ +/', '/@/'], ['', ' ', ' at '], $original_from_header) ." <$sender_email>";
	} // getRelaySender


	/**
	 * @param string $email_address
	 * @return bool Whether email address is valid.
	 */
	public static function isAddressValid (string $email_address): bool {
		$valid_pattern = '[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}'; // From http://www.regular-expressions.info/email.html
		return (bool)preg_match("/^$valid_pattern$/i", $email_address);
	} // isAddressValid


	/**
	 * @param string $header_string_value like "Vladimir Kornea" <vladkornea@gmail.com>
	 * @return string email address
	 */
	public static function extractEmailAddress (string $header_string_value): string {
		$header_string_value = preg_replace('/^.*</', '', $header_string_value);
		$email = preg_replace('/>.*$/', '', $header_string_value);
		return $email;
	} // extractEmailAddress
} // Email

