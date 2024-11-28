<?php declare(strict_types = 1);

$config = [];
$phpVersion = (int)getenv('ANALYSIS_PHP_VERSION') ?: PHP_VERSION_ID;
$config['parameters']['phpVersion'] = $phpVersion;

// Load PHP 8.2 specific config
if ($phpVersion >= 80200) {
   $config['includes'][] = 'email-editor-php-8-config.neon';
}

return $config;
