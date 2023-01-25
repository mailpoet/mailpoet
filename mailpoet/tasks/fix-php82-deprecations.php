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
  [
    'file' => __DIR__ . '/../vendor/soundasleep/html2text/src/Html2Text.php',
    'find' => [
      '$html = mb_convert_encoding($html, "HTML-ENTITIES", "UTF-8");',
    ],
    'replace' => [
      '// HTML-ENTITIES mbstring encoder is deprecated since PHP 8.2,' . PHP_EOL .
      "\t\t\t" . '// replaced by htmlentities() and htmlspecialchars_decode() as per' . PHP_EOL .
      "\t\t\t" . '// https://php.watch/versions/8.2/mbstring-qprint-base64-uuencode-html-entities-deprecated#html' . PHP_EOL .
      "\t\t\t" . '$html = htmlentities($html);' . PHP_EOL .
      "\t\t\t" . '$html = htmlspecialchars_decode($html);',
    ],
  ],
];

// Apply replacements
foreach ($replacements as $singleFile) {
  $data = file_get_contents($singleFile['file']);
  $data = str_replace($singleFile['find'], $singleFile['replace'], $data);
  file_put_contents($singleFile['file'], $data);
}
