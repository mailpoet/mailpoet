<?php
declare(strict_types = 1);
# PHPStan gets smarter when runs on PHP8 and some type checks added because of PHP8 are reported as unnecessary when we run PHPStan on PHP7
# see https://github.com/phpstan/phpstan/issues/4060
$config = [];

$config['parameters']['phpVersion'] = 80000;

if (PHP_VERSION_ID < 80000) {
  $config['parameters']['ignoreErrors'][] = [
    'message' => '#^Else branch is unreachable because ternary operator condition is always true#',
    'path' => __DIR__ . '/../../lib/AdminPages/Pages/Forms.php',
    'count' => 1,
  ];
  $config['parameters']['ignoreErrors'][] = [
    'message' => '#^Else branch is unreachable because ternary operator condition is always true#',
    'path' => __DIR__ . '/../../lib/AdminPages/Pages/Newsletters.php',
    'count' => 1,
  ];
  $config['parameters']['ignoreErrors'][] = [
    'message' => '#^Strict comparison using === between PDOStatement and false will always evaluate to false#',
    'path' => __DIR__ . '/../../lib/Doctrine/Driver/PDOConnection.php',
    'count' => 1,
  ];
  $config['parameters']['ignoreErrors'][] = [
    'message' => '#^Strict comparison using === between string and false will always evaluate to false#',
    'path' => __DIR__ . '/../../lib/Doctrine/Driver/PDOConnection.php',
    'count' => 1,
  ];
  $config['parameters']['ignoreErrors'][] = [
    'message' => '#^Cannot access offset \\(int\\|string\\) on array\\|false#',
    'path' => __DIR__ . '/../../lib/Features/FeatureFlagsController.php',
    'count' => 1,
  ];
}

return $config;
