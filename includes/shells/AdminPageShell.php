<?php

require_once 'HtmlPageShell.php';

interface AdminPageShellInterface extends HtmlPageShellInterface {
} // AdminPageShellInterface

class AdminPageShell extends HtmlPageShell implements AdminPageShellInterface {
	protected $keywords = "INTJ, ENTJ, INTP, ENTP, ISTJ, ESTJ, ISTP, ESTP, INFJ, ENFJ, INFP, ENFP, ISFJ, ESFJ, ISFP, ESFP, MBTI, Myers-Briggs, Carl Jung, David Keirsey, Personality Type, Personality Theory";

	function __construct (string $page_title = "TypeTango") {
		ob_start();
		parent::__construct($page_title);
		static::requireAdminAccess();
		$this->addCssFile('/css/generic.css');
		$this->addJsFile('/js/lib/jquery/jquery-3.4.1.min.js');
		$this->addJsFile('/js/functions.js');
		JavascriptErrorHandler::register($this);
		$this->addCssFile('/pages/admin/admin.css');
		$this->addJsFile('/pages/admin/admin.js');
	} // __construct


	/** The destructor compiles the final page. */
	function __destruct () {
		$ob_contents = ob_get_clean();
		?>
<div id="non-footer">
	<div id="header-placeholder"></div>
	<div id="header-container">
		<div id="header">
		<div id="header-left"></div>
			<h1 id="page-title"><?=htmlspecialchars($this->pageTitle)?></h1>
			<div id="header-right"></div>
		</div><!-- /#header -->
	</div><!-- /#header-container -->
	<div id="main-container">
		<div id="main">
<?=$this->getConfirmationMessageMarkupOnce()?>
<?=$ob_contents?>
		</div><!-- /#main -->
	</div><!-- /#main-container -->
	<ul id="navbar">
		<li><a href="/admin/settings">Settings</a></li>
		<li><a href="/admin/spammers">Spammers</a></li>
		<li><a href="/admin/db-upgrades">DB Upgrades</a></li>
		<li><a href="/admin/zip-code-coordinates">Zip Coordinates</a></li>
		<li><a href="/admin/recreate-images">Recreate Images</a></li>
		<li><a href="/admin/update-photo-dimensions">Update Photo Dimensions</a></li>
		<li><a href="/admin/purge-deleted-photos">Purge Deleted Photos</a></li>
		<li><a href="/admin/test">Test</a></li>
		<li><a href="/admin/phpinfo">phpinfo</a></li>
	</ul>
	<div id="footer-placeholder"></div>
</div><!-- /#non-footer -->
<div id="footer">
	<span id="copyright-notice">Copyright <a href="/contact" rel="help">Vladimir Kornea</a></span>
</div><!-- /#footer -->
<?php
		parent::__destruct();
	} // __destruct
} // AdminPageShell

