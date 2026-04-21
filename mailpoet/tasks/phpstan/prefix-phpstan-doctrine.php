<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

// throw exception if anything fails
set_error_handler(function ($severity, $message, $file, $line) {
  throw new ErrorException($message, 0, $severity, $file, $line);
});

$iterator = new RecursiveDirectoryIterator(__DIR__ . '/vendor/phpstan/phpstan-doctrine', RecursiveDirectoryIterator::SKIP_DOTS);
$files = new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::SELF_FIRST);
foreach ($files as $file) {
  if (substr($file, -4) === '.php' || substr($file, -5) === '.stub') {
    $data = file_get_contents($file);

    // when string 'Doctrine' is prefixed by a whitespace, ', ", or ( plus zero or more \, and suffixed by
    // one or more \, prefix it with 'MailPoetDoctrine' + the number of trailing \ in the original string
    $data = preg_replace('/([\'"\s(?]\\\\*)(Doctrine)(\\\\+)/', '$1MailPoetVendor$3$2$3', $data);
    file_put_contents($file, $data);
  }
}

// Inject the Doctrine package entries from the prefixer's installed.php into
// phpstan's own vendor installed.php. Without this, phpstan-doctrine's calls
// to `InstalledVersions::getVersion('doctrine/dbal')` (and friends) throw
// "Package not installed" at analysis time — bootstrap.php's runtime reload
// only works from the main phpstan process and the merged state doesn't
// reliably propagate to forked worker processes in every CI environment.
// Writing the data directly into phpstan's installed.php makes the lookup
// work via Composer's default vendor-scan path.
$phpstanInstalledPath = __DIR__ . '/vendor/composer/installed.php';
$prefixerInstalledPath = __DIR__ . '/../../prefixer/vendor/composer/installed.php';
if (file_exists($phpstanInstalledPath) && file_exists($prefixerInstalledPath)) {
  $phpstanData = require $phpstanInstalledPath;
  $prefixerData = require $prefixerInstalledPath;

  // Copy every `doctrine/*` entry from prefixer into phpstan's versions list.
  // phpstan-doctrine only reads version metadata via InstalledVersions
  // (getVersion / getPrettyVersion), never install_path — and prefixer's
  // stored install_path is already absolute and prefixer-relative, which
  // wouldn't be valid from phpstan's vendor anyway. Drop it to avoid
  // leaving a misleading-looking path in phpstan's installed.php.
  foreach ($prefixerData['versions'] as $packageName => $packageData) {
    if (strpos($packageName, 'doctrine/') === 0) {
      unset($packageData['install_path']);
      $phpstanData['versions'][$packageName] = $packageData;
    }
  }

  // Regenerate installed.php with the augmented data.
  file_put_contents(
    $phpstanInstalledPath,
    "<?php return " . var_export($phpstanData, true) . ";\n"
  );
  $injectedCount = count(array_filter(array_keys($prefixerData['versions']), function ($p) {
    return strpos($p, 'doctrine/') === 0;
  }));
  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CLI build script, $injectedCount is a count() result.
  echo 'Injected ' . $injectedCount . ' doctrine/* package entries into phpstan\'s installed.php' . "\n";
}
