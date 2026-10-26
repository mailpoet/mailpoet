<?php declare(strict_types = 1);

/**
 * Keeps the WooCommerce PHPStan stubs (php-stubs/woocommerce-stubs) on MailPoet’s minimum
 * required WooCommerce version: the lowest stubs release of that minor version (X.Y.*) that
 * has a supplement in the installed woocommerce/php-stubs.
 *
 * php .github/workflows/scripts/sync_woocommerce_stubs.php          Updates the pin and the lock file.
 * php .github/workflows/scripts/sync_woocommerce_stubs.php --check  Exits with 1 when they differ.
 */

const STUBS_PACKAGE = 'php-stubs/woocommerce-stubs';
const MINIMUM_CONSTANT = 'MAILPOET_MINIMUM_REQUIRED_WOOCOMMERCE_VERSION';
const HEADER_PREFIX = '* WC requires at least:';

$rootDir = dirname(__DIR__, 3);
$pluginFile = $rootDir . '/mailpoet/mailpoet.php';
$phpstanDir = $rootDir . '/mailpoet/tasks/phpstan';
$composerJsonFile = $phpstanDir . '/composer.json';
$composerLockFile = $phpstanDir . '/composer.lock';
$installedFile = $phpstanDir . '/vendor/composer/installed.json';
$supplementsDir = $phpstanDir . '/vendor/woocommerce/php-stubs/supplements/woocommerce-internal';

function info(string $message): void {
  fwrite(STDOUT, $message . "\n");
}

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

function readJsonOrFail(string $path): array {
  $data = json_decode(readFileOrFail($path), true);
  if (!is_array($data)) {
    fail("The file at $path is not valid JSON.");
  }
  return $data;
}

/**
 * Accepts “X.Y” or “X.Y.Z” (digits only, no leading zeros) and returns “X.Y.Z”; null otherwise.
 */
function normalizeVersion(string $value): ?string {
  $parts = explode('.', $value);
  if (count($parts) < 2 || count($parts) > 3) {
    return null;
  }
  foreach ($parts as $part) {
    if (!ctype_digit($part) || ($part !== '0' && $part[0] === '0')) {
      return null;
    }
  }
  return implode('.', array_pad($parts, 3, '0'));
}

/**
 * Returns the first line (trimmed) that starts with $prefix, ignoring commented-out copies.
 */
function findLine(string $content, string $prefix): ?string {
  foreach (explode("\n", $content) as $line) {
    $line = trim($line);
    if (strpos($line, $prefix) === 0) {
      return $line;
    }
  }
  return null;
}

/**
 * The constant holds a “major.minor” version such as '11.1'; stubs are released per patch version.
 * The plugin header “WC requires at least” must say the same.
 */
function getMinimumWooCommerceVersion(string $pluginFile): string {
  $content = readFileOrFail($pluginFile);
  $line = findLine($content, 'const ' . MINIMUM_CONSTANT);
  if ($line === null) {
    fail('Could not find ' . MINIMUM_CONSTANT . ' in ' . $pluginFile . '.');
  }
  $value = trim(explode(';', explode('=', $line, 2)[1] ?? '', 2)[0], " \t'\"");
  $minimum = normalizeVersion($value);
  if ($minimum === null) {
    fail(MINIMUM_CONSTANT . " has the value '$value'. Expected X.Y or X.Y.Z, for example '11.1'.");
  }

  $header = findLine($content, HEADER_PREFIX);
  if ($header === null) {
    fail('Could not find the “WC requires at least” header in ' . $pluginFile . '.');
  }
  $headerValue = trim(substr($header, strlen(HEADER_PREFIX)));
  if (normalizeVersion($headerValue) !== $minimum) {
    fail("The “WC requires at least” header ($headerValue) differs from " . MINIMUM_CONSTANT . " ($value) in $pluginFile. Update both.");
  }
  return $minimum;
}

function getPinnedStubsVersion(string $composerJsonFile): string {
  $version = readJsonOrFail($composerJsonFile)['require'][STUBS_PACKAGE] ?? null;
  if (!is_string($version)) {
    fail('Could not find ' . STUBS_PACKAGE . ' in ' . $composerJsonFile . '.');
  }
  return $version;
}

function getLockedVersion(string $composerLockFile, string $package): string {
  $lock = readJsonOrFail($composerLockFile);
  foreach (array_merge($lock['packages'] ?? [], $lock['packages-dev'] ?? []) as $item) {
    if (($item['name'] ?? null) === $package) {
      return ltrim((string)($item['version'] ?? ''), 'v');
    }
  }
  fail("Could not find $package in $composerLockFile.");
}

function getInstalledVersion(string $installedFile, string $package): string {
  $data = readJsonOrFail($installedFile);
  foreach ($data['packages'] ?? $data as $item) {
    if (($item['name'] ?? null) === $package) {
      return (string)($item['version'] ?? 'unknown');
    }
  }
  return 'unknown';
}

/**
 * The lowest version X.Y.Z (same X.Y as the minimum, Z at least the minimum’s) that has a supplement.
 * Null when there is none, or when the supplements are not installed.
 */
