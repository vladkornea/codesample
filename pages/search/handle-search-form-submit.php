<?php
/**
 * There are problems with submitting the new search criteria via AJAX without refreshing the page. Browser caching causes original
 * form values to be loaded when the user clicks back from one of the profile pages. This could be defeated in three ways:
 * 1. Have JavaScript do a page reload after submitting the new search form parameters via AJAX.
 * 2. Tell the browser not to cache the search page.
 * 3. Do a conventional HTML <form method="post"> to this page.
 * I chose method 3 because it is the most straightforward, predictable, reliable.
 */

require_once $_SERVER['DOCUMENT_ROOT'] .'/includes/config.php';

$form_data = [];

// boolean search criteria
$form_data['exclude_contacted']      = !empty($_POST['exclude_contacted']);
$form_data['match_shared_negatives'] = !empty($_POST['match_shared_negatives']);
$form_data['must_have_description']  = !empty($_POST['must_have_description']);
$form_data['must_have_picture']      = !empty($_POST['must_have_picture']);
$form_data['must_like_my_gender']    = !empty($_POST['must_like_my_gender']);

// non-boolean search criteria
$form_data['gender'] = (function(): ?string {
	if (empty($_POST['genders'])) {
		return null;
	}
	if (count($_POST['genders']) == 2) {
		return null;
	}
	return $_POST['genders'][0];
})();
$form_data['mbti_types'] = $_POST['mbti_types'] ?? null;
$form_data['min_age']    = $_POST['min_age'] ?? null;
$form_data['max_age']    = $_POST['max_age'] ?? null;
$form_data['country']    = $_POST['country'] ?? null;

// non-boolean search criteria that can be toggled off
$form_data['max_distance']    = empty($_POST['has_distance_limit']) ? null : (!empty($_POST['max_distance'])    ? $_POST['max_distance']    : null);
$form_data['newer_than_days'] = empty($_POST['has_time_limit'])     ? null : (!empty($_POST['newer_than_days']) ? $_POST['newer_than_days'] : null);
$form_data['logged_in_within_days'] = empty($_POST['has_login_time_limit']) ? null : ($_POST['logged_in_within_days'] ?? null);

// save
$error_messages = Session::getUserModel()->setSearchCriteria($form_data);
if ($error_messages) {
	trigger_error("Error setting search criteria: " .print_r($error_messages, true), E_USER_WARNING);
}
header("Location: /search");

