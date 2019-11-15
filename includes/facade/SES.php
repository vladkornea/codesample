<?php
/**
 * API Reference: http://docs.aws.amazon.com/ses/latest/APIReference/Welcome.html
 * Developer Guide: http://docs.aws.amazon.com/ses/latest/DeveloperGuide/send-email-api.html
 * Authentication: http://docs.aws.amazon.com/ses/latest/DeveloperGuide/query-interface-authentication.html
 *
 * Search for calls to the getenv() function to see which environment variables need to be set to use this class.
 *
 * Class SES interacts with Amazon SES (Simple Email Service). This class should be used only by
 * the Email class; application code should use the Email class.
 *
 * http://docs.aws.amazon.com/ses/latest/DeveloperGuide/query-interface.html
 *
 * Test emails:
 * http://docs.aws.amazon.com/ses/latest/DeveloperGuide/mailbox-simulator.html
 * success@simulator.amazonses.com
 * bounce@simulator.amazonses.com
 * ooto@simulator.amazonses.com
 * complaint@simulator.amazonses.com
 * suppressionlist@simulator.amazonses.com
 */
interface SesInterface {
	function sendRawEmail (string $raw_message): array;
} // SesInterface

class SES implements SesInterface {
	protected $postParams = [];
	protected $requestTimestamp = 0;

	/**
	 * @link http://docs.aws.amazon.com/ses/latest/DeveloperGuide/send-email-raw.html
	 * @link http://docs.aws.amazon.com/ses/latest/APIReference/API_SendRawEmail.html
	 * @link http://docs.aws.amazon.com/ses/latest/DeveloperGuide/email-format.html
	 * @link http://docs.aws.amazon.com/ses/latest/APIReference/API_RawMessage.html
	 * @param string $raw_message
	 * @return array with keys: 'message_id', 'request_id', 'error_message'
	 * @throws Exception
	 */
	public function sendRawEmail (string $raw_message): array {
		$return_values = [
			 'message_id'    => null
			,'request_id'    => null
			,'error_message' => null
		];
		$this->postParams['RawMessage.Data'] = base64_encode($raw_message);
		$response_string = $this->sendPostRequest('SendRawEmail');
		/* @var SimpleXMLElement $response */ $response = simplexml_load_string($response_string);
		if (isset($response->Error)) {
			$return_values['error_message'] = "Error sending email via SES: {$response->Error->Message}";
			Email::sendEmailToDeveloperViaSendmail([
				 'reply-to' => DEFAULT_REPLY_TO
				,'to'       => ERROR_RECIPIENT
				,'subject'  => $return_values['error_message']
				,'text'     => print_r($response, true)
			]);
			return $return_values;
		}
		$return_values['message_id'] = (string)$response->SendRawEmailResult->MessageId;
		$return_values['request_id'] = (string)$response->ResponseMetadata->RequestId;
		return $return_values;
	} // sendRawEmail


	// http://docs.aws.amazon.com/AWSEC2/latest/APIReference/CommonParameters.html
	protected function sendPostRequest (string $action): string {
		$content = http_build_query($this->postParams, '', '&', PHP_QUERY_RFC3986);
		$headers = [];
		$headers[] = "Content-Type:application/x-www-form-urlencoded";
		$headers[] = "Host:" .$this->getEndpoint();
		$headers[] = "Date:" .$this->getDateHeaderValue();
		$headers[] = "Content-Length:" .strlen($content);
		$headers[] = "Connection:close";
		$headers[] = "X-Amzn-Authorization:" .$this->getAuthHeaderValue();
		$stream_context_options = [
			'http' => [
				'method'   => 'POST'
				,'content' => $content
				,'header'  => $headers
				,'ignore_errors' => true
			]
		];
		$url = 'https://' .$this->getEndpoint() .'?Action=' .urlencode($action);
		$response_text = file_get_contents($url, false, stream_context_create($stream_context_options));
		return $response_text;
	} // sendPostRequest


	// http://docs.aws.amazon.com/ses/latest/DeveloperGuide/query-interface-authentication.html
	protected function getAuthHeaderValue (): string {
		return "AWS3-HTTPS AWSAccessKeyId=".$this->getAccessKeyId().", Algorithm=HMACSHA256, Signature=".$this->getRequestSignature();
	} // getAuthHeaderValue


	// http://docs.aws.amazon.com/ses/latest/DeveloperGuide/query-interface-authentication.html
	protected function getRequestSignature (): string {
		return base64_encode($this->getStringToSign());
	} // getRequestSignature


	// http://docs.aws.amazon.com/ses/latest/DeveloperGuide/query-interface-authentication.html
	protected function getStringToSign (): string {
		return hash_hmac('sha256', $this->getDateHeaderValue(), $this->getSecretKey(), true);
	} // getStringToSign


	// http://docs.aws.amazon.com/ses/latest/DeveloperGuide/query-interface-authentication.html
	protected function getDateHeaderValue (): string {
		return date('r', $this->getRequestTimestamp());
	} // getDateHeaderValue


	protected function getRequestTimestamp (): int {
		if (!$this->requestTimestamp) {
			$this->requestTimestamp = time();
		}
		return $this->requestTimestamp;
	} // getRequestTimestamp


	protected function getSecretKey (): string {
		return getenv('AWS_SECRET_ACCESS_KEY');
	} // getSecretKey


	protected function getEndpoint (): string {
		return getenv('AWS_SES_ENDPOINT');
	} // getEndpoint


	// https://console.aws.amazon.com/iam/home?#security_credential
	protected function getAccessKeyId (): string {
		return getenv('AWS_ACCESS_KEY_ID');
	} // getAccessKeyId
} // SES

