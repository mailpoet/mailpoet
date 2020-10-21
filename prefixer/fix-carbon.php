<?php

// throw exception if anything fails
set_error_handler(function ($severity, $message, $file, $line) {
  throw new ErrorException($message, 0, $severity, $file, $line);
});

$replacements = [
  // Fix for PHP 7.4 deprecation warnings for nested ternary operators without brackets
  // This is caused by a bug in PHP Parser used within PHP Scoper. We can remove it after we update to PHP Scoper
  // that contains updated PHP Parser.
  [
    'file' => '../vendor-prefixed/nesbot/carbon/src/Carbon/CarbonInterval.php',
    'find' => [
      '$relativeToNow ? $isFuture ? \'from_now\' : \'ago\' : ($isFuture ? \'after\' : \'before\')',
      'func_num_args() === 0 ? !$this->invert : $inverted ? 1 : 0',
    ],
    'replace' => [
      '$relativeToNow ? ($isFuture ? \'from_now\' : \'ago\') : ($isFuture ? \'after\' : \'before\')',
      'func_num_args() === 0 ? !$this->invert : ($inverted ? 1 : 0)',
    ],
  ],
  // Remove unnecessary class alias (fix for Symfony) that breaks PHPStan
  [
    'file' => '../vendor-prefixed/nesbot/carbon/src/Carbon/Traits/Localization.php',
    'find' => [
      "/if \\(!\\\\interface_exists\\('MailPoetVendor\\\\\\\\Symfony\\\\\\\\Component\\\\\\\\Translation\\\\\\\\TranslatorInterface'\\)\\) {[a-z_(',);\\s\\\\]+}/i",
    ],
    'replace' => [
      '',
    ],
    'regular' => true,
  ],
];

// Apply replacements
foreach ($replacements as $singleFile) {
  $data = file_get_contents($singleFile['file']);
  if (isset($singleFile['regular']) && $singleFile['regular']) {
    $data = preg_replace($singleFile['find'], $singleFile['replace'], $data);
  } else {
    $data = str_replace($singleFile['find'], $singleFile['replace'], $data);
  }
  file_put_contents($singleFile['file'], $data);
}

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

// cleanup file types by extension
exec('find ' . __DIR__ . "/../vendor-prefixed/nesbot/carbon -type f -name '*.xml' -delete");
exec('find ' . __DIR__ . "/../vendor-prefixed/nesbot/carbon -type f -name '*.neon' -delete");

// cleanup
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/nesbot/Carbon/bin');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/nesbot/carbon/src/Carbon/Cli');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/nesbot/carbon/src/Carbon/PHPStan');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/nesbot/carbon/src/Carbon/Laravel');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/nesbot/carbon/src/Carbon/Doctrine');
