<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing


// Tools versions for PHP 7.4+
$composerVersion = '2.10.0';
$legacyPhpScoperVersion = '0.17.2'; // 0.17.2 supports PHP 7.4-8.3; fails on 8.4+ due to bundled thecodingmachine/safe v2
$phpScoperVersion = '0.18.19'; // 0.18.19 requires PHP 8.2+ and supports PHP 8.4 and 8.5
$legacyTracyVersion = '2.9.4'; // Tracy 2.9.4 supports PHP 7.4
$tracyVersion = '2.11.1'; // Tracy 2.11.0+ supports PHP 8.4 and 8.5

$phpScoperUrl = PHP_VERSION_ID >= 80400
  ? "https://github.com/humbug/php-scoper/releases/download/$phpScoperVersion/php-scoper.phar"
  : "https://github.com/humbug/php-scoper/releases/download/$legacyPhpScoperVersion/php-scoper.phar";

$tools = [
  "https://github.com/composer/composer/releases/download/$composerVersion/composer.phar" => 'composer.phar',
  $phpScoperUrl => 'php-scoper.phar',
  "https://github.com/nette/tracy/releases/download/v$legacyTracyVersion/tracy.phar" => 'tracy-legacy.phar',
  "https://github.com/nette/tracy/releases/download/v$tracyVersion/tracy.phar" => 'tracy.phar',
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

/**
 * Every CI job whose container PHP differs from the one that built the workspace
 * re-downloads at least one of these, because the cached copy records a different
 * URL. On PHP 8.4+ that is php-scoper, on every acceptance run. A single hiccup
 * reaching GitHub therefore killed the whole job before a test had run, so the
 * download is retried the way the CircleCI config already retries docker pulls.
 */
function downloadFile($url, $filePath, $fileInfoPath, $attempts = 5) {
  fwrite(STDERR, "Downloading '$url'...");
  if (file_exists($filePath) && file_exists($fileInfoPath) && file_get_contents($fileInfoPath) === $url) {
    fwrite(STDERR, " skipped (already exists).\n");
    return;
  }

  // Without a timeout a stalled connection hangs until CI kills the whole job.
  $context = stream_context_create([
    'http' => [
      'timeout' => 30,
      'follow_location' => 1,
      'user_agent' => 'mailpoet-tools-installer',
    ],
  ]);

  $lastError = 'unknown error';
  for ($attempt = 1; $attempt <= $attempts; $attempt++) {
    $contents = @file_get_contents($url, false, $context);

    // Write only once the body is fully in hand. Streaming straight to disk could
    // leave a truncated phar behind *and* record it as valid in the .info file,
    // so every later run would skip the download and use the broken copy.
    if (is_string($contents) && $contents !== '') {
      file_put_contents($filePath, $contents);
      file_put_contents($fileInfoPath, $url);
      chmod($filePath, 0755);
      fwrite(STDERR, " done.\n");
      return;
    }

    $error = error_get_last();
    $lastError = isset($error['message']) ? $error['message'] : 'empty response';
    if ($attempt < $attempts) {
      $delay = $attempt * 2;
      fwrite(STDERR, " attempt $attempt/$attempts failed, retrying in {$delay}s...");
      sleep($delay);
    }
  }

  throw new \RuntimeException("Could not download '$url' after $attempts attempts. Last error: $lastError");
}

// download all tools
foreach ($tools as $url => $path) {
  $pharPath = "$vendorDir/$path";
  $pharInfoPath = "$pharPath.info";

  downloadFile($url, $pharPath, $pharInfoPath);
}
