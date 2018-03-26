<?php

require_once $_SERVER['DOCUMENT_ROOT'] .'/includes/config.php';

$pageShell = new StandardPageShell("Help");
$pageShell->addCssFile('/pages/help/help.css');

?>

<h4>How do TypeTango keywords work?</h4>
<p>There are two sets of keywords: <i>positives</i> and <i>negatives</i>. To view and edit these keywords, go to your profile.</p>

<p>If you love to dance and dislike sarcasm, you'd enter <i>dancing</i> in positives and <i>sarcasm</i> in negatives. When you search, all your keywords are compared to all other users' keywords, and users are shown in order of total match score.</p>

<hr>

<h4>How do keyword weights work?</h4>

<p>Keyword weights control the relative importance of your keywords when searching. The higher the weight you assign to a keyword, the more it will increase or decrease your match score. If you assign the weight of 10 to <i>dancing</i> and only give 2 to <i>sarcasm</i>, dancing will be considered five times more important than sarcasm. The maximum keyword weight is 250.</p>

<hr>

<h4>How will a person know that I want to contact them?</h4>

<p>TypeTango sends an email to the person you are attempting to contact.</p>

<hr>

<h4>Books</h4>
<ul class="structural">
<li><a href="https://www.amazon.com/exec/obidos/tg/detail/-/089106074X" target="_blank"><i>Gifts Differing: Understanding Personality Type</i></a> by Isabel Briggs Myers, Peter B. Myers<br>Good introduction to the entire theory, along with statistical data</li>
<li><a href="https://www.amazon.com/exec/obidos/tg/detail/-/0877739870" target="_blank"><i>Personality Type: An Owner's Manual</i></a> by Lenore Thomson<br>Functions in their dominant role</li>
<li><a href="https://www.amazon.com/exec/obidos/tg/detail/-/0891061703" target="_blank"><i>Was That Really Me?: How Everyday Stress Brings Out Our Hidden Personality</i></a> by Naomi L. Quenk<br>Functions in their inferior role</li>
<li><a href="https://www.amazon.com/exec/obidos/tg/detail/-/1885705026" target="_blank"><i>Please Understand Me II: Temperament, Character, Intelligence</i></a> by David Keirsey<br>Categorizes the sixteen types into four groups (SJ Guardians, SP Artisans, NF Idealists, NT Rationals).</li>
</ul>

<hr>

<h4>How do I contact someone at TypeTango?</h4>
<p>Send an email to owner at <?=EMAIL_DOMAIN?></p>

