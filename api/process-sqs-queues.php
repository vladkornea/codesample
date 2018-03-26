<?php

require_once $_SERVER['DOCUMENT_ROOT'] .'/includes/config.php';

handle_sns_subscription_confirmation();

process_sqs_messages(SQS::FEEDBACK_QUEUE, 1);
process_sqs_messages(SQS::INBOUND_QUEUE, 1);
EmailModel::sendQueuedEmails();

return; // functions below

/**
 * Exits scripts if this is a SubscriptionConfirmation request.
 * @link http://docs.aws.amazon.com/sns/latest/dg/SendMessageToHttp.html
 */
function handle_sns_subscription_confirmation (): void {
	$json_encoded_post_data = file_get_contents("php://input");
	$post_data = json_decode($json_encoded_post_data, true);
	if ($post_data['Type'] == 'SubscriptionConfirmation') {
		file_get_contents($post_data['SubscribeURL']);
		HistoricEventModel::create(['event_synopsis' => "Confirmed SNS Subscription"]);
		exit;
	}
} // handle_sns_subscription_confirmation

// $queue_name can be SQS::FEEDBACK_QUEUE or SQS::INBOUND_QUEUE
function process_sqs_messages (string $queue_name, int $long_polling_seconds = 2): void {
	for ($i = 0; $i < 20; $i++) {
		if ($queue_name == SQS::INBOUND_QUEUE) {
			if (!GlobalSettings::inboundQueueProcessing()) {
				trigger_error("SQS inbound queue processing is disabled.", E_USER_NOTICE);
				return;
			}
		}
		if ($queue_name == SQS::FEEDBACK_QUEUE) {
			if (!GlobalSettings::feedbackQueueProcessing()) {
				trigger_error("SQS feedback queue processing is disabled.", E_USER_NOTICE);
				return;
			}
		}
		$sqs_messages = SQS::getMessages($queue_name, $long_polling_seconds);
		if (!$sqs_messages) {
			break;
		}
		foreach ($sqs_messages as $sqs_message) {
			process_sqs_message($sqs_message, $queue_name);
		}
	}
} // process_sqs_messages

// $queue_name can be SQS::FEEDBACK_QUEUE or SQS::INBOUND_QUEUE
function process_sqs_message (array $sqs_message, string $queue_name): void {
	$db_sqs_message_id = SqsMessageModel::getLocalDbIdFromAwsMessageId($sqs_message['MessageId']);
	if ($db_sqs_message_id) {
		SQS::deleteMessageFromQueue($sqs_message['ReceiptHandle'], $queue_name);
		$sqsMessage = new SqsMessageModel($db_sqs_message_id);
		$sqsMessage->markDeleted();
		trigger_error("SQS message deleted because it was already processed (\$db_sqs_message_id=$db_sqs_message_id).", E_USER_WARNING);
		return;
	}
	$db_sqs_message_id = SqsMessageModel::create([
		'message_id'     => $sqs_message['MessageId'],
		'receipt_handle' => $sqs_message['ReceiptHandle'],
		'md5_of_body'    => $sqs_message['MD5OfBody'],
		'body'           => $sqs_message['Body'],
	], "{$sqs_message['MessageId']}: SQS message fetched.");
	if (!is_numeric($db_sqs_message_id)) {
		$error_message = $db_sqs_message_id;
		trigger_error("Error processing SQS message: $error_message", E_USER_WARNING);
		return;
	}
	$sqsMessage = new SqsMessageModel($db_sqs_message_id);
	$success = $sqsMessage->processSqsMessage();
	if ($success === false) {
		trigger_error("Failed to process SQS message $db_sqs_message_id.", E_USER_WARNING);
		return;
	} else {
		$event_synopsis = is_string($success) ? $success : null;
		SQS::deleteMessageFromQueue($sqs_message['ReceiptHandle'], $queue_name);
		$sqsMessage->markDeleted($event_synopsis);
	}
} // process_sqs_message

