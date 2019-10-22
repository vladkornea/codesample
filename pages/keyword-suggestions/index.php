<?php

require_once $_SERVER['DOCUMENT_ROOT'] .'/includes/config.php';

$pageShell = new StandardPageShell("Keyword Suggestions");
$pageShell->addCssFile('/pages/keyword-suggestions/keyword-suggestions.css');

$cache_filename = __DIR__.'/cache/cached-keyword-suggestions-markup.html';

// serve cached file if it hasn't expired
if (file_exists($cache_filename) and filemtime($cache_filename) > strtotime('-10 minutes')) {
	readfile($cache_filename);
	return;
}

$markup = '';

// keyword disagreements
$query = '
	select min_keywords as Disagreement, keyword as Keyword from (
		(select keyword, count(*) as total_tables, min(total_keywords) as min_keywords from
			(
				select keyword, count(*) as total_keywords from positive_keywords group by keyword
				union
				select keyword, count(*) as total_keywords from negative_keywords group by keyword
			) as keyword_counts
		group by keyword
		having total_tables = 2) as keyword_appearing_in_both_tables
	)
	where min_keywords >= 20
	order by min_keywords desc, keyword
	limit 100';
$markup .= get_html_table_markup(DB::getTable($query));

// popular positives
$query = '
	select count(*) as Total, keyword as Positives
	from positive_keywords
	group by keyword
	having Total > 100
	order by Total desc, keyword
	limit 100';
$markup .= get_html_table_markup(DB::getTable($query));

// popular negatives
$query = '
	select count(*) as Total, keyword as Negatives
	from negative_keywords
	group by keyword
	having Total > 100
	order by Total desc, keyword
	limit 100';
$markup .= get_html_table_markup(DB::getTable($query));

// print output
$bytes_written = file_put_contents($cache_filename, $markup, LOCK_EX);
if ($bytes_written) {
	readfile($cache_filename);
} else {
	trigger_error("No bytes written when attempting to create new cache file.", E_USER_WARNING);
	if ($markup) {
		echo $markup;
	} else {
		trigger_error("No markup generated.", E_USER_WARNING);
		if (file_exists($cache_filename)) {
			readfile($cache_filename);
		} else {
			trigger_error("No old cache file.", E_USER_WARNING);
		}
	}
}

