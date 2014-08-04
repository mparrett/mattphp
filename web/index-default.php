<?php

////
// Basic Setup
////

date_default_timezone_set('UTC');

////
// Autoloaders
////

require_once __DIR__.'/../src/MP/Framework/AutoLoader.php';

$classLoader = new \MP\Framework\AutoLoader(__DIR__.'/../src');
$classLoader->register();

////
// Bootstrap the framework
////

$req = \MP\Framework\Request::createFromGlobals();
$response = new \MP\Framework\Response();

$di = new \MP\Framework\DI();

$app = new \MP\Framework\App($di);

////
// Configure the app
////

// App-specific configuration
// (session, database, etc)
require_once 'configure.php';
require_once 'controllers.php';

////
// Process the request
////

$app->handle($req, $response);
