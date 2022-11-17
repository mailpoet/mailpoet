<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

// throw exception if anything fails
set_error_handler(function ($severity, $message, $file, $line) {
  throw new ErrorException($message, 0, $severity, $file, $line);
});

// because php-scoper contains an issue with PHP attributes we need to replace the use of ReturnTypeWillChange
// this can be deleted when the issue will be solved https://github.com/humbug/php-scoper/issues/539
$iterator = new RecursiveDirectoryIterator(__DIR__ . '/../vendor-prefixed', RecursiveDirectoryIterator::SKIP_DOTS);
$files = new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::SELF_FIRST);
foreach ($files as $file) {
  if (substr($file, -3) === 'php') {
    $data = file_get_contents($file);
    $data = str_replace('use MailPoetVendor\\ReturnTypeWillChange;', 'use ReturnTypeWillChange;', $data);
    file_put_contents($file, $data);
  }
}
