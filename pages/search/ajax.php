<?php

require_once $_SERVER['DOCUMENT_ROOT'] .'/includes/config.php';

$pageShell = new AjaxPageShell;
HttpPageShell::requireSessionLogin();

$action = $_GET['action'];
switch ($action) {
	case 'get_search_results':
		handle_get_search_results();
		break;
	default:
		$pageShell->error("Undefined action: $action");
		break;
}

return; // functions below

function handle_get_search_results (): void {
	global $pageShell;
	$page = $_REQUEST['page'] ?? 1;
	$search_results = Session::getUserModel()->getSearchCriteriaModel()->getSearchResults($page);
	$pageShell->success($search_results);
} // handle_get_search_results

