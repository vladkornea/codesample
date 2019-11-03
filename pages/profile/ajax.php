<?php

require_once $_SERVER['DOCUMENT_ROOT'] .'/includes/config.php';

$pageShell = new AjaxPageShell;
HttpPageShell::requireSessionLogin();

$action = $_GET['action'];
switch ($action) {
	case 'save_profile':
		handle_save_profile();
		break;
	case 'upload_photo':
		handle_upload_photo();
		break;
	case 'delete_photo':
		handle_delete_photo();
		break;
	case 'edit_photo':
		handle_edit_photo();
		break;
	case 'set_photo_order':
		handle_set_photo_order();
		break;
	case 'update_positive_keyword':
		handle_update_positive_keyword();
		break;
	case 'update_negative_keyword':
		handle_update_negative_keyword();
		break;
	case 'save_positive_keywords':
		handle_save_positive_keywords();
		break;
	case 'save_negative_keywords':
		handle_save_negative_keywords();
		break;
	case 'send_message':
		handle_send_message();
		break;
	case 'block':
		handle_block_user();
		break;
	case 'unblock':
		handle_unblock_user();
		break;
	case 'report':
		handle_report_user();
		break;
	case 'unreport':
		handle_unreport_user();
		break;
	default:
		$pageShell->error("Undefined action: $action");
		break;
}

return; // functions below

function handle_save_negative_keywords () {
	global $pageShell;
	$keywords = $_POST['keywords'] ?? [];
	$userModel = Session::getUserModel();
	$userModel->setKeywords('negative', $keywords);
	$new_ordered_keywords = $userModel->getNegativeKeywords();
	$pageShell->success(['keywords' => $new_ordered_keywords]);
} // handle_save_negative_keywords

function handle_save_positive_keywords () {
	global $pageShell;
	$keywords = $_POST['keywords'] ?? [];
	$userModel = Session::getUserModel();
	$userModel->setKeywords('positive', $keywords);
	$new_ordered_keywords = $userModel->getPositiveKeywords();
	$pageShell->success(['keywords' => $new_ordered_keywords]);
} // handle_save_positive_keywords

function handle_update_positive_keyword (): void {
	global $pageShell;
	$old_keyword = $_POST['old_keyword'] ?? null;
	$new_keyword = $_POST['new_keyword'] ?? null;
	$new_weight  = (int)($_POST['new_keyword_weight'] ?? 1);
	$error_message = Session::getUserModel()->saveKeyword('positive', $old_keyword, $new_keyword, $new_weight);
	if ($error_message) {
		$pageShell->error($error_message);
	}
	$pageShell->success();
} // handle_update_positive_keyword

function handle_update_negative_keyword (): void {
	global $pageShell;
	$old_keyword = $_POST['old_keyword'] ?? null;
	$new_keyword = $_POST['new_keyword'] ?? null;
	$new_weight  = (int)($_POST['new_keyword_weight'] ?? 1);
	$error_message = Session::getUserModel()->saveKeyword('negative', $old_keyword, $new_keyword, $new_weight);
	if ($error_message) {
		$pageShell->error($error_message);
	}
	$pageShell->success();
} // handle_update_negative_keyword

function handle_block_user (): void {
	global $pageShell;
	if (empty($_POST['user_id'])) {
		$pageShell->error("Missing user_id");
	}
	Session::getUserModel()->blockUser($_POST['user_id']);
	$pageShell->success();
} // handle_block_user

function handle_unblock_user (): void {
	global $pageShell;
	if (empty($_POST['user_id'])) {
		$pageShell->error("Missing user_id");
	}
	Session::getUserModel()->unblockUser($_POST['user_id']);
	$pageShell->success();
} // handle_unblock_user

function handle_report_user (): void {
	global $pageShell;
	if (empty($_POST['user_id'])) {
		$pageShell->error("Missing user_id");
	}
	Session::getUserModel()->reportUser($_POST['user_id']);
	$pageShell->success();
} // handle_report_user

function handle_unreport_user (): void {
	global $pageShell;
	if (empty($_POST['user_id'])) {
		$pageShell->error("Missing user_id");
	}
	Session::getUserModel()->unreportUser($_POST['user_id']);
	$pageShell->success();
} // handle_unreport_user

