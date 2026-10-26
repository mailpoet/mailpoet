<?php declare(strict_types = 1);

/**
 * Keeps the PHPStan stubs on MailPoet’s minimum required versions:
 *
 * - php-stubs/wordpress-stubs on MAILPOET_MINIMUM_REQUIRED_WP_VERSION: the lowest released
 *   stubs version of that minor version (X.Y.*) that is not below the minimum.
 * - php-stubs/woocommerce-stubs on MAILPOET_MINIMUM_REQUIRED_WOOCOMMERCE_VERSION: the lowest
 *   stubs release of that minor version (X.Y.*) that has a supplement in the installed
 *   woocommerce/php-stubs.
 *
 * php .github/workflows/scripts/sync_php_stubs.php          Updates the pins and the lock file.
 * php .github/workflows/scripts/sync_php_stubs.php --check  Exits with 1 when they differ. Works offline.
 */

const PACKAGES = [
  'WordPress' => [
    'stubs' => 'php-stubs/wordpress-stubs',
    'constant' => 'MAILPOET_MINIMUM_REQUIRED_WP_VERSION',
    'header' => '* Requires at least:',
    'headerName' => 'Requires at least',
    'pinPrefix' => 'v',
  ],
  'WooCommerce' => [
    'stubs' => 'php-stubs/woocommerce-stubs',
    'constant' => 'MAILPOET_MINIMUM_REQUIRED_WOOCOMMERCE_VERSION',
    'header' => '* WC requires at least:',
    'headerName' => 'WC requires at least',
    'pinPrefix' => '',
  ],
];

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

function getMinor(string $version): string {
  return implode('.', array_slice(explode('.', $version), 0, 2));
}

/**
 * True for X.Y.Z versions of the same X.Y as the minimum that are not below it.
 */
