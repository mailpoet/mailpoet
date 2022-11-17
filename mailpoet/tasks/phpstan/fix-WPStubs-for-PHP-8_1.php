<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

// throw exception if anything fails
set_error_handler(function ($severity, $message, $file, $line) {
  throw new ErrorException($message, 0, $severity, $file, $line);
});

$file = 'vendor/php-stubs/wordpress-stubs/wordpress-stubs.php';

$data = file_get_contents($file);

$search_term = 'function readonly';

if (!strpos($data, $search_term)) {
  return;
}

// "readonly" is a reserved keyword in PHP 8.1,
// WP has a "function readonly", hence, we need to replace readonly with __readonly for the stub
$data = str_replace($search_term, 'function __readonly', $data);
file_put_contents($file, $data);
