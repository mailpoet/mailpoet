<?php

// throw exception if anything fails
set_error_handler(function ($severity, $message, $file, $line) {
  throw new ErrorException($message, 0, $severity, $file, $line);
});

$replacements = [
  // Fix deprecation warnings for nested ternary operators
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
