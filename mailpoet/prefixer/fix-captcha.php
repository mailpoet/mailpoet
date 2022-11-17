<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing
// throw exception if anything fails
set_error_handler(function ($severity, $message, $file, $line) {
  throw new ErrorException($message, 0, $severity, $file, $line);
});


// The library Gregwar/Captcha contains an incompatibility with PHP 8.1.
// It looks like the plugin is not maintained anymore.
// PR with a fix https://github.com/Gregwar/Captcha/pull/101
$replacements = [
  [
    'file' => '../vendor-prefixed/gregwar/captcha/src/Gregwar/Captcha/CaptchaBuilder.php',
    'find' => [
      '$size = $width / $length - $this->rand(0, 3) - 1;',
      '$x = ($width - $textWidth) / 2;',
      '$y = ($height - $textHeight) / 2 + $size;',
      '$value = \mt_rand($min, $max);',
    ],
    'replace' => [
      '$size = (int) round($width / $length) - $this->rand(0, 3) - 1;',
      '$x = (int) round(($width - $textWidth) / 2);',
      '$y = (int) round(($height - $textHeight) / 2) + $size;',
      '$value = \mt_rand((int) $min, (int)$max);',
    ],
  ],
];

foreach ($replacements as $singleFile) {
  $data = file_get_contents($singleFile['file']);
  $data = str_replace($singleFile['find'], $singleFile['replace'], $data);
  file_put_contents($singleFile['file'], $data);
}
