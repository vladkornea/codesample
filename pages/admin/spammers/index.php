<?php

require_once $_SERVER['DOCUMENT_ROOT'] .'/includes/config.php';

$pageShell = new AdminPageShell("Spammers");
$pageShell->addJsFile('/pages/admin/spammers/spammers.js');
$pageShell->addCssFile('/pages/admin/spammers/spammers.css');

$known_spammers = UserFinder::getSpammers();
$pageShell->addJsVar('knownSpammers', $known_spammers);

$suspected_spammers = UserFinder::getSuspectedSpammers();
$pageShell->addJsVar('suspectedSpammers', $suspected_spammers);

