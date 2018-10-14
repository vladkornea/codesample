<?php

require_once $_SERVER['DOCUMENT_ROOT'] .'/includes/config.php';

$pageShell = new StandardPageShell("Privacy Policy");
$pageShell->addCssFile('/pages/privacy-policy/privacy-policy.css');
?>
<div class="text" id="privacy-policy-text">Email addresses are kept private.

Only logged-in users can see your profile.

You can <a href="/account">deactivate your profile</a> at any time.

Passwords are <a href="http://php.net/manual/en/function.password-hash.php" target="_blank">not stored in a retrievable format</a>.

The web server requires secure <a href="https://en.wikipedia.org/wiki/HTTPS" target="_blank">HTTPS</a> encryption for all requests.

Running on Amazon Web Services (AWS).</div>
