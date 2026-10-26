<?php

/**
 * Keeps the WooCommerce PHPStan stubs (php-stubs/woocommerce-stubs) equal to MailPoet’s
 * minimum required WooCommerce version.
 *
 * php .github/workflows/scripts/sync_woocommerce_stubs.php          Updates the pin and the lock file.
 * php .github/workflows/scripts/sync_woocommerce_stubs.php --check  Exits with 1 when they differ.
 */

const STUBS_PACKAGE = 'php-stubs/woocommerce-stubs';

$rootDir = dirname(__DIR__, 3);
$pluginFile = $rootDir . '/mailpoet/mailpoet.php';
$phpstanDir = $rootDir . '/mailpoet/tasks/phpstan';
$composerJsonFile = $phpstanDir . '/composer.json';

function fail(string $message): void {
  fwrite(STDERR, $message . "\n");
  exit(1);
}

function readFileOrFail(string $path): string {
  $content = file_get_contents($path);
  if ($content === false) {
    fail("Failed to read the file at $path.");
  }
  return $content;
}

/**
 * The constant holds a “major.minor” version such as '11.1'; stubs are released per patch version.
 */
function getMinimumWooCommerceVersion(string $pluginFile): string {
  $content = readFileOrFail($pluginFile);
  if (!preg_match("/const MAILPOET_MINIMUM_REQUIRED_WOOCOMMERCE_VERSION = '(\d+(?:\.\d+){1,2})';/", $content, $matches)) {
    fail('Could not find MAILPOET_MINIMUM_REQUIRED_WOOCOMMERCE_VERSION in ' . $pluginFile . '.');
  }
  return implode('.', array_pad(explode('.', $matches[1]), 3, '0'));
}

function getPinnedStubsVersion(string $composerJsonFile): string {
  $data = json_decode(readFileOrFail($composerJsonFile), true);
  $version = $data['require'][STUBS_PACKAGE] ?? null;
  if (!is_string($version)) {
    fail('Could not find ' . STUBS_PACKAGE . ' in ' . $composerJsonFile . '.');
  }
  return $version;
}

$minimumVersion = getMinimumWooCommerceVersion($pluginFile);
$stubsVersion = getPinnedStubsVersion($composerJsonFile);

if ($stubsVersion === $minimumVersion) {
  echo "WooCommerce stubs are up to date ($stubsVersion).\n";
  exit(0);
}

if (in_array('--check', $argv, true)) {
  fail("WooCommerce stubs are $stubsVersion, MailPoet's minimum WooCommerce is $minimumVersion. Run: php .github/workflows/scripts/sync_woocommerce_stubs.php");
}

echo "Setting WooCommerce stubs from $stubsVersion to $minimumVersion...\n";
$updatedJson = str_replace(
  '"' . STUBS_PACKAGE . '": "' . $stubsVersion . '"',
  '"' . STUBS_PACKAGE . '": "' . $minimumVersion . '"',
  readFileOrFail($composerJsonFile),
  $replacements
);
if ($replacements !== 1 || file_put_contents($composerJsonFile, $updatedJson) === false) {
  fail("Failed to write the updated file at $composerJsonFile.");
}

// CI installs Composer with setup-php; the repo's own phar exists only after tools/install.php ran.
$repoComposer = $rootDir . '/mailpoet/tools/vendor/composer.phar';
$composer = file_exists($repoComposer) ? escapeshellarg($repoComposer) : 'composer';
// Skip the post-update script: it needs the prefixed Doctrine build, which is not available here.
$command = sprintf(
  '%s --working-dir=%s update %s --no-interaction --no-scripts',
  $composer,
  escapeshellarg($phpstanDir),
  escapeshellarg(STUBS_PACKAGE)
);
passthru($command, $exitCode);
if ($exitCode !== 0) {
  fail("Composer failed with exit code $exitCode.");
}

$supplement = $phpstanDir . '/vendor/woocommerce/php-stubs/supplements/woocommerce-internal/' . $minimumVersion . '.neon';
if (!file_exists($supplement)) {
  fail("Warning: $supplement does not exist. PHPStan would fail. Check that woocommerce/php-stubs supports WooCommerce $minimumVersion.");
}

echo "WooCommerce stubs are now $minimumVersion.\n";
