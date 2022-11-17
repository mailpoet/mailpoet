<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

// throw exception if anything fails
set_error_handler(function ($severity, $message, $file, $line) {
  throw new ErrorException($message, 0, $severity, $file, $line);
});

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
