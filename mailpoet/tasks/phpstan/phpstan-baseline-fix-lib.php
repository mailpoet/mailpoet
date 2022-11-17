<?php declare(strict_types = 1);

$config = [];
$phpVersion = (int)getenv('ANALYSIS_PHP_VERSION') ?: PHP_VERSION_ID;
$config['parameters']['phpVersion'] = $phpVersion;

# PHPStan allows us to declare the currently reported list of errors as “the baseline” and cause it not being reported in subsequent runs.
# PHPStan will throw violations only in new and changed code.
# read more here: https://phpstan.org/user-guide/baseline
# we need to load different baseline file based on the php version
if ($phpVersion >= 70100 && $phpVersion < 80000) {
  $config['includes'][] = 'phpstan-7-baseline.neon';
} elseif ($phpVersion >= 80000 && $phpVersion < 80100) {
  $config['includes'][] = 'phpstan-8-baseline.neon';
} else {
  $config['includes'][] = 'phpstan-8.1-baseline.neon';
}

return $config;
