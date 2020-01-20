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
		$this->addJsFile('/js/standard.js');
		if (SERVER_ROLE != 'live') {
			static::requireBasicHttpAuth();
		}
		$this->includeJavascriptSessionVars();
		$this->setKeywords('INTJ, ENTJ, INTP, ENTP, ISTJ, ESTJ, ISTP, ESTP, INFJ, ENFJ, INFP, ENFP, ISFJ, ESFJ, ISFP, ESFP, Myers-Briggs, MBTI, David Keirsey, Carl Jung, Personality Theory, Temperament, Dating');
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
<table id="page-structure" class="structural<?=Session::getUserId() ? ' logged_in' : ''?>"><thead>
	<tr>
		<td><a id="home-link" href="/"><img src="/images/typetango-logo.gif" alt="TypeTango" width="150" height="80"></a><a id="keyword-suggestions-link" href="/keyword-suggestions">💡 Keyword Suggestions</a><!-- <a id="type-distribution-link" href="/">📊 Type Distribution</a>--></td>
		<td><a href="/help">❓ Help</a> <?php
			if ( Session::getUserId() ) {
				?><a id="account-link" href="/account">🔑 My Account</a> <a id="logout-link" href="/logout">🚪 Log Out</a></td><?php
			} else {
				?><a id="account-link" href="/create-account"><!--📋-->📝 Create Account</a> <a id="login-link" href="/login">🚪 Log In</a></td><?php
			} ?>
	</tr><tr>
		<td colspan="2"><?php
		if ( Session::getUserId() ) {
			?><a id="edit-profile-link" href="/profile">✎ Edit Profile</a> <a id="view-profile-link" href="/profile?user_id=<?=Session::getUserId()?>">👀 View Profile</a> <a id="search-link" href="/search">🔍 Search</a> <a id="contacts-link" href="/contacts">✉ Contacts</a><?php
		} ?>
		</td>
	</tr>
</thead><tbody>
	<tr>
		<td id="main" colspan="2"><?=$this->getAccountIsDeactivatedMessageMarkup()?><?=$this->getConfirmationMessageMarkupOnce()?><?=$ob_content?></td>
	</tr>
</tbody><tfoot>
	<tr><td colspan="2" id="footer"><div id="footer-copyright-notice">Myers-Briggs®, MBTI®, and Myers-Briggs Type Indicator® are trademarks of <a href="https://www.myersbriggs.org" target="_blank">the Myers &amp; Briggs Foundation</a>.<br>Keirsey Temperament Sorter®, Guardian®, Artisan®, and Rational® are trademarks of Prometheus Nemesis Book Company.<br>TypeTango is not affiliated with either organization. © <?=date('Y')?> <a href="https://www.kornea.com/resume" target="_blank">Vladimir Kornea</a>. All rights reserved. Logo by <a href="https://www.easchweitzer.com/" target="_blank">Elise Schweitzer</a>.</div><a id="privacy-policy-page-footer-link" href="/privacy-policy">🔒 Cookies and Privacy Policy</a>
</td></tr>
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

