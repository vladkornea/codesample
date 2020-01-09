<?php

require_once 'AjaxPageShell.php';

interface AdminAjaxPageShellInterface extends AjaxPageShellInterface {
} // AjaxPageShellInterface

class AdminAjaxPageShell extends AjaxPageShell implements AdminAjaxPageShellInterface {
	function __construct () {
		parent::__construct();
		self::requireAdminAccess();
	}
} // AdminAjaxPageShell
