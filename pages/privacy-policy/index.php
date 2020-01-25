<?php

require_once $_SERVER['DOCUMENT_ROOT'] .'/includes/config.php';

$pageShell = new StandardPageShell("Privacy Policy");
$pageShell->addCssFile('/pages/privacy-policy/privacy-policy.css');

?>
<div id="privacy-policy-text">
	<h3>Cookies</h3>
	<ul>
		<li>TypeTango uses a <a href="https://www.php.net/manual/en/intro.session.php" target="_blank">standard PHP session cookie</a> that has been made <a href="https://en.wikipedia.org/wiki/HTTP_cookie#Http-only_cookie" target="_blank">http-only</a>, <a href="https://en.wikipedia.org/wiki/HTTP_cookie#Same-site_cookie" target="_blank">same-site</a>, and <a href="https://en.wikipedia.org/wiki/HTTP_cookie#Secure_cookie" target="_blank">secure</a>. This cookie is used for temporarily storing anonymous data, such as confirmation messages between pages.</li>
		<li>When a user logs in, TypeTango sets an <a href="https://en.wikipedia.org/wiki/HTTP_cookie#Http-only_cookie" target="_blank">http-only</a> <a href="https://en.wikipedia.org/wiki/HTTP_cookie#Same-site_cookie" target="_blank">same-site</a> <a href="https://en.wikipedia.org/wiki/HTTP_cookie#Secure_cookie" target="_blank">secure</a> <a href="https://en.wikipedia.org/wiki/HTTP_cookie#Session_cookie" target="_blank">session cookie</a> in order to stay logged in until the user's browser is closed. This cookie is made <a href="https://en.wikipedia.org/wiki/HTTP_cookie#Persistent_cookie" target="_blank">persistent</a> if the "remember me" checkbox is checked at login. When a user logs out, this cookie is invalidated and deleted.</li>
		<li>TypeTango uses <a href="https://policies.google.com/technologies/partner-sites" target="_blank">Google Analytics</a> to track website traffic, which uses <a href="https://en.wikipedia.org/wiki/HTTP_cookie#Third-party_cookie" target="_blank">third-party cookies</a> as per <a href="https://policies.google.com/technologies/cookies" target="_blank">Google's cookie policy</a>.</li>
	</ul>
	<h3>If <a href="https://en.wikipedia.org/wiki/Do_Not_Track" target="_blank">"Do Not Track"</a> header is included</h3>
	<ul>
		<li>Google Analytics is not included.</li>
		<li>The "remember me" checkbox in the login form defaults to unchecked.</li>
	</ul>
	<h3>Registration</h3>
	<ul>
		<li>Passwords are <a href="http://php.net/manual/en/function.password-hash.php" target="_blank">not stored in a retrievable format</a>.</li>
		<li>Email addresses are kept private.</li>
		<li>Date of birth is not shown to other users, it is merely used to calculate age.</li>
		<li>Geographical coordinates and zip/postal code are not shown to other users; they are merely used to calculate distance between users.</li>
		<li>Other personal information such as city, gender, and personality type are requested in order to show to other users.</li>
	</ul>
	<h3>Sharing</h3>
	<ul>
		<li>Personal information will not be sold or shared with third parties.</li>
		<li>TypeTango publishes some anonymous statistics, such as type distribution of users.</li>
	</ul>
	<h3>Data Location</h3>
	<p>Running on <a href="https://aws.amazon.com/" target="_blank">Amazon Web Services</a> in Northern Virginia in the United States of America.</p>
	<h3>Contact Us</h3>
	<ul>
		<li>Send email to owner at typetango.com</li>
		<li>TypeTango<br>70-22 66th St., 3L<br>Glendale, NY 11385-6518</li>
	</ul>
</div>
