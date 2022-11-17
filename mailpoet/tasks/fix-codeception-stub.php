<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

// throw exception if anything fails
set_error_handler(function ($severity, $message, $file, $line) {
  throw new ErrorException($message, 0, $severity, $file, $line);
});

// Skip for production build
if (!file_exists(__DIR__ . '/../vendor/codeception/stub/src/Stub.php')) {
  exit;
}

// Fixes Codeception/Stub annotation for Stub::MakeEmptyExcept.
// This fix can be deleted when we use Codeception/Stub:4.0.1 or newer.
$replacements = [
  [
    'file' => __DIR__ . '/../vendor/codeception/stub/src/Stub.php',
    'find' => [
      '* @template' . PHP_EOL,
    ],
    'replace' => [
      '* @template RealInstanceType of object' . PHP_EOL,
    ],
  ],

];

// Apply replacements
foreach ($replacements as $singleFile) {
  $data = file_get_contents($singleFile['file']);
  $data = str_replace($singleFile['find'], $singleFile['replace'], $data);
  file_put_contents($singleFile['file'], $data);
}
