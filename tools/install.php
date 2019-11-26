<?php

$tools = [
  'https://github.com/composer/composer/releases/download/1.9.0/composer.phar' => 'composer.phar',
  'https://github.com/humbug/php-scoper/releases/download/0.11.4/php-scoper.phar' => 'php-scoper.phar',
  'https://github.com/nette/tracy/releases/download/v2.7.1/tracy.phar' => 'tracy.phar',
];

// ensure installation in dev-mode only
$is_dev_mode = (bool)getenv('COMPOSER_DEV_MODE');
if (!$is_dev_mode) {
  fwrite(STDERR, "Skipping installing dev tools in non-dev mode.\n");
  return;
}

// prepare vendor dir
$vendor_dir = __DIR__ . '/vendor';
if (!file_exists($vendor_dir)) {
  mkdir($vendor_dir);
}

// download all tools
foreach ($tools as $url => $path) {
  $phar_path = "$vendor_dir/$path";
  $phar_info_path = "$phar_path.info";

  fwrite(STDERR, "Downloading '$url'...");
  if (file_exists($phar_path) && file_exists($phar_info_path) && file_get_contents($phar_info_path) === $url) {
    fwrite(STDERR, " skipped (already exists).\n");
    continue;
  }

  $resource = fopen($url, 'r');
  if ($resource === false) {
    throw new \RuntimeException("Could not connect to '$url'");
  }
  file_put_contents($phar_path, $resource);
  file_put_contents($phar_info_path, $url);
  chmod($phar_path, 0755);
  fwrite(STDERR, " done.\n");
}
