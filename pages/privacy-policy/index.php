<?php

require_once $_SERVER['DOCUMENT_ROOT'] .'/includes/config.php';

$pageShell = new StandardPageShell("Privacy Policy");
$pageShell->addCssFile('/pages/privacy-policy/privacy-policy.css');
?>
<div class="text" id="privacy-policy-text">Email addresses are kept private.

Only logged-in users can see your profile.

<a href="https://en.wikipedia.org/wiki/HTTP_cookie" target="_blank">HTTP cookies</a> are used to keep you logged in.

<a href="https://policies.google.com/technologies/partner-sites" target="_blank">Google Analytics</a> is utilized unless "<a href="https://en.wikipedia.org/wiki/Do_Not_Track" target="_blank">Do Not Track</a>" is requested.

Passwords are <a href="http://php.net/manual/en/function.password-hash.php" target="_blank">not stored in a retrievable format</a>.

<a href="https://en.wikipedia.org/wiki/HTTPS" target="_blank">HTTPS</a> encryption required for all requests.
</div>
