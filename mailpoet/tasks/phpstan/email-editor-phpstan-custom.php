<?php declare(strict_types = 1);

$config = [];
$phpVersion = (int)getenv('ANALYSIS_PHP_VERSION') ?: PHP_VERSION_ID;
$config['parameters']['phpVersion'] = $phpVersion;

// Load PHP 8 specific config
if ($phpVersion >= 80000) {
  $config['includes'][] = 'email-editor-php-8-config.neon';
}

return $config;
