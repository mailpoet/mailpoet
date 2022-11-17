<?php declare(strict_types = 1);

ini_set('max_execution_time', '900');

$dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__ . '/../..');
$dotenv->load();
