<?php

require_once $_SERVER['DOCUMENT_ROOT'] .'/includes/config.php';

$pageShell = new AdminPageShell( "Recreate Images" );
$pageShell->addJsFile( '/pages/admin/recreate-images/recreate-images.js' );
$pageShell->addCssFile( '/pages/admin/recreate-images/recreate-images.css' );

unset( $_SESSION[ 'filenames' ] );
