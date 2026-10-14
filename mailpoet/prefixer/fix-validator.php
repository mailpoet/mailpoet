<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

// throw exception if anything fails
set_error_handler(function ($severity, $message, $file, $line) {
  throw new ErrorException($message, 0, $severity, $file, $line);
});

require_once __DIR__ . '/replace-in-file.php';

replaceInFile(
  __DIR__ . '/../vendor-prefixed/symfony/validator/Mapping/PropertyMetadata.php',
  ['$member->setAccessible(\true);'],
  'if (PHP_VERSION_ID < 80100) { $member->setAccessible( \true ); }' // Add spaces in bracket to avoid multiple replacements
);

// cleanup unused Validator paths
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/symfony/validator/DataCollector');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/symfony/validator/DependencyInjection');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/symfony/validator/Resources');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/symfony/validator/Test');

// cleanup unused Translator paths
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/symfony/translation/Catalogue');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/symfony/translation/Command');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/symfony/translation/DataCollector');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/symfony/translation/DependencyInjection');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/symfony/translation/Dumper');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/symfony/translation/Extractor');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/symfony/translation/Formatter');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/symfony/translation/Reader');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/symfony/translation/Loader');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/symfony/translation/Resources');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/symfony/translation/Writer');
