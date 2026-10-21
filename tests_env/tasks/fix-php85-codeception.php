<?php

// throw exception if anything fails
set_error_handler(function ($severity, $message, $file, $line) {
  throw new ErrorException($message, 0, $severity, $file, $line);
});

// Fixes for PHP 8.5 Compatibility
// - the backtick operator and the (boolean) cast are deprecated at compile time
// - ${var} string interpolation and "default;" in a switch are deprecated at compile time
// - ReflectionProperty/ReflectionMethod::setAccessible() is deprecated (no-op since PHP 8.1)
// - the $http_response_header variable is deprecated at compile time
// Every patch fails loudly when its target is missing, so a vendor update can’t silently drop it.

function fixPhp85Fail(string $file, string $message): void {
  fwrite(STDERR, "fix-php85-codeception: $file: $message\n");
  exit(1);
}

function fixPhp85Read(string $file): string {
  $data = is_file($file) ? file_get_contents($file) : false;
  if ($data === false) {
    fixPhp85Fail($file, 'file not found');
  }
  return $data;
}

$replacements = [
  [
    'file' => __DIR__ . '/../vendor/codeception/codeception/src/Codeception/Subscriber/Console.php',
    'find' => '(int) (`command -v tput >> /dev/null 2>&1 && tput cols`) - 2;',
    'replace' => "(int) shell_exec('command -v tput >> /dev/null 2>&1 && tput cols') - 2;",
  ],
  [
    'file' => __DIR__ . '/../vendor/codeception/module-db/src/Codeception/Module/Db.php',
    'find' => '(boolean) $databaseConfig[ \'ssl_verify_server_cert\' ]',
    'replace' => '(bool) $databaseConfig[ \'ssl_verify_server_cert\' ]',
  ],
  [
    'file' => __DIR__ . '/../vendor/codeception/codeception/src/Codeception/Step/Retry.php',
    'find' => 'Retrying #$retry in ${interval}ms',
    'replace' => 'Retrying #$retry in {$interval}ms',
  ],
  [
    'file' => __DIR__ . '/../vendor/lucatume/wp-browser/src/includes/isolated-install.php',
    'find' => "\tdefault;\n\tcase 'drop':",
    'replace' => "\tdefault:\n\tcase 'drop':",
  ],
  [
    'file' => __DIR__ . '/../vendor/codeception/codeception/src/Codeception/Coverage/Subscriber/LocalServer.php',
    'find' => "false, \$context);\n\n        \$okHeaders = array_filter(\n            \$http_response_header,",
    // get_defined_vars() avoids naming the deprecated variable, which PHP 8.5 flags at compile time
    'replace' => "false, \$context);\n        \$responseHeaders = function_exists('http_get_last_response_headers')\n            ? http_get_last_response_headers()\n            : get_defined_vars()['http_response_header'];\n\n        \$okHeaders = array_filter(\n            \$responseHeaders,",
  ],
  [
    'file' => __DIR__ . '/../vendor/codeception/codeception/src/Codeception/Coverage/Subscriber/LocalServer.php',
    'find' => '. $http_response_header[0]',
    'replace' => '. $responseHeaders[0]',
  ],
  [
    'file' => __DIR__ . '/../vendor/codeception/codeception/src/Codeception/Coverage/Subscriber/LocalServer.php',
    'find' => 'getRemoteError($http_response_header)',
    'replace' => 'getRemoteError($responseHeaders)',
  ],
];

foreach ($replacements as $replacement) {
  $file = $replacement['file'];
  $data = fixPhp85Read($file);
  if (strpos($data, $replacement['replace']) !== false) {
    continue;
  }
  if (strpos($data, $replacement['find']) !== false) {
    file_put_contents($file, str_replace($replacement['find'], $replacement['replace'], $data));
  } else {
    fixPhp85Fail($file, 'snippet to patch not found: ' . $replacement['find']);
  }
}

// Wrap every unguarded setAccessible(true) call in a PHP version check
$setAccessibleFiles = [
  __DIR__ . '/../vendor/codeception/codeception/src/Codeception/Util/ReflectionPropertyAccessor.php',
  __DIR__ . '/../vendor/codeception/codeception/src/Codeception/Util/ReflectionHelper.php',
  __DIR__ . '/../vendor/codeception/codeception/src/Codeception/Lib/Di.php',
  __DIR__ . '/../vendor/codeception/stub/src/Stub.php',
  __DIR__ . '/../vendor/lucatume/wp-browser/src/tad/WPBrowser/Traits/WithCodeceptionTestCaseEnhancements.php',
  __DIR__ . '/../vendor/lucatume/wp-browser/src/tad/WPBrowser/props.php',
];

$guard = 'if (PHP_VERSION_ID < 80100) {';
foreach ($setAccessibleFiles as $file) {
  $lines = explode("\n", fixPhp85Read($file));
  $result = [];
  $patched = 0;
  $alreadyGuarded = 0;
  foreach ($lines as $index => $line) {
    if (!preg_match('/^(\s*)(\$\w+)->setAccessible\(true\);\s*$/', $line, $matches)) {
      $result[] = $line;
      continue;
    }
    if (trim($lines[$index - 1]) === $guard) {
      $alreadyGuarded++;
      $result[] = $line;
      continue;
    }
    $result[] = $matches[1] . $guard;
    $result[] = '    ' . $line;
    $result[] = $matches[1] . '}';
    $patched++;
  }
  if ($patched === 0 && $alreadyGuarded === 0) {
    fixPhp85Fail($file, 'no setAccessible(true) call found to patch');
  }
  if ($patched > 0) {
    file_put_contents($file, implode("\n", $result));
  }
}