function handle_send_message (): void {
	global $pageShell;
	$from_user_id = Session::getUserId();
	$to_user_id = (int)($_POST['to_user_id'] ?? 0);
	if (!$to_user_id) {
		$pageShell->error('Non-Numeric `to_user_id`');
	}
	$fromUserModel = Session::getUserModel();
	if ($fromUserModel->getIsBlockedByUserId($to_user_id)) {
		$pageShell->error('Blocked by user.');
	}

	$message_text = trim($_POST['message_text'] ?? '');
	if (empty($message_text)) {
		$pageShell->error('Empty `message_text`');
	}
	$previous_contact_exists = $fromUserModel->getWhetherPreviousContactExistsWith($to_user_id);
	if (!$previous_contact_exists) {
		$next_send_allowed_at_datetime = $fromUserModel->getWhenNextSendAllowed();
		if ($next_send_allowed_at_datetime) {
			$pageShell->error("Cannot send message to new user yet.", ['next_send_allowed_at_timestamp' => $next_send_allowed_at_datetime]);
		}
	}
	$db_row = [
		 'to_user_id'   => $to_user_id
		,'from_user_id' => $from_user_id
		,'message_text' => $message_text
	];
	['error_messages' => $error_messages, 'user_message_id' => $user_message_id] = UserMessageModel::create($db_row);
	if ($error_messages) {
		$pageShell->error($error_messages);
	}
	$userMessageModel = new UserMessageModel($user_message_id);
	$error_message = $userMessageModel->send();
	if ($error_message) {
		$pageShell->error($error_message);
	}
	$conversation = $fromUserModel->getConversationWith($to_user_id);
	$pageShell->success(['conversation' => $conversation]);
} // handle_send_message

function handle_set_photo_order (): void {
	global $pageShell;
	$photo_order = $_POST['photo_order'];
	if (!$photo_order) {
		$pageShell->error("Missing photo_order");
	}
	Session::getUserModel()->setPhotoOrder($photo_order);
	$pageShell->success();
} // handle_set_photo_order

function handle_delete_photo (): void {
	global $pageShell;
	$photo_id = (int)$_POST['photo_id'];
	if (!$photo_id) {
		$pageShell->error("Invalid photo_id");
	}
	$photoModel = new PhotoModel($photo_id);
	$photoModel->delete();
	$pageShell->success(['photoCarouselData' => Session::getUserModel()->getPhotoCarouselData()]);
} // handle_delete_photo

function handle_edit_photo (): void {
	global $pageShell;
	$photo_id = (int) $_POST['photo_id'];
	if ( ! $photo_id ) {
		$pageShell->error( "Missing photo_id" );
	}
	$photoModel = new PhotoModel( $photo_id );
	$photo_belongs_to_this_user = $photoModel->getUserId() == Session::getUserModel()->getId();
	if ( ! $photo_belongs_to_this_user ) {
		$error_message = "Photo does not belong to this user.";
		$pageShell->error( $error_message );
	}
	$photo_data = [
		'caption'      => $_POST['caption'],
		'rotate_angle' => $_POST['rotate_angle'],
	];
	$photoModel->update( $photo_data );
	$output_data = [ 'photoCarouselData' => Session::getUserModel()->getPhotoCarouselData() ];
	$pageShell->success( $output_data );
} // handle_edit_photo

function handle_upload_photo (): void {
	global $pageShell;
	$photo_files = $_FILES['photo_files'];
	foreach ($photo_files['error'] as $i => $error) {
		if ($error) {
			trigger_error("Error uploading file.", E_USER_WARNING);
			$pageShell->error("Error uploading file.");
		}
		$file_name = $photo_files['name'][$i];
		$file_location = $photo_files['tmp_name'][$i];
		$form_data = [
			'user_id'            => Session::getUserModel()->getId()
			,'file'              => $file_location
			,'original_filename' => $file_name
		];
		$photo_id = PhotoModel::create($form_data);
		if (!is_numeric($photo_id)) {
			$error_messages = $photo_id;
			$pageShell->error($error_messages);
		}
	}
	$pageShell->success(['photoCarouselData' => Session::getUserModel()->getPhotoCarouselData()]);
} // handle_upload_photo

function handle_save_profile (): void {
	global $pageShell;
	$profile_data = [
		'mbti_type'        => $_POST['mbti_type'] ?? null
		,'birth_month'     => $_POST['birth_month'] ?? null
		,'birth_year'      => $_POST['birth_year'] ?? null
		,'birth_day'       => $_POST['birth_day'] ?? null
		,'gender'          => $_POST['gender'] ?? null
		,'orientation'     => $_POST['orientation'] ?? null
		,'height_in_in'    => $_POST['height_in_in'] ?? null
		,'weight_in_kg'    => $_POST['weight_in_kg'] ?? null
		,'body_type'       => $_POST['body_type'] ?? null
		,'have_children'   => $_POST['have_children'] ?? null
		,'want_children'   => $_POST['want_children'] ?? null
		,'country'         => $_POST['country'] ?? null
		,'city'            => $_POST['city'] ?? null
		,'state'           => $_POST['state'] ?? null
		,'zip_code'        => $_POST['zip_code'] ?? null
		,'latitude'        => $_POST['latitude'] ?? null
		,'longitude'       => $_POST['longitude'] ?? null
		,'would_relocate'  => $_POST['would_relocate'] ?? null
		,'virtrades'       => $_POST['virtrades'] ?? null
		,'self_described'  => $_POST['self_described'] ?? null
		,'lover_described' => $_POST['lover_described'] ?? null
		,'share_keywords'  => $_POST['share_keywords'] ?? null
	];
	$error_messages = Session::getUserModel()->update($profile_data);
	if ($error_messages) {
		$pageShell->error($error_messages);
	}
	$pageShell->success();
} // handle_save_profile

