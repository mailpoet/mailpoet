<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

// throw exception if anything fails
set_error_handler(function ($severity, $message, $file, $line) {
  throw new ErrorException($message, 0, $severity, $file, $line);
});

// Remove all locales except default english
// We don't use Carbon translate capabilities so we keep only default english locale to reduce size of the library
exec('find ' . __DIR__ . "/../vendor-prefixed/nesbot/carbon/src/Carbon/Lang -type f -not -name 'en.php' -delete");
$langList = <<<LANGUGES
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
$replacements = [
  [
    'file' => '../vendor-prefixed/nesbot/carbon/src/Carbon/Carbon.php',
    'find' => [
      '@mixin DeprecatedProperties',
    ],
    'replace' => [
      '',
    ],
  ],
  [
    'file' => '../vendor-prefixed/nesbot/carbon/src/Carbon/CarbonImmutable.php',
    'find' => [
      '@mixin DeprecatedProperties',
    ],
    'replace' => [
      '',
    ],
  ],
  [
    'file' => '../vendor-prefixed/nesbot/carbon/src/Carbon/Traits/Date.php',
    'find' => [
      '@mixin DeprecatedProperties',
    ],
    'replace' => [
      '',
    ],
  ],
];

foreach ($replacements as $singleFile) {
  $data = file_get_contents($singleFile['file']);
  $data = str_replace($singleFile['find'], $singleFile['replace'], $data);
  file_put_contents($singleFile['file'], $data);
}

// cleanup file types by extension
exec('find ' . __DIR__ . "/../vendor-prefixed/nesbot/carbon -type f -name '*.xml' -delete");
exec('find ' . __DIR__ . "/../vendor-prefixed/nesbot/carbon -type f -name '*.neon' -delete");

// cleanup
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/nesbot/carbon/bin');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/nesbot/carbon/src/Carbon/Cli');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/nesbot/carbon/src/Carbon/PHPStan');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/nesbot/carbon/src/Carbon/Laravel');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/nesbot/carbon/src/Carbon/Doctrine');
