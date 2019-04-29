<?php
require_once $_SERVER['DOCUMENT_ROOT'] .'/includes/config.php';
?>
User-agent: *
Disallow: /admin
Disallow: /api
Disallow: /ajax
Disallow: */ajax
Disallow: <?=PROFILE_PHOTOS_REMOTE_DIR?>

# Some URLs that are frequently visited according to the log but do not exist, see if putting them here will reduce the visits
Disallow: keirsey-temperament-sorter-test.php
Disallow: login.php
Disallow: outgoing.php
Disallow: up.php
Disallow: wp-login.php
Disallow: intj-personality.php
Disallow: entj-personality.php
Disallow: intp-personality.php
Disallow: entp-personality.php
Disallow: istj-personality.php
Disallow: estj-personality.php
Disallow: istp-personality.php
Disallow: estp-personality.php
Disallow: infj-personality.php
Disallow: enfj-personality.php
Disallow: infp-personality.php
Disallow: enfp-personality.php
Disallow: isfj-personality.php
Disallow: esfj-personality.php
Disallow: isfp-personality.php
Disallow: esfp-personality.php

