<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

// throw exception if anything fails
set_error_handler(function ($severity, $message, $file, $line) {
  throw new ErrorException($message, 0, $severity, $file, $line);
});

require_once __DIR__ . '/replace-in-file.php';

$vendor = __DIR__ . '/../vendor-prefixed/symfony/';

replaceInFile($vendor . 'polyfill-intl-idn/Idn.php', ['\\Normalizer::'], '\\MailPoetVendor\\Normalizer::');

// 'Normalizer::' is for use in strings like defined('...')
replaceInFile($vendor . 'polyfill-intl-normalizer/Normalizer.php', ['\\Normalizer::'], '\\MailPoetVendor\\Normalizer::');
replaceInFile($vendor . 'polyfill-intl-normalizer/Normalizer.php', ['\'Normalizer::'], '\'\\MailPoetVendor\\Normalizer::');

// These unprefixed functions break WP 6.1 compatibility, we don't seem to use them, let's prefix them.
replaceInFile($vendor . 'polyfill-intl-normalizer/bootstrap.php', ['function normalizer_is_normalized'], 'function mailpoet_normalizer_is_normalized');
replaceInFile($vendor . 'polyfill-intl-normalizer/bootstrap.php', ['function normalizer_normalize'], 'function mailpoet_normalizer_normalize');

replaceInFile($vendor . 'polyfill-iconv/Iconv.php', ['\\Normalizer::'], '\\MailPoetVendor\\Normalizer::');

// Make the polyfill compatible with PHP 7.4
// php-scoper 0.17 prints ") : type", 0.18 prints "): type". int|false|null must be replaced before int|false.
$grapheme = $vendor . 'polyfill-intl-grapheme/bootstrap80.php';
foreach (['string|false', 'int|false|null', 'int|false', 'array|false'] as $returnType) {
  replaceInFile($grapheme, [") : $returnType", "): $returnType"], ')');
}

// Scoper (prefixer) was adding MailPoetVendor\\\mb_str_pad when it wasn't required
foreach (['bootstrap.php', 'bootstrap80.php'] as $mbstringFile) {
  replaceInFile($vendor . 'polyfill-mbstring/' . $mbstringFile, ['MailPoetVendor\\\mb_str_pad', 'MailPoetVendor\\mb_str_pad'], 'mb_str_pad');
}

// php-scoper 0.18 appends a global class_alias() to the polyfill stubs, which would define Stringable, ValueError,
// Attribute and the like outside our prefix. php-scoper 0.17 output has none, so a missing alias is not an error.
$stubClasses = [
  'polyfill-php80/Resources/stubs/Attribute.php' => 'Attribute',
  'polyfill-php80/Resources/stubs/PhpToken.php' => 'PhpToken',
  'polyfill-php80/Resources/stubs/Stringable.php' => 'Stringable',
  'polyfill-php80/Resources/stubs/UnhandledMatchError.php' => 'UnhandledMatchError',
  'polyfill-php80/Resources/stubs/ValueError.php' => 'ValueError',
  'polyfill-php81/Resources/stubs/CURLStringFile.php' => 'CURLStringFile',
  'polyfill-php81/Resources/stubs/ReturnTypeWillChange.php' => 'ReturnTypeWillChange',
  'polyfill-intl-normalizer/Resources/stubs/Normalizer.php' => 'Normalizer',
];
foreach ($stubClasses as $stubFile => $stubClass) {
  $alias = '\\class_alias(\'MailPoetVendor\\' . $stubClass . '\', \'' . $stubClass . '\', \false);';
  $data = str_replace(["    $alias\n", "$alias\n"], '', file_get_contents($vendor . $stubFile));
  if (strpos($data, 'class_alias(') !== false) {
    throw new RuntimeException("Global class_alias() left in $stubFile");
  }
  file_put_contents($vendor . $stubFile, $data);
}

// Remove unnecessary polyfills these polyfills are required by symfony/console
// but don't use and remove the package
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/symfony/polyfill-php73');
