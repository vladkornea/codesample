<?php

require_once $_SERVER['DOCUMENT_ROOT'] .'/includes/config.php';

$pageShell = new StandardPageShell("Help");
$pageShell->addCssFile('/pages/help/help.css');

?>
<div id="help-page-text">

<h3>How do TypeTango keywords work?</h3>
<p>There are two sets of keywords: <i>positives</i> and <i>negatives</i>. To view and edit these keywords, go to your profile.</p>

<p>If you love to dance and dislike sarcasm, you'd enter <i>dancing</i> in positives and <i>sarcasm</i> in negatives. When you search, all your keywords are compared to all other users' keywords, and users are shown in order of total match score.</p>

<h3>How do keyword weights work?</h3>

<p>Keyword weights control the relative importance of your keywords when searching. The higher the weight you assign to a keyword, the more it will increase or decrease your match score. If you assign the weight of 10 to <i>dancing</i> and only give 2 to <i>sarcasm</i>, dancing will be considered five times more important than sarcasm. The maximum keyword weight is 250.</p>

<h3>How will a person know that I want to contact them?</h3>

<p>TypeTango sends an email to the person you are attempting to contact.</p>

<h3>Books</h3>
<ul><li itemscope itemtype="https://schema.org/Book">
	<a itemprop="name" rel="external nofollow" href="https://www.amazon.com/exec/obidos/tg/detail/-/089106074X" target="_blank">
		Gifts Differing: Understanding Personality Type
	</a>
	<div>
		by <span itemprop="author" itemscope itemtype="http://schema.org/Person">
			<a itemprop="name" rel="external nofollow" href="https://en.wikipedia.org/wiki/Isabel_Briggs_Myers" target="_blank">
				Isabel Briggs Myers
			</a>
		</span>
		and <span itemprop="author" itemscope itemtype="http://schema.org/Person">
			<a itemprop="name" rel="external nofollow" href="https://www.goodreads.com/author/show/434031.Peter_B_Myers" target="_blank">
				Peter B. Myers
			</a>
		</span>
	</div>
	<div itemprop="review" itemscope itemtype="https://schema.org/Review">
		<span itemprop="reviewBody">
			Good introduction to the entire theory, along with statistical data.
		</span>
	</div>
</li><li itemscope itemtype="https://schema.org/Book">
	<a itemprop="name" rel="external nofollow" href="https://www.amazon.com/exec/obidos/tg/detail/-/0877739870" target="_blank">
		Personality Type: An Owner's Manual
	</a>
	<div>
		by <span itemprop="author" itemscope itemtype="http://schema.org/Person">
			<a itemprop="name" rel="external nofollow" href="https://www.goodreads.com/author/show/302550.Lenore_Thomson" target="_blank">
				Lenore Thomson
			</a>
		</span>
	</div>
	<div itemprop="review" itemscope itemtype="https://schema.org/Review">
		<span itemprop="reviewBody">
			Functions in their dominant role.
		</span>
	</div>
</li><li itemscope itemtype="https://schema.org/Book">
	<a itemprop="name" rel="external nofollow" href="https://www.amazon.com/exec/obidos/tg/detail/-/0891061703" target="_blank">
		Was That Really Me?: How Everyday Stress Brings Out Our Hidden Personality
	</a>
	<div>
		by <span itemprop="author" itemscope itemtype="http://schema.org/Person">
			<a itemprop="name" rel="external nofollow" href="https://www.goodreads.com/author/show/433616.Naomi_L_Quenk" target="_blank">
				Naomi L. Quenk
			</a>
		</span>
	</div>
	<div itemprop="review" itemscope itemtype="https://schema.org/Review">
		<span itemprop="reviewBody">
			Functions in their inferior role.
		</span>
	</div>
</li><li itemscope itemtype="https://schema.org/Book">
	<a itemprop="name" rel="external nofollow" href="https://www.amazon.com/exec/obidos/tg/detail/-/1885705026" target="_blank">
		Please Understand Me II: Temperament, Character, Intelligence
	</a>
	<div>
		by <span itemprop="author" itemscope itemtype="http://schema.org/Person">
			<a itemprop="name" rel="external nofollow" href="https://en.wikipedia.org/wiki/David_Keirsey" target="_blank">
				David Keirsey
			</a>
		</span>
	</div>
	<div itemprop="review" itemscope itemtype="https://schema.org/Review">
		<span itemprop="reviewBody">
			Categorizes the sixteen types into <abbr title="SJ types (ESFJ, ESTJ, ISFJ, ISTJ)">Guardians</abbr>, <abbr title="SP types (ESFP, ESTP, ISFP, ISTP)">Artisans</abbr>, <abbr title="NF types (ENFJ, ENFP, INFJ, INFP)">Idealists</abbr>, and <abbr title="NT types (ENTJ, ENTP, INTJ, INTP)">Rationals</abbr>.
		</span>
	</div>
</li></ul>

<h3>Contact Us</h3>
<p>Send an email to owner at <?=EMAIL_DOMAIN?></p>

</div>
