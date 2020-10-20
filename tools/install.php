<?php

$tools = [
  'https://github.com/composer/composer/releases/download/1.10.13/composer.phar' => 'composer.phar',
  'https://github.com/humbug/php-scoper/releases/download/0.13.5/php-scoper.phar' => 'php-scoper.phar',
  'https://github.com/nette/tracy/releases/download/v2.7.2/tracy.phar' => 'tracy.phar',
];

// ensure installation in dev-mode only
$isDevMode = (bool)getenv('COMPOSER_DEV_MODE');
if (!$isDevMode) {
  fwrite(STDERR, "Skipping installing dev tools in non-dev mode.\n");
  return;
}

// prepare vendor dir
$vendorDir = __DIR__ . '/vendor';
if (!file_exists($vendorDir)) {
  mkdir($vendorDir);
}

// download all tools
foreach ($tools as $url => $path) {
  $pharPath = "$vendorDir/$path";
  $pharInfoPath = "$pharPath.info";

  fwrite(STDERR, "Downloading '$url'...");
  if (file_exists($pharPath) && file_exists($pharInfoPath) && file_get_contents($pharInfoPath) === $url) {
    fwrite(STDERR, " skipped (already exists).\n");
    continue;
  }

  $resource = fopen($url, 'r');
  if ($resource === false) {
    throw new \RuntimeException("Could not connect to '$url'");
  }
  file_put_contents($pharPath, $resource);
  file_put_contents($pharInfoPath, $url);
  chmod($pharPath, 0755);
  fwrite(STDERR, " done.\n");
}
