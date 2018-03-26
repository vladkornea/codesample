<?php

require_once $_SERVER['DOCUMENT_ROOT'] .'/includes/config.php';

$pageShell = new AjaxPageShell;
HttpPageShell::requireSessionLogin();

$action = $_GET['action'];
switch ($action) {
	case 'set_search_criteria':
		handle_set_search_criteria();
		break;
	case 'get_search_results':
		handle_get_search_results();
		break;
	default:
		$pageShell->error("Undefined action: $action");
		break;
}

return; // functions below

function handle_set_search_criteria (): void {
	global $pageShell;
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

	$error_messages = Session::getUserModel()->setSearchCriteria($form_data);
	if ($error_messages) {
		$pageShell->error($error_messages);
	}
	$search_criteria = Session::getUserModel()->getSearchCriteria();
	$success_data = ['searchFormData' => $search_criteria];
	$pageShell->success($success_data);
} // handle_set_search_criteria


function handle_get_search_results (): void {
	global $pageShell;
	$page = $_REQUEST['page'] ?? 1;
	$search_results = Session::getUserModel()->getSearchCriteriaModel()->getSearchResults($page, 7);
	$pageShell->success($search_results);
} // handle_get_search_results

