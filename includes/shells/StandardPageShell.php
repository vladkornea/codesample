<?php

require_once 'HtmlPageShell.php';

interface StandardPageShellInterface extends HtmlPageShellInterface {
} // StandardPageShellInterface

class StandardPageShell extends HtmlPageShell implements StandardPageShellInterface {
	function __construct (string $page_title = "TypeTango") {
		ob_start();
		parent::__construct($page_title);
		$this->addCssFile('/css/generic.css');
		$this->addCssFile('/css/standard.css');
		$this->addJsFile('/js/lib/jquery/jquery-3.4.1.min.js');
		$this->addJsFile('/js/functions.js');
		JavascriptErrorHandler::register($this);
		$this->addCssFile('/widgets/login-widget/login-widget.css');
		$this->addJsFile('/widgets/login-widget/login-widget.js');
		if (SERVER_ROLE != 'live') {
			static::requireBasicHttpAuth();
		}
		$this->includeJavascriptSessionVars();
		$this->setKeywords('INTJ, ENTJ, INTP, ENTP, ISTJ, ESTJ, ISTP, ESTP, INFJ, ENFJ, INFP, ENFP, ISFJ, ESFJ, ISFP, ESFP, Myers-Briggs, MBTI, David Keirsey, Carl Jung, Personality Theory, Dating');
	} // __construct


	protected function includeJavascriptSessionVars (): void {
		$userModel = Session::getUserModel();
		if ($userModel) {
			$js_session_vars = [
				'username'    => $userModel->getUsername()
				,'email'      => $userModel->getEmail()
				,'user_id'    => $userModel->getId()
			];
			$this->addJsVar('sessionData', $js_session_vars);
		}
	} // includeJavascriptSessionVars


	protected function getAccountIsDeactivatedMessageMarkup (): string {
		if (!Session::getUserId()) {
			return '';
		}
		if (Session::getUserModel()->getIsDeactivated()) {
			return '<p class="error">Your profile is currently deactivated (other users cannot see it). Go to <a href="/account">your account page</a> to activate your profile.</p>';
		}
		return '';
	} // getAccountIsDeactivatedMessageMarkup


	protected function getGoogleAnalyticsCode (): string {
		$do_not_track_requested = ! empty( $_SERVER[ 'HTTP_DNT' ] );
		if ( $do_not_track_requested ) {
			return '';
		}
		$google_analytics_code = <<<HEREDOC
<script async src="https://www.googletagmanager.com/gtag/js?id=UA-1659325-1"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'UA-1659325-1');
</script>
HEREDOC;
		return $google_analytics_code;
	} // getGoogleAnalyticsCode


	protected function printStandardPageMarkup () {
		$ob_content = ob_get_clean(); ?>
<table id="page-structure"><tbody>
	<tr>
		<td id="left-panel"><div id="navbar"></div></td>
		<td id="main">
<?=$this->getAccountIsDeactivatedMessageMarkup()?>
<?=$this->getConfirmationMessageMarkupOnce()?>
<h1 id="page-title"><?=$this->pageTitle?></h1>
<?=$ob_content?>
		</td>
	</tr>
</tbody><tfoot>
	<tr><td colspan="2" id="footer">Myers-Briggs®, MBTI®, and Myers-Briggs Type Indicator® are trademarks of CPP, Inc. TypeTango is not affiliated with CPP, Inc. © <a href="https://www.kornea.com/resume" target="_blank">Vladimir Kornea</a><a id="privacy-policy-page-footer-link" href="/privacy-policy">Privacy Policy</a></td></tr>
</tfoot></table>
<?php
	} // printStandardPageMarkup


	function __destruct () {
		$this->printStandardPageMarkup();
		echo $this->getGoogleAnalyticsCode();
		parent::__destruct();
		$userModel = Session::getUserModel();
		if ($userModel) {
			$userModel->setLastVisit();
		}
	} // __destruct
} // StandardPageShell

