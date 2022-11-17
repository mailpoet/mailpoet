<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

// throw exception if anything fails
set_error_handler(function ($severity, $message, $file, $line) {
  throw new ErrorException($message, 0, $severity, $file, $line);
});

$replacements = [
  // Replace usage of regexp from constant to avoid installing additional package symfony/config
  // The symfony/config causes issues with serialization in integration tests
  [
    'file' => '../vendor-prefixed/symfony/dependency-injection/Dumper/PhpDumper.php',
    'find' => [
      'FileLoader::ANONYMOUS_ID_REGEXP',
    ],
    'replace' => [
      "'/^\\.\\d+_[^~]*+~[._a-zA-Z\\d]{7}$/'",
    ],
  ],
];

// Apply replacements
foreach ($replacements as $singleFile) {
  $data = file_get_contents($singleFile['file']);
  $data = str_replace($singleFile['find'], $singleFile['replace'], $data);
  file_put_contents($singleFile['file'], $data);
}

// removing attribute classes because contain features from PHP 8.0, and then the job qa:php-max-wporg fails
exec('rm ' . __DIR__ . '/../vendor-prefixed/symfony/dependency-injection/Attribute/AsTaggedItem.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/symfony/dependency-injection/Attribute/Autoconfigure.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/symfony/dependency-injection/Attribute/AutoconfigureTag.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/symfony/dependency-injection/Attribute/TaggedIterator.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/symfony/dependency-injection/Attribute/TaggedLocator.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/symfony/dependency-injection/Attribute/When.php');
