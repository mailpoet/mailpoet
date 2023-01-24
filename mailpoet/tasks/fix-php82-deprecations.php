<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

// throw exception if anything fails
set_error_handler(function ($severity, $message, $file, $line) {
  throw new ErrorException($message, 0, $severity, $file, $line);
});

// Fixes for PHP8.2 Compatibility

// Production packages
$replacements = [
  [
    'file' => __DIR__ . '/../vendor-prefixed/gregwar/captcha/src/Gregwar/Captcha/CaptchaBuilder.php',
    'find' => [
      'protected $backgroundColor = null;' . "\n" . '    /**',
    ],
    'replace' => [
      'protected $backgroundColor = null;' . PHP_EOL . '    protected $background = null;' . PHP_EOL . '    /**',
    ],
  ],
];

// Apply replacements
foreach ($replacements as $singleFile) {
  $data = file_get_contents($singleFile['file']);
  $data = str_replace($singleFile['find'], $singleFile['replace'], $data);
  file_put_contents($singleFile['file'], $data);
}
