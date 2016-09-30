<?php

////
// Basic Setup
////

date_default_timezone_set('UTC');

////
// Autoloader
////
require_once __DIR__.'/../src/MP/Framework/AutoLoader.php';

$classLoader = new MP\Framework\AutoLoader(__DIR__.'/../src');
$classLoader->register();

// Dependency injection
$di = new \MP\Framework\DI();

// Do anything!
