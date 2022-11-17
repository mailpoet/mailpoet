<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

$tools = [
  'https://github.com/composer/composer/releases/download/2.3.5/composer.phar' => 'composer.phar',
  'https://github.com/humbug/php-scoper/releases/download/0.17.2/php-scoper.phar' => 'php-scoper.phar',
  'https://github.com/nette/tracy/releases/download/v2.9.1/tracy.phar' => 'tracy.phar',
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

function downloadFile($url, $filePath, $fileInfoPath) {
  fwrite(STDERR, "Downloading '$url'...");
  if (file_exists($filePath) && file_exists($fileInfoPath) && file_get_contents($fileInfoPath) === $url) {
    fwrite(STDERR, " skipped (already exists).\n");
    return;
  }

  $resource = fopen($url, 'r');
  if ($resource === false) {
    throw new \RuntimeException("Could not connect to '$url'");
  }
  file_put_contents($filePath, $resource);
  file_put_contents($fileInfoPath, $url);
  chmod($filePath, 0755);
  fwrite(STDERR, " done.\n");
}

// download all tools
foreach ($tools as $url => $path) {
  $pharPath = "$vendorDir/$path";
  $pharInfoPath = "$pharPath.info";

  downloadFile($url, $pharPath, $pharInfoPath);
}
