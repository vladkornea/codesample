<?php

require_once $_SERVER['DOCUMENT_ROOT'] .'/includes/config.php';

$pageShell = new StandardPageShell("Search");
HttpPageShell::requireSessionLogin();
$pageShell->addCssFile('/pages/search/search.css');
$pageShell->addJsFile('/pages/search/search.js');

$userModel = Session::getUserModel();
$why_not_allowed_to_search = $userModel->getWhyCannotViewUsers();
if ($why_not_allowed_to_search) {
	$pageShell->addJsVar('whyNotAllowedToSearch', $why_not_allowed_to_search);
	return;
}
$search_criteria = $userModel->getSearchCriteria();
$pageShell->addJsVar('searchFormData', $search_criteria);
$pageShell->addJsVar('firstPageSearchResults', Session::getUserModel()->getSearchCriteriaModel()->getSearchResults(1));
$pageShell->addJsVar('userCountryCode', Session::getUserModel()->getCountryCode());
$pageShell->addJsVar('countriesWithUsers', UserFinder::getCountriesWithUsers());

