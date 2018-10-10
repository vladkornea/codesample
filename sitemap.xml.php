<?php

require_once $_SERVER['DOCUMENT_ROOT'] .'/includes/config.php';

$pageShell = new XmlPageShell;

$urllist_path = $_SERVER['DOCUMENT_ROOT'] .'/urllist.txt';
$urllist_handle = fopen($urllist_path, 'r');
if (!$urllist_handle) {
	die;
}

echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
while ($url = trim(fgets($urllist_handle))) {
	echo '
		<url>
			<loc>' .htmlspecialchars($url) .'</loc>
			<changefreq>daily</changefreq>
		</url>'
	;
}
echo "\n</urlset>";