function isCandidate(?string $version, string $minimumVersion): bool {
  return $version !== null
    && strpos($version, getMinor($minimumVersion) . '.') === 0
    && version_compare($version, $minimumVersion, '>=');
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
 * The plugin header must say the same.
 */
function getMinimumVersion(string $name, array $config, string $pluginFile): string {
  $content = readFileOrFail($pluginFile);
  $line = findLine($content, 'const ' . $config['constant']);
  if ($line === null) {
    fail('Could not find ' . $config['constant'] . ' in ' . $pluginFile . '.');
  }
  $value = trim(explode(';', explode('=', $line, 2)[1] ?? '', 2)[0], " \t'\"");
  $minimum = normalizeVersion($value);
  if ($minimum === null) {
    fail($config['constant'] . " has the value '$value'. Expected X.Y or X.Y.Z, for example '11.1'.");
  }

  $header = findLine($content, $config['header']);
  if ($header === null) {
    fail('Could not find the “' . $config['headerName'] . '” header in ' . $pluginFile . '.');
  }
  $headerValue = trim(substr($header, strlen($config['header'])));
  if (normalizeVersion($headerValue) !== $minimum) {
    fail("The “" . $config['headerName'] . "” header ($headerValue) differs from " . $config['constant'] . " ($value) in $pluginFile. Update both $name values.");
  }
  return $minimum;
}

function getPinnedStubsVersion(string $composerJsonFile, string $package): string {
  $version = readJsonOrFail($composerJsonFile)['require'][$package] ?? null;
  if (!is_string($version)) {
    fail("Could not find $package in $composerJsonFile.");
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
 * WooCommerce: the lowest version that has a supplement in woocommerce/php-stubs.
 * Null when there is none, or when the supplements are not installed.
 */
function findWooCommerceTarget(string $minimumVersion, string $supplementsDir): ?string {
  $target = null;
  foreach (glob($supplementsDir . '/*.neon') ?: [] as $file) {
    $version = normalizeVersion(basename($file, '.neon'));
    if (isCandidate($version, $minimumVersion) && ($target === null || version_compare($version, $target, '<'))) {
      $target = $version;
    }
  }
  return $target;
}

/**
 * WordPress: the lowest released stubs version. Asks Composer, so it needs the network.
 */
function findWordPressTarget(string $minimumVersion, string $composer, string $phpstanDir, string $package): ?string {
  $command = sprintf('%s --working-dir=%s show %s --all --format=json --no-interaction', $composer, escapeshellarg($phpstanDir), escapeshellarg($package));
  exec($command, $output, $exitCode);
  $data = json_decode(implode("\n", $output), true);
  if ($exitCode !== 0 || !is_array($data) || !is_array($data['versions'] ?? null)) {
    fail("Composer could not list the released versions of $package (exit code $exitCode).");
  }
  $target = null;
  foreach ($data['versions'] as $tag) {
    $version = normalizeVersion(ltrim((string)$tag, 'v'));
    if (isCandidate($version, $minimumVersion) && ($target === null || version_compare($version, $target, '<'))) {
      $target = $version;
    }
  }
  return $target;
}

/**
 * @return string[] Descriptions of the places that differ from the target version.
 */
function findMismatches(string $package, string $minimumVersion, ?string $target, string $composerJsonFile, string $composerLockFile): array {
  $found = [
    'require in ' . $composerJsonFile => ltrim(getPinnedStubsVersion($composerJsonFile, $package), 'v'),
    'lock in ' . $composerLockFile => getLockedVersion($composerLockFile, $package),
  ];
  $mismatches = [];
  foreach ($found as $place => $version) {
    // Without a known target accept any release of the minimum’s minor version.
    $isExpected = $target !== null ? $version === $target : isCandidate(normalizeVersion($version), $minimumVersion);
    if (!$isExpected) {
      $mismatches[] = "$place is $version";
    }
  }
  if (!$mismatches && count(array_unique($found)) > 1) {
    $mismatches[] = 'composer.json and composer.lock differ (' . implode(' and ', $found) . ')';
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

// Composer reverts both files itself when the require fails; this covers anything it misses.
function restoreAndFail(string $message, string $json, string $lock, string $jsonFile, string $lockFile): void {
  file_put_contents($jsonFile, $json);
  file_put_contents($lockFile, $lock);
  fail($message);
}

$isCheck = in_array('--check', $argv, true);

// CI installs Composer with setup-php; the repo's own phar exists only after tools/install.php ran.
$repoComposer = $rootDir . '/mailpoet/tools/vendor/composer.phar';
$composer = file_exists($repoComposer) ? escapeshellarg($repoComposer) : 'composer';

$minimums = [];
$targets = [];
$mismatches = [];
foreach (PACKAGES as $name => $config) {
  $minimums[$name] = getMinimumVersion($name, $config, $pluginFile);
  // The check must work offline, so WordPress has no target there.
  $targets[$name] = $name === 'WooCommerce'
    ? findWooCommerceTarget($minimums[$name], $supplementsDir)
    : ($isCheck ? null : findWordPressTarget($minimums[$name], $composer, $phpstanDir, $config['stubs']));
  $mismatches[$name] = findMismatches($config['stubs'], $minimums[$name], $targets[$name], $composerJsonFile, $composerLockFile);
}

if ($isCheck) {
  $messages = [];
  foreach ($mismatches as $name => $items) {
    if ($items) {
      $messages[] = 'MailPoet’s minimum ' . $name . ' is ' . $minimums[$name] . ($targets[$name] ? ' (stubs ' . $targets[$name] . ')' : '') . ', but ' . implode(' and ', $items) . ' for ' . PACKAGES[$name]['stubs'] . '.';
    }
  }
  if ($messages) {
    fail(implode("\n", $messages) . "\nRun: php .github/workflows/scripts/sync_php_stubs.php");
  }
}

if (!array_filter($mismatches)) {
  foreach (PACKAGES as $name => $config) {
    info("$name stubs are up to date (minimum {$minimums[$name]}).");
  }
  exit(0);
}

if (!file_exists($installedFile)) {
  runComposer($composer, $phpstanDir, 'install');
  $targets['WooCommerce'] = findWooCommerceTarget($minimums['WooCommerce'], $supplementsDir);
  $mismatches['WooCommerce'] = findMismatches(PACKAGES['WooCommerce']['stubs'], $minimums['WooCommerce'], $targets['WooCommerce'], $composerJsonFile, $composerLockFile);
}

// Decided before changing anything, so a failure leaves the files untouched.
foreach (PACKAGES as $name => $config) {
  if (!$mismatches[$name] || $targets[$name] !== null) {
    continue;
  }
  if ($name === 'WooCommerce') {
    $installedVersion = getInstalledVersion($installedFile, 'woocommerce/php-stubs');
    fail("woocommerce/php-stubs $installedVersion has no supplement for WooCommerce {$minimums[$name]}. Update woocommerce/php-stubs first (Dependabot’s php-stubs group, or composer update woocommerce/php-stubs in mailpoet/tasks/phpstan).");
  }
  fail($config['stubs'] . " has no release for $name {$minimums[$name]}. Wait for the stubs release or lower the minimum.");
}

$originalJson = readFileOrFail($composerJsonFile);
$originalLock = readFileOrFail($composerLockFile);

foreach (PACKAGES as $name => $config) {
  if (!$mismatches[$name]) {
    continue;
  }
  $target = $targets[$name];
  $pin = $config['pinPrefix'] . $target;
  info("Setting $name stubs to $pin...");
  passthru(sprintf('%s --working-dir=%s require %s --no-scripts --no-interaction', $composer, escapeshellarg($phpstanDir), escapeshellarg($config['stubs'] . ':' . $pin)), $exitCode);
  if ($exitCode !== 0) {
    restoreAndFail("Composer failed with exit code $exitCode.", $originalJson, $originalLock, $composerJsonFile, $composerLockFile);
  }

  // Keep the exact pin even if Composer wrote the constraint differently.
  $written = getPinnedStubsVersion($composerJsonFile, $config['stubs']);
  if ($written !== $pin) {
    file_put_contents($composerJsonFile, str_replace('"' . $config['stubs'] . '": "' . $written . '"', '"' . $config['stubs'] . '": "' . $pin . '"', readFileOrFail($composerJsonFile)));
  }
  if (findMismatches($config['stubs'], $minimums[$name], $target, $composerJsonFile, $composerLockFile)) {
    restoreAndFail("The $name stubs are not $pin in composer.json and composer.lock after the update.", $originalJson, $originalLock, $composerJsonFile, $composerLockFile);
  }
  info("$name stubs are now $pin.");
}
