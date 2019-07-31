<?php

$tools = [
  'https://github.com/nette/tracy/releases/download/v2.6.4/tracy.phar' => 'tracy.phar',
];

// ensure installation in dev-mode only
$is_dev_mode = (bool) getenv('COMPOSER_DEV_MODE');
if (!$is_dev_mode) {
  fwrite(STDERR, "Skipping installing dev tools in non-dev mode.\n");
  return;
}

// download all tools
foreach ($tools as $url => $path) {
  fwrite(STDERR, "Downloading '$url'...");
  $resource = fopen($url, 'r');
  if ($resource === false) {
    throw new \RuntimeException("Could not connect to '$url'");
  }
  file_put_contents(__DIR__ . "/$path", $resource);
  fwrite(STDERR, " done.\n");
}
