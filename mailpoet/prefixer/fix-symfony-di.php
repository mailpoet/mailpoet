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
  // setAccessible() is deprecated in PHP 8.5 and has no effect since PHP 8.1
  // The spaces in the brackets prevent re-wrapping when the script runs repeatedly
  [
    'file' => '../vendor-prefixed/symfony/dependency-injection/ServiceLocator.php',
    'find' => ['$r->setAccessible(\true);'],
    'replace' => ['if (PHP_VERSION_ID < 80100) { $r->setAccessible( \true ); }'],
  ],
  [
    'file' => '../vendor-prefixed/symfony/dependency-injection/Compiler/Compiler.php',
    'find' => ['$r->setAccessible(\true);'],
    'replace' => ['if (PHP_VERSION_ID < 80100) { $r->setAccessible( \true ); }'],
  ],
  [
    'file' => '../vendor-prefixed/symfony/dependency-injection/Compiler/ResolveChildDefinitionsPass.php',
    'find' => ['$r->setAccessible(\true);'],
    'replace' => ['if (PHP_VERSION_ID < 80100) { $r->setAccessible( \true ); }'],
  ],
  [
    'file' => '../vendor-prefixed/symfony/dependency-injection/Compiler/RegisterAutoconfigureAttributesPass.php',
    'find' => ['$parseDefinitions->setAccessible(\true);'],
    'replace' => ['if (PHP_VERSION_ID < 80100) { $parseDefinitions->setAccessible( \true ); }'],
  ],
];

// Apply replacements
foreach ($replacements as $singleFile) {
  $data = file_get_contents($singleFile['file']);
  $data = str_replace($singleFile['find'], $singleFile['replace'], $data);
  file_put_contents($singleFile['file'], $data);
}
