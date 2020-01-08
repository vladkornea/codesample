<?php

require_once $_SERVER['DOCUMENT_ROOT'] .'/includes/config.php';

$pageShell = new StandardPageShell();
HttpPageShell::requireSessionLogin();
$pageShell->addCssFile('/pages/profile/profile.css');
$pageShell->addJsFile('/pages/profile/profile.js');

if (empty($_GET['username']) and empty($_GET['user_id'])) {
	$pageShell->setTitle("Edit Profile");
	include_photo_carousel_widget_and_data(Session::getUserId());
	include_edit_mode_profile_data(Session::getUserId());
} else {
	if (isset($_GET['username'])) {
		$username = $_GET['username'];
		$user_id = UserFinder::getIdFromUsername($username);
		if (!$user_id) {
			$pageShell->setTitle("Invalid Profile");
			$pageShell->addJsVar('isValidUser', false);
			return;
		}
		$userModel = new UserModel($user_id);
		$username_capitalization_is_wrong = $userModel->getUsername() != $username;
		if ($username_capitalization_is_wrong) {
			$new_page_params = $_GET;
			$new_page_params['username'] = $userModel->getUsername();
			$query_string = http_build_query($new_page_params);
			HttpPageShell::movedPermanently("?$query_string");
		}
	}
	if (isset($_GET['user_id'])) {
		$user_id = (int)$_GET['user_id'];
		$username = UserFinder::getUsernameFromId($user_id);
		if (!$username) {
			$pageShell->setTitle("Invalid Profile");
			$pageShell->addJsVar('isValidUser', false);
			return;
		}
	}
	$pageShell->setTitle("Profile of $username");
	include_photo_carousel_widget_and_data($user_id);
	include_view_mode_profile_data($user_id);
	include_conversation_with_logged_in_user($user_id);
	if ( Session::getUserModel()->getIsAdmin() ) {
		$pageShell->addJsFile( '/pages/profile/admin.js' );
	}
}

return; // functions below

function include_photo_carousel_widget_and_data (int $user_id): void {
	global $pageShell;
	$photo_carousel_data = (new UserModel($user_id))->getPhotoCarouselData();
	$pageShell->addJsVar('photoCarouselData', $photo_carousel_data);
} // include_photo_carousel_widget_and_data


function include_edit_mode_profile_data (int $user_id): void {
	global $pageShell;
	$pageShell->addJsFiles(['/js/countries.js', '/js/usa-states.js']);
	$profile_data = (function() use($user_id): array {
		$desired_fields = ['user_id', 'mbti_type', 'birth_day', 'birth_month', 'birth_year', 'gender', 'orientation', 'country', 'city', 'state', 'zip_code', 'latitude', 'longitude', 'would_relocate', 'body_type', 'height_in_in', 'weight_in_kg', 'have_children', 'want_children', 'self_described', 'lover_described', 'virtrades', 'share_keywords', 'positive_keywords', 'negative_keywords'];
		$userFinder = new UserFinder;
		$userFinder->setUserId($user_id);
		$userFinder->includeUsuallyExcludedUsers();
		$profile_data_array = $userFinder->getSearchResults($desired_fields);
		return $profile_data_array['users'][0];
	})();
	$pageShell->addJsVar('profileData', $profile_data);
} // include_edit_mode_profile_data


function include_view_mode_profile_data (int $user_id): void {
	global $pageShell;
	$pageShell->addJsFiles(['/js/lib/moment/2.19.2/moment-with-locales.js', '/js/lib/moment/2.19.2/moment-timezone-with-data.js']);
	$userModel = new UserModel($user_id);
	$next_send_allowed_at = $userModel->getWhenNextSendAllowed();
	$pageShell->addJsVar('nextSendAllowedAt', $next_send_allowed_at);
	$profile_data = (function() use($user_id): array {
		$desired_fields = ['user_id', 'username', 'would_relocate', 'body_type', 'height_in_in', 'weight_in_kg', 'have_children', 'want_children', 'self_described', 'lover_described', 'virtrades', 'last_visit', 'description', 'thumbnail_url', 'positive_keywords', 'negative_keywords', 'email_bouncing'];
		$userFinder = new UserFinder;
		$userFinder->setUserId($user_id);
		$userFinder->includeUsuallyExcludedUsers();
		$profile_data_array = $userFinder->getSearchResults($desired_fields);
		return $profile_data_array['users'][0];
	})();
	$keywords = $userModel->getKeywordsAsOther(Session::getUserModel());
	$profile_data = array_merge($profile_data, $keywords);
	$pageShell->addJsVar('profileData', $profile_data);

	$blocked_users = Session::getUserModel()->getBlockedUsers();
	$is_blocked = in_array($user_id, $blocked_users) ? true : false;
	$pageShell->addJsVar('isBlocked', $is_blocked);

	$reported_users = Session::getUserModel()->getReportedUsers();
	$is_reported = in_array($user_id, $reported_users) ? true : false;
	$pageShell->addJsVar('isReported', $is_reported);

	$pageShell->addJsVar('isDeactivated', $userModel->getIsDeactivated());

	$pageShell->addJsVar('theyBlockedUs', Session::getUserModel()->getIsBlockedByUserId($user_id));
	$pageShell->addJsVar('whyCannotViewUser', Session::getUserModel()->getWhyCannotViewUser($user_id));
} // include_view_mode_profile_data


function include_conversation_with_logged_in_user (int $user_id): void {
	global $pageShell;
	$conversation = Session::getUserModel()->getConversationWith($user_id);
	$pageShell->addJsVar('conversation', $conversation);
} // include_conversation

