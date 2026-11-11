<?php declare(strict_types = 1);

/**
 * Keeps the PHPStan stubs on MailPoet’s minimum required versions:
 *
 * - php-stubs/wordpress-stubs on MAILPOET_MINIMUM_REQUIRED_WP_VERSION: a released stubs version
 *   of that minor version (X.Y.*) that is not below the minimum. A version that already fits is
 *   kept, otherwise the lowest such release is used.
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
    'source' => 'releases',
    'noTarget' => 'php-stubs/wordpress-stubs has no release for WordPress {minimum}. Wait for the stubs release or lower the minimum.',
  ],
  'WooCommerce' => [
    'stubs' => 'php-stubs/woocommerce-stubs',
    'constant' => 'MAILPOET_MINIMUM_REQUIRED_WOOCOMMERCE_VERSION',
    'header' => '* WC requires at least:',
    'headerName' => 'WC requires at least',
    'pinPrefix' => '',
    'source' => 'supplements',
    'noTarget' => 'woocommerce/php-stubs {installed} has no supplement for WooCommerce {minimum}. Update woocommerce/php-stubs first (Dependabot’s php-stubs group, or composer update woocommerce/php-stubs in mailpoet/tasks/phpstan).',
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
  $raw = trim(explode(';', explode('=', $line, 2)[1] ?? '', 2)[0]);
  $quote = substr($raw, 0, 1);
  if (($quote !== "'" && $quote !== '"') || strlen($raw) < 2 || substr($raw, -1) !== $quote) {
    fail($config['constant'] . " has the value $raw. It must be a quoted string such as '11.1', because PHP reads an unquoted 7.10 as the number 7.1.");
  }
  $value = substr($raw, 1, -1);
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
 * The lowest of the X.Y.Z versions that are of the minimum’s minor version and not below it.
 *
 * @param array<int, string|null> $versions
 */
function findLowestCandidate(array $versions, string $minimumVersion): ?string {
  $target = null;
  foreach ($versions as $version) {
    if (isCandidate($version, $minimumVersion) && ($target === null || version_compare($version, $target, '<'))) {
      $target = $version;
    }
  }
  return $target;
}

/**
 * WooCommerce: the lowest version that has a supplement in woocommerce/php-stubs.
 * Null when there is none, or when the supplements are not installed.
 */
function findSupplementTarget(string $minimumVersion, string $supplementsDir): ?string {
  $versions = [];
  foreach (glob($supplementsDir . '/*.neon') ?: [] as $file) {
    $versions[] = normalizeVersion(basename($file, '.neon'));
  }
  return findLowestCandidate($versions, $minimumVersion);
}

/**
 * WordPress: the lowest released stubs version. Asks Composer, so it needs the network.
 *
 * @throws RuntimeException When Composer cannot list the releases.
 */
