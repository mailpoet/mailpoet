<?php declare(strict_types = 1);

ini_set('max_execution_time', '900');

$dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__ . '/../..');
$dotenv->load();

// Load Composer autoload - we need to load it explicitly because we run the tests from the root of the project via /tests_env/vendor/bin/codecept
require_once(__DIR__ . '/../../vendor/autoload.php');
