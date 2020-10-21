<?php

// throw exception if anything fails
set_error_handler(function ($severity, $message, $file, $line) {
  throw new ErrorException($message, 0, $severity, $file, $line);
});

$replacements = [
  // Replace usage of regexp from constant to avoid installing additional package symfony/config
  // The symfony/config causes issues with serialization in integration tests
  [
    'file' => '../vendor-prefixed/symfony/dependency-injection/Dumper/PhpDumper.php',
    'find' => [
      '\MailPoetVendor\Symfony\Component\DependencyInjection\Loader\FileLoader::ANONYMOUS_ID_REGEXP',
    ],
    'replace' => [
      "'/^\\.\\d+_[^~]*+~[._a-zA-Z\\d]{7}$/'",
    ],
  ],
];

// Apply replacements
foreach ($replacements as $singleFile) {
  $data = file_get_contents($singleFile['file']);
  $data = str_replace($singleFile['find'], $singleFile['replace'], $data);
  file_put_contents($singleFile['file'], $data);
}
