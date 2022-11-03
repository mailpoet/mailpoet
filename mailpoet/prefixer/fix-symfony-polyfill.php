<?php

// throw exception if anything fails
set_error_handler(function ($severity, $message, $file, $line) {
  throw new ErrorException($message, 0, $severity, $file, $line);
});

$file = __DIR__ . '/../vendor-prefixed/symfony/polyfill-intl-idn/Idn.php';
$data = file_get_contents($file);
$data = str_replace('\\Normalizer::', '\\MailPoetVendor\\Normalizer::', $data);
$data = str_replace('use Normalizer;', 'use MailPoetVendor\\Normalizer;', $data);
file_put_contents($file, $data);

$file = __DIR__ . '/../vendor-prefixed/symfony/polyfill-intl-normalizer/Normalizer.php';
$data = file_get_contents($file);
$data = str_replace('\\Normalizer::', '\\MailPoetVendor\\Normalizer::', $data);
$data = str_replace('\'Normalizer::', '\'\\MailPoetVendor\\Normalizer::', $data); // for use in strings like defined('...')
file_put_contents($file, $data);

$file = __DIR__ . '/../vendor-prefixed/symfony/polyfill-intl-normalizer/bootstrap.php';
$data = file_get_contents($file);
// These unprefixed functions break WP 6.1 compatibility, we don't seem to use them, let's prefix them.
$data = str_replace('function normalizer_is_normalized', 'function mailpoet_normalizer_is_normalized', $data);
$data = str_replace('function normalizer_normalize', 'function mailpoet_normalizer_normalize', $data);
file_put_contents($file, $data);

$file = __DIR__ . '/../vendor-prefixed/symfony/polyfill-iconv/Iconv.php';
$data = file_get_contents($file);
$data = str_replace('\\Normalizer::', '\\MailPoetVendor\\Normalizer::', $data);
file_put_contents($file, $data);

// Remove unnecessary polyfills these polyfills are required by symfony/console
// but don't use and remove the package
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/symfony/polyfill-php73');
