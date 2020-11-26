<?php

// throw exception if anything fails
set_error_handler(function ($severity, $message, $file, $line) {
  throw new ErrorException($message, 0, $severity, $file, $line);
});

// Skip for production build
if (!file_exists(__DIR__ . '/../vendor/phpunit/phpunit/src/Framework/MockObject/MockMethod.php')) {
  exit;
}

$replacements = [
  // Fixes for PHP8 Compatibility
  [
    'file' => __DIR__ . '/../vendor/phpunit/phpunit/src/Framework/MockObject/MockMethod.php',
    'find' => [
      '$class = $parameter->getClass();',
    ],
    'replace' => [
      '$class = $parameter->hasType() && $parameter->getType() && !$parameter->getType()->isBuiltin() ? new ReflectionClass($parameter->getType()->getName()) : null;',
    ],
  ],
];

// Apply replacements
foreach ($replacements as $singleFile) {
  $data = file_get_contents($singleFile['file']);
  $data = str_replace($singleFile['find'], $singleFile['replace'], $data);
  file_put_contents($singleFile['file'], $data);
}
