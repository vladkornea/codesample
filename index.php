<?php
// .htaccess routes requests for `example.com/home` to `pages/home/index.php` in the document root, but nobody actually requests
// `example.com/home`, they request just `example.com` instead, which routes to here, making this an alias for the home page:
require $_SERVER['DOCUMENT_ROOT'] .'/pages/home/index.php';

