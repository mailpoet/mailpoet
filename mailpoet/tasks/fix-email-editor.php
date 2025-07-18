<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

// throw exception if anything fails
set_error_handler(function ($severity, $message, $file, $line) {
  throw new ErrorException($message, 0, $severity, $file, $line);
});

// This fix can be removed after bundling the Html2Text library into the email-editor package.
$rendererFilePath = __DIR__ . '/../vendor/woocommerce/email-editor/src/Engine/Renderer/class-renderer.php§';

if (!file_exists($rendererFilePath)) {
  exit;
}

// Replaces using Soundasleep\Html2Text with the prefixed class from the vendor-prefixed directory.
$replacements = [
  [
    'file' => $rendererFilePath,
    'find' => [
      'use Soundasleep\Html2Text;',
    ],
    'replace' => [
      'use MailPoetVendor\Html2Text\Html2Text;',
    ],
  ],
];

// Apply replacements
foreach ($replacements as $singleFile) {
  $data = file_get_contents($singleFile['file']);
  $data = str_replace($singleFile['find'], $singleFile['replace'], $data);
  file_put_contents($singleFile['file'], $data);
}
