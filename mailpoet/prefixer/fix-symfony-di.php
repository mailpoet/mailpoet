<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

// throw exception if anything fails
set_error_handler(function ($severity, $message, $file, $line) {
  throw new ErrorException($message, 0, $severity, $file, $line);
});

require_once __DIR__ . '/replace-in-file.php';

// Replace usage of regexp from constant to avoid installing additional package symfony/config
// The symfony/config causes issues with serialization in integration tests
replaceInFile(
  __DIR__ . '/../vendor-prefixed/symfony/dependency-injection/Dumper/PhpDumper.php',
  ['FileLoader::ANONYMOUS_ID_REGEXP'],
  "'/^\\.\\d+_[^~]*+~[._a-zA-Z\\d]{7}$/'"
);

// setAccessible() is deprecated in PHP 8.5 and has no effect since PHP 8.1
// The spaces in the brackets prevent re-wrapping when the script runs repeatedly
foreach (['ServiceLocator.php', 'Compiler/Compiler.php', 'Compiler/ResolveChildDefinitionsPass.php'] as $diFile) {
  replaceInFile(
    __DIR__ . '/../vendor-prefixed/symfony/dependency-injection/' . $diFile,
    ['$r->setAccessible(\true);'],
    'if (PHP_VERSION_ID < 80100) { $r->setAccessible( \true ); }'
  );
}
replaceInFile(
  __DIR__ . '/../vendor-prefixed/symfony/dependency-injection/Compiler/RegisterAutoconfigureAttributesPass.php',
  ['$parseDefinitions->setAccessible(\true);'],
  'if (PHP_VERSION_ID < 80100) { $parseDefinitions->setAccessible( \true ); }'
);
