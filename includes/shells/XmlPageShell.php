<?php

require_once 'HttpPageShell.php';

interface XmlPageShellInterface extends HttpPageShellInterface {
} // XmlPageShellInterface

class XmlPageShell extends HttpPageShell implements XmlPageShellInterface {
	function __construct () {
		ob_start();
		parent::__construct();
	} // __construct

	function __destruct () {
		header("Content-Type: application/xml; charset=UTF-8");
		$ob_content = ob_get_clean();
		echo '<?xml version="1.0" encoding="UTF-8" standalone="yes" ?>', "\n";
		echo $ob_content;
		parent::__destruct();
	} // __destruct
} // XmlPageShell

