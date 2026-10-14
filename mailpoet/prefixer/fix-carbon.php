<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

// throw exception if anything fails
set_error_handler(function ($severity, $message, $file, $line) {
  throw new ErrorException($message, 0, $severity, $file, $line);
});

require_once __DIR__ . '/replace-in-file.php';

// Remove all locales except default english
// We don't use Carbon translate capabilities so we keep only default english locale to reduce size of the library
exec('find ' . __DIR__ . "/../vendor-prefixed/nesbot/carbon/src/Carbon/Lang -type f -not -name 'en.php' -delete");
$langList = <<<'LANGUGES'
<?php
return [
  'en' => [
    'isoName' => 'English',
    'nativeName' => 'English',
  ],
];
LANGUGES;
file_put_contents(__DIR__ . '/../vendor-prefixed/nesbot/carbon/src/Carbon/List/languages.php', $langList);

// @mixin annotation causes an error in Doctrine Annotations
foreach (['Carbon.php', 'CarbonImmutable.php', 'Traits/Date.php'] as $carbonFile) {
  replaceInFile(__DIR__ . '/../vendor-prefixed/nesbot/carbon/src/Carbon/' . $carbonFile, ['@mixin DeprecatedProperties'], '');
}

// setAccessible() is deprecated in PHP 8.5 and has no effect since PHP 8.1
// The spaces keep the replacement from containing its own find string
replaceInFile(
  __DIR__ . '/../vendor-prefixed/nesbot/carbon/src/Carbon/Traits/Mixin.php',
  ['$method->setAccessible(\true);'],
  'if (PHP_VERSION_ID < 80100) { $method->setAccessible( \true ); }'
);

// cleanup file types by extension
exec('find ' . __DIR__ . "/../vendor-prefixed/nesbot/carbon -type f -name '*.xml' -delete");
exec('find ' . __DIR__ . "/../vendor-prefixed/nesbot/carbon -type f -name '*.neon' -delete");

// cleanup
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/nesbot/carbon/bin');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/nesbot/carbon/lazy');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/nesbot/carbon/src/Carbon/Cli');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/nesbot/carbon/src/Carbon/PHPStan');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/nesbot/carbon/src/Carbon/Laravel');