function findTargetVersion(string $minimumVersion, string $supplementsDir): ?string {
  $minor = implode('.', array_slice(explode('.', $minimumVersion), 0, 2));
  $target = null;
  foreach (glob($supplementsDir . '/*.neon') ?: [] as $file) {
    $version = normalizeVersion(basename($file, '.neon'));
    $isCandidate = $version !== null
      && strpos($version, $minor . '.') === 0
      && version_compare($version, $minimumVersion, '>=');
    if ($isCandidate && ($target === null || version_compare($version, $target, '<'))) {
      $target = $version;
    }
  }
  return $target;
}

/**
 * @return string[] Descriptions of the places that differ from the target version.
 */
function findMismatches(string $minimumVersion, ?string $target, string $composerJsonFile, string $composerLockFile): array {
  $mismatches = [];
  $found = ['require in ' . $composerJsonFile => getPinnedStubsVersion($composerJsonFile), 'lock in ' . $composerLockFile => getLockedVersion($composerLockFile, STUBS_PACKAGE)];
  foreach ($found as $place => $version) {
    // Without installed supplements the target is unknown: accept any release of the minimum’s minor version.
    $isExpected = $target !== null
      ? $version === $target
      : strpos($version, implode('.', array_slice(explode('.', $minimumVersion), 0, 2)) . '.') === 0 && version_compare($version, $minimumVersion, '>=');
    if (!$isExpected) {
      $mismatches[] = "$place is $version";
    }
  }
  return $mismatches;
}

function runComposer(string $composer, string $phpstanDir, string $arguments): void {
  // Skip scripts: the post-update script needs the prefixed Doctrine build, which is not available here.
  passthru(sprintf('%s --working-dir=%s %s --no-scripts --no-interaction', $composer, escapeshellarg($phpstanDir), $arguments), $exitCode);
  if ($exitCode !== 0) {
    fail("Composer failed with exit code $exitCode.");
  }
}

$minimumVersion = getMinimumWooCommerceVersion($pluginFile);
$target = findTargetVersion($minimumVersion, $supplementsDir);
$mismatches = findMismatches($minimumVersion, $target, $composerJsonFile, $composerLockFile);

if (!$mismatches) {
  info("WooCommerce stubs are up to date (minimum $minimumVersion).");
  exit(0);
}

if (in_array('--check', $argv, true)) {
  fail('MailPoet’s minimum WooCommerce is ' . $minimumVersion . ($target ? " (stubs $target)" : '') . ', but ' . implode(' and ', $mismatches) . '. Run: php .github/workflows/scripts/sync_woocommerce_stubs.php');
}

// CI installs Composer with setup-php; the repo's own phar exists only after tools/install.php ran.
$repoComposer = $rootDir . '/mailpoet/tools/vendor/composer.phar';
$composer = file_exists($repoComposer) ? escapeshellarg($repoComposer) : 'composer';

if (!file_exists($installedFile)) {
  runComposer($composer, $phpstanDir, 'install');
  $target = findTargetVersion($minimumVersion, $supplementsDir);
}

// Decided before changing anything, so a failure leaves the files untouched.
if ($target === null) {
  $installedVersion = getInstalledVersion($installedFile, 'woocommerce/php-stubs');
  fail("woocommerce/php-stubs $installedVersion has no supplement for WooCommerce $minimumVersion. Update woocommerce/php-stubs first (Dependabot’s php-stubs group, or composer update woocommerce/php-stubs in mailpoet/tasks/phpstan).");
}

info("Setting WooCommerce stubs to $target...");
$originalJson = readFileOrFail($composerJsonFile);
$originalLock = readFileOrFail($composerLockFile);

// Composer reverts both files itself when the require fails; this covers anything it misses.
function restoreAndFail(string $message, string $json, string $lock, string $jsonFile, string $lockFile): void {
  file_put_contents($jsonFile, $json);
  file_put_contents($lockFile, $lock);
  fail($message);
}

passthru(sprintf('%s --working-dir=%s require %s --no-scripts --no-interaction', $composer, escapeshellarg($phpstanDir), escapeshellarg(STUBS_PACKAGE . ':' . $target)), $exitCode);
if ($exitCode !== 0) {
  restoreAndFail("Composer failed with exit code $exitCode.", $originalJson, $originalLock, $composerJsonFile, $composerLockFile);
}

// Keep the exact pin even if Composer wrote the constraint differently.
$written = getPinnedStubsVersion($composerJsonFile);
if ($written !== $target) {
  file_put_contents($composerJsonFile, str_replace('"' . STUBS_PACKAGE . '": "' . $written . '"', '"' . STUBS_PACKAGE . '": "' . $target . '"', readFileOrFail($composerJsonFile)));
}
if (findMismatches($minimumVersion, $target, $composerJsonFile, $composerLockFile)) {
  restoreAndFail("The stubs are not $target in composer.json and composer.lock after the update.", $originalJson, $originalLock, $composerJsonFile, $composerLockFile);
}

info("WooCommerce stubs are now $target.");
