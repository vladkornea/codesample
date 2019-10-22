<?php
/**
 * This page is used to create and alter database tables. It's basically a web interface for the `DbUpgrades` class.
 */
require_once $_SERVER['DOCUMENT_ROOT'] .'/includes/config.php';

$first_run_ever = !DbUpgrades::isInstalled();
if ($first_run_ever) {
	install_database();
}

$pageShell = new AdminPageShell("DB Upgrades");

if (!empty($_POST['submitted'])) {
	DbUpgrades::performUpgrades();
}

echo '<form method="post" action="', htmlspecialchars($_SERVER['SCRIPT_NAME']), '"><input type="hidden" name="submitted" value="1"><input type="submit" value="Run Upgrades"></form>';

echo get_html_table_markup(DbUpgrades::getUpgradesSummary());

return; // functions below

function install_database (): void {
	$pageShell = new HttpPageShell;
	HttpPageShell::requireBasicHttpAuth();
	DbUpgrades::performUpgrades();
	echo "<p>Installed DbUpgrades. Refresh page to log in as admin.</p>";
	exit;
} // install_database

