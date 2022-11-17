<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

// throw exception if anything fails
set_error_handler(function ($severity, $message, $file, $line) {
  throw new ErrorException($message, 0, $severity, $file, $line);
});

// Skip for production build
if (!file_exists(__DIR__ . '/../vendor/phpunit/phpunit/src/Framework/MockObject/MockMethod.php')) {
  exit;
}

if (!file_exists(__DIR__ . '/../vendor/phpunit/phpunit/src/Framework/MockObject/Builder/MockMatch.php')) {
  // Rename Match interface source file to Matcher
  exec('mv ' . __DIR__ . '/../vendor/phpunit/phpunit/src/Framework/MockObject/Builder/Match.php ' . __DIR__ . '/../vendor/phpunit/phpunit/src/Framework/MockObject/Builder/MockMatch.php');
}

// Fixes for PHP8 Compatibility
$replacements = [
  [
    'file' => __DIR__ . '/../vendor/phpunit/phpunit/src/Framework/MockObject/MockMethod.php',
    'find' => [
      '$class = $parameter->getClass();',
    ],
    'replace' => [
      '$class = $parameter->hasType() && $parameter->getType() && !$parameter->getType()->isBuiltin() ? new ReflectionClass($parameter->getType()->getName()) : null;',
    ],
  ],
  // Renaming Match Interface
  [
    'file' => __DIR__ . '/../vendor/phpunit/phpunit/src/Framework/MockObject/Builder/MockMatch.php',
    'find' => [
      'interface Match extends Stub',
    ],
    'replace' => [
      'interface MockMatch extends Stub',
    ],
  ],
  [
    'file' => __DIR__ . '/../vendor/phpunit/phpunit/src/Framework/MockObject/Builder/NamespaceMatch.php',
    'find' => [
      '* @return Match',
      ', Match $builder',
      'Match  $builder',
    ],
    'replace' => [
      '* @return MockMatch',
      ', MockMatch $builder',
      'MockMatch $builder',
    ],
  ],
  [
    'file' => __DIR__ . '/../vendor/phpunit/phpunit/src/Framework/MockObject/InvocationMocker.php',
    'find' => [
      'use PHPUnit\Framework\MockObject\Builder\Match;',
      '* @var Match[]',
      ', Match $builder',
    ],
    'replace' => [
      'use PHPUnit\Framework\MockObject\Builder\MockMatch;',
      '* @var MockMatch[]',
      ', MockMatch $builder',
    ],
  ],
  [
    'file' => __DIR__ . '/../vendor/phpunit/phpunit/src/Framework/MockObject/Builder/ParametersMatch.php',
    'find' => [
      'interface ParametersMatch extends Match',
    ],
    'replace' => [
      'interface ParametersMatch extends MockMatch',
    ],
  ],

];

// Apply replacements
foreach ($replacements as $singleFile) {
  $data = file_get_contents($singleFile['file']);
  $data = str_replace($singleFile['find'], $singleFile['replace'], $data);
  file_put_contents($singleFile['file'], $data);
}