function findReleaseTarget(string $minimumVersion, string $composer, string $phpstanDir, string $package): ?string {
  $command = sprintf('%s --working-dir=%s show %s --all --format=json --no-interaction', $composer, escapeshellarg($phpstanDir), escapeshellarg($package));
  exec($command, $output, $exitCode);
  $data = json_decode(implode("\n", $output), true);
  if ($exitCode !== 0 || !is_array($data) || !is_array($data['versions'] ?? null)) {
    throw new RuntimeException("Composer could not list the released versions of $package (exit code $exitCode).");
  }
  $versions = [];
  foreach ($data['versions'] as $tag) {
    $versions[] = normalizeVersion(ltrim((string)$tag, 'v'));
  }
  return findLowestCandidate($versions, $minimumVersion);
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

function runComposer(string $composer, string $phpstanDir, string $arguments): int {
  // Skip scripts: the post-update script needs the prefixed Doctrine build, which is not available here.
  passthru(sprintf('%s --working-dir=%s %s --no-scripts --no-interaction', $composer, escapeshellarg($phpstanDir), $arguments), $exitCode);
  return $exitCode;
}

/**
 * Puts both files back, then reinstalls so vendor/ matches the restored lock file again.
 * Composer reverts both files itself when the require fails; this covers anything it misses.
 */
function restoreAndFail(string $message, string $json, string $lock, string $jsonFile, string $lockFile, string $composer, string $phpstanDir): void {
  file_put_contents($jsonFile, $json);
  file_put_contents($lockFile, $lock);
  if (runComposer($composer, $phpstanDir, 'install') !== 0) {
    $message .= "\nReinstalling the Composer packages from the restored files failed too. Run composer install in $phpstanDir.";
  }
  fail($message);
}

/**
 * The stubs version to use, or null when there is none. May throw RuntimeException.
 */
function resolveTarget(array $config, string $minimum, bool $isCheck, string $composer, string $phpstanDir, string $supplementsDir, string $composerJsonFile, string $composerLockFile): ?string {
  if ($config['source'] === 'supplements') {
    return findSupplementTarget($minimum, $supplementsDir);
  }
  // The check works offline and accepts any fitting release.
  if ($isCheck) {
    return null;
  }
  // Keep a version that fits and is in both files; this is what the check accepts too.
  $json = ltrim(getPinnedStubsVersion($composerJsonFile, $config['stubs']), 'v');
  if ($json === getLockedVersion($composerLockFile, $config['stubs']) && isCandidate(normalizeVersion($json), $minimum)) {
    return $json;
  }
  return findReleaseTarget($minimum, $composer, $phpstanDir, $config['stubs']);
}

$isCheck = in_array('--check', $argv, true);

// CI installs Composer with setup-php; the repo's own phar exists only after tools/install.php ran.
$repoComposer = $rootDir . '/mailpoet/tools/vendor/composer.phar';
$composer = file_exists($repoComposer) ? escapeshellarg($repoComposer) : 'composer';

$minimums = [];
$targets = [];
$errors = [];
$mismatches = [];
foreach (PACKAGES as $name => $config) {
  $minimums[$name] = getMinimumVersion($name, $config, $pluginFile);
}

function resolvePackage(string $name, array $config, array $minimums, bool $isCheck, string $composer, string $phpstanDir, string $supplementsDir, string $composerJsonFile, string $composerLockFile, array &$targets, array &$errors, array &$mismatches): void {
  try {
    $targets[$name] = resolveTarget($config, $minimums[$name], $isCheck, $composer, $phpstanDir, $supplementsDir, $composerJsonFile, $composerLockFile);
  } catch (RuntimeException $e) {
    $targets[$name] = null;
    $errors[$name] = $e->getMessage();
    return;
  }
  $mismatches[$name] = findMismatches($config['stubs'], $minimums[$name], $targets[$name], $composerJsonFile, $composerLockFile);
}

foreach (PACKAGES as $name => $config) {
  resolvePackage($name, $config, $minimums, $isCheck, $composer, $phpstanDir, $supplementsDir, $composerJsonFile, $composerLockFile, $targets, $errors, $mismatches);
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

// The supplements come with woocommerce/php-stubs, which may not be installed yet.
if (!$isCheck && array_filter($mismatches) && !file_exists($installedFile)) {
  if (runComposer($composer, $phpstanDir, 'install') !== 0) {
    fail('Composer install failed.');
  }
  foreach (PACKAGES as $name => $config) {
    if ($config['source'] === 'supplements') {
      resolvePackage($name, $config, $minimums, $isCheck, $composer, $phpstanDir, $supplementsDir, $composerJsonFile, $composerLockFile, $targets, $errors, $mismatches);
    }
  }
}

// A package that needs a change but has no target fails on its own; the others still sync.
$updates = [];
foreach (PACKAGES as $name => $config) {
  if (!empty($errors[$name]) || !$mismatches[$name]) {
    continue;
  }
  if ($targets[$name] === null) {
    $installed = strpos($config['noTarget'], '{installed}') !== false ? getInstalledVersion($installedFile, 'woocommerce/php-stubs') : '';
    $errors[$name] = str_replace(['{installed}', '{minimum}'], [$installed, $minimums[$name]], $config['noTarget']);
    continue;
  }
  $updates[$name] = $config['pinPrefix'] . $targets[$name];
}

if ($updates) {
  $originalJson = readFileOrFail($composerJsonFile);
  $originalLock = readFileOrFail($composerLockFile);

  // One require: the two stubs packages depend on each other, so separate ones can conflict.
  $arguments = [];
  foreach ($updates as $name => $pin) {
    info("Setting $name stubs to $pin...");
    $arguments[] = escapeshellarg(PACKAGES[$name]['stubs'] . ':' . $pin);
  }
  $exitCode = runComposer($composer, $phpstanDir, 'require ' . implode(' ', $arguments));
  if ($exitCode !== 0) {
    restoreAndFail("Composer failed with exit code $exitCode.", $originalJson, $originalLock, $composerJsonFile, $composerLockFile, $composer, $phpstanDir);
  }

  foreach ($updates as $name => $pin) {
    $stubs = PACKAGES[$name]['stubs'];
    // Keep the exact pin even if Composer wrote the constraint differently.
    $written = getPinnedStubsVersion($composerJsonFile, $stubs);
    if ($written !== $pin) {
      file_put_contents($composerJsonFile, str_replace('"' . $stubs . '": "' . $written . '"', '"' . $stubs . '": "' . $pin . '"', readFileOrFail($composerJsonFile)));
    }
    if (findMismatches($stubs, $minimums[$name], $targets[$name], $composerJsonFile, $composerLockFile)) {
      restoreAndFail("The $name stubs are not $pin in composer.json and composer.lock after the update.", $originalJson, $originalLock, $composerJsonFile, $composerLockFile, $composer, $phpstanDir);
    }
    info("$name stubs are now $pin.");
  }
}

foreach (PACKAGES as $name => $config) {
  if (!isset($errors[$name]) && !isset($updates[$name])) {
    info("$name stubs are up to date (minimum {$minimums[$name]}).");
  }
}
foreach ($errors as $name => $message) {
  fwrite(STDERR, "$name: $message\n");
}
exit($errors ? 1 : 0);
