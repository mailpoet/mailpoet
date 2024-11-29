<?php declare(strict_types = 1);

$config = [];
$phpVersion = (int)getenv('ANALYSIS_PHP_VERSION') ?: PHP_VERSION_ID;
$config['parameters']['phpVersion'] = $phpVersion; // passes the PHP version to PHPStan

return $config;
