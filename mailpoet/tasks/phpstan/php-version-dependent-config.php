<?php declare(strict_types = 1);

$config = [];
$phpVersion = (int)getenv('ANALYSIS_PHP_VERSION') ?: PHP_VERSION_ID;
$config['parameters']['phpVersion'] = $phpVersion;

# PHPStan gets smarter when runs on PHP8 and some type checks added because of PHP8 are reported as unnecessary when we run PHPStan on PHP7
# see https://github.com/phpstan/phpstan/issues/4060
if ($phpVersion < 80000) {
  $config['parameters']['ignoreErrors'][] = [
    'message' => '#^Cannot access offset \\(int\\|string\\) on array\\<string#',
    'path' => __DIR__ . '/../../lib/Features/FeatureFlagsController.php',
    'count' => 1,
  ];
}

return $config;
