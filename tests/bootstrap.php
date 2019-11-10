<?php
date_default_timezone_set('UTC');

$root = dirname(__DIR__) . DIRECTORY_SEPARATOR;

$loader = require $root . 'vendor/autoload.php';
/*
$loader->add('Flow\\', $root . 'src/');
$loader->add('Flow\\Test\\', $root . 'tests/');

$loader->add('Flow\\', $root . 'dev/');
$loader->add('Flow\\Test\\', $root . 'dev/tests/');
*/

$loader->add('Flow\\TestApp\\', $root . 'tests/test_app/src/');

require_once $root . 'src/functions.php';
