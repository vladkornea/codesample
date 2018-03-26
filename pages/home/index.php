<?php

require_once $_SERVER['DOCUMENT_ROOT'] .'/includes/config.php';

$pageShell = new StandardPageShell("TypeTango");
$pageShell->addCssFile('/pages/home/home.css');
$pageShell->addJsFile('/pages/home/home.js');

$type_distribution = UserFinder::getTypeDistribution();
$pageShell->addJsVar('typeDistribution', $type_distribution);
$pageShell->addJsVar('totalUsers', array_sum($type_distribution));
$pageShell->addJsVar('ageDistributionChartData', UserFinder::getAgeDistributionGoogleChartData());
$pageShell->addJsVar('countryStatistics', UserFinder::getCountryStatistics());

?>
<div id="site-description">
	<p>TypeTango is a dating site based on Jungian Myers-Briggs/Keirsey personality theory.</p>
	<p>TypeTango's keyword matching system lets you find people based on shared values and interests.</p>
	<p>TypeTango is free to use. New contacts are limited to one per day.</p>
</div>
