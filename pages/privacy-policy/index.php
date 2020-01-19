<?php

require_once $_SERVER['DOCUMENT_ROOT'] .'/includes/config.php';

$pageShell = new StandardPageShell("Privacy Policy");
$pageShell->addCssFile('/pages/privacy-policy/privacy-policy.css');
?>
<div id="privacy-policy-text">
	<h3>Cookies</h3>

	<ul>

		<li>TypeTango sets a <a href="https://en.wikipedia.org/wiki/HTTP_cookie#Secure_cookie" target="_blank">secure HttpOnly session cookie</a> to store across subsequent anonymous requests temporary data such as notifications.</li>

		<li>TypeTango sets a <a href="https://en.wikipedia.org/wiki/HTTP_cookie#Secure_cookie" target="_blank">secure HttpOnly session cookie</a> to keep you logged in until your browser closes. This cookie is made <a href="https://en.wikipedia.org/wiki/HTTP_cookie#Persistent_cookie" target="_blank">persistent</a> if "Remember me" is checked at login. Clicking "Log Out" removes this cookie.</li>

		<li>TypeTango uses <a href="https://policies.google.com/technologies/partner-sites" target="_blank">Google Analytics</a> to track website traffic, which uses third-party cookies as per <a href="https://policies.google.com/technologies/cookies" target="_blank">Google's cookie policy</a>.</li>

	</ul>

	<h3>"Do Not Track" header</h3>

	<p>If your browser includes a "<a href="https://en.wikipedia.org/wiki/Do_Not_Track" target="_blank">Do Not Track</a>" HTTP header (like during <a href="https://en.wikipedia.org/wiki/Private_browsing" target="_blank">private browsing</a>), TypeTango will not include <a href="https://policies.google.com/technologies/partner-sites" target="_blank">Google Analytics</a>.</p>

	<h3>Registration</h3>

	<p>Passwords are <a href="http://php.net/manual/en/function.password-hash.php" target="_blank">not stored in a retrievable format</a>. Email addresses are kept private. Your date of birth is used to calculate your age (which is used in TypeTango searches), but is not shown to other users. Other personal information like your city and gender are requested in order to show to other users.</p>

	<h3>Sharing</h3>

	<p>Personal information will not be sold or shared with third parties. TypeTango publishes some anonymous statistics, such as type distribution of users.</p>

	<h3>Data Location</h3>

	<p>Running on <a href="https://aws.amazon.com/" target="_blank">Amazon Web Services</a> in Northern Virginia in the United States of America.</p>

	<h3>Contact Us</h3>

	<p>Send email to owner at typetango.com</p>

	<p>TypeTango<br>
		70-22 66th St 3L<br>
		Glendale, NY 11385</p>
</div>
