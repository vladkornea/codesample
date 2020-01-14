<?php

require_once $_SERVER['DOCUMENT_ROOT'] .'/includes/config.php';

$pageShell = new StandardPageShell("Privacy Policy");
$pageShell->addCssFile('/pages/privacy-policy/privacy-policy.css');
?>
<div class="text" id="privacy-policy-text">
<a href="https://en.wikipedia.org/wiki/HTTP_cookie" target="_blank">HTTP cookies</a> are used to keep you logged in, and by <a href="https://policies.google.com/technologies/partner-sites" target="_blank">Google Analytics</a> unless "<a href="https://en.wikipedia.org/wiki/Do_Not_Track" target="_blank">Do Not Track</a>" is requested.

Passwords are <a href="http://php.net/manual/en/function.password-hash.php" target="_blank">not stored in a retrievable format</a>. Email addresses are kept private. Other personal information, such as city and birthday, are useful for TypeTango searches; only logged-in users can see or deduce some of this information. Personal information will not be sold or shared with third parties; TypeTango publishes some anonymous statistics, such as the user type distribution.

<a href="https://en.wikipedia.org/wiki/HTTPS" target="_blank">HTTPS</a> encryption is required for all requests.
</div>
