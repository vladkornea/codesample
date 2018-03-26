<?php
/**
 * Class SQS
 * Console: https://console.aws.amazon.com/sqs/home
 * Service Index: http://aws.amazon.com/sqs/
 * Documentation Index: https://aws.amazon.com/documentation/sqs
 * API Reference: http://docs.aws.amazon.com/AWSSimpleQueueService/latest/APIReference/Welcome.html
 * Regions and Endpoints: http://docs.aws.amazon.com/general/latest/gr/rande.html#sqs_region
 *
 * Authentication: http://docs.aws.amazon.com/AWSSimpleQueueService/latest/SQSDeveloperGuide/Query_QueryAuth.html
 * "Amazon SQS supports signature version 4."
 *
 * Access Keys: http://docs.aws.amazon.com/AWSSimpleQueueService/latest/SQSDeveloperGuide/AWSCredentials.html
 * "For API access, you need an access key ID and secret access key. Use IAM user access keys instead of AWS root account access keys."
 *
 * SDK Docs: http://docs.aws.amazon.com/aws-sdk-php/v2/guide/service-sqs.html
 */
interface SqsInterface {
	const FEEDBACK_QUEUE = 'sent';
	const INBOUND_QUEUE = 'received';
	static function getMessages (string $queue_name): ?array;
	static function deleteMessageFromQueue (string $receipt_handle, string $queue_name): bool;
} // SqsInterface

require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/lib/aws/aws.phar';
use Aws\Sqs\SqsClient;

class SQS implements SqsInterface {
	/**
	 * @param string $queue_name SQS::FEEDBACK_QUEUE or SQS::INBOUND_QUEUE
	 * @param int $long_polling_seconds
	 * @return array
	 * @see SQS::getQueueUrl()
	 */
	public static function getMessages (string $queue_name, int $long_polling_seconds = 0): ?array {
		$sqsClient = static::getClient();
		$receiveMessageResult = $sqsClient->receiveMessage([
			'QueueUrl' => static::getQueueUrl($queue_name),
			'WaitTimeSeconds' => $long_polling_seconds,
			'MaxNumberOfMessages' => 10,
		]);
		$messages = $receiveMessageResult->get('Messages');
		return $messages;
	} // getMessages


	/**
	 * @param string $queue_name static::FEEDBACK_QUEUE or static::INBOUND_QUEUE
	 * @return string
	 * @see SQS::getSentQueueUrl(), SQS::getReceivedQueueUrl()
	 */
	protected static function getQueueUrl (string $queue_name): string {
		$queue_urls = [
			'dev'   => [
				static::FEEDBACK_QUEUE => 'https://sqs.us-east-1.amazonaws.com/545506226046/typetango-dev-feedback'
				,static::INBOUND_QUEUE  => 'https://sqs.us-east-1.amazonaws.com/545506226046/typetango-dev-inbound'
			]
			,'test' => [
				static::FEEDBACK_QUEUE => 'https://sqs.us-east-1.amazonaws.com/545506226046/typetango-test-feedback'
				,static::INBOUND_QUEUE  => 'https://sqs.us-east-1.amazonaws.com/545506226046/typetango-test-inbound'
			]
			,'live' => [
				static::FEEDBACK_QUEUE => 'https://sqs.us-east-1.amazonaws.com/545506226046/typetango-live-feedback'
				,static::INBOUND_QUEUE  => 'https://sqs.us-east-1.amazonaws.com/545506226046/typetango-live-inbound'
			]
		];
		$queue_url = $queue_urls[SERVER_ROLE][$queue_name];
		return $queue_url;
	} // getQueueUrl


	/**
	 * @link http://docs.aws.amazon.com/aws-sdk-php/v2/guide/service-sqs.html#creating-a-client
	 * @return Aws\Sqs\SqsClient
	 */
	protected static function getClient (): SqsClient {
		$sqsClient = new SqsClient([
			'region'  => static::getRegionName(),
			'version' => '2012-11-05', // http://docs.aws.amazon.com/aws-sdk-php/v3/api/class-Aws.Sqs.SqsClient.html
//			'debug'   => SERVER_ROLE == 'dev',
			'retries' => 0,
		]);
		return $sqsClient;
	} // getClient


	/**
	 * @link http://docs.aws.amazon.com/aws-sdk-php/v3/api/api-sqs-2012-11-05.html#deletemessage
	 * @param string $receipt_handle
	 * @param string $queue_name
	 * @return bool success
	 */
	public static function deleteMessageFromQueue (string $receipt_handle, string $queue_name): bool {
		try {
			static::getClient()->deleteMessage([
				'QueueUrl' => static::getQueueUrl($queue_name),
				'ReceiptHandle' => $receipt_handle,
			]);
			return true;
		} catch (Exception $exception) {
			trigger_error($exception->getMessage(), E_USER_WARNING);
			return false;
		}
	} // deleteMessageFromQueue

	/** @link http://docs.aws.amazon.com/general/latest/gr/rande.html#sqs_region */
	protected static function getRegionName (): string {
		return 'us-east-1';
	} // getRegionName
} // SQS

