<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

// throw exception if anything fails
set_error_handler(function ($severity, $message, $file, $line) {
  throw new ErrorException($message, 0, $severity, $file, $line);
});

// Fixes for PHP8.2 Compatibility

$codeceptionStepReplacement = <<<'CODE'
$parentClass = get_parent_class($argument);
            $reflection = new \ReflectionClass($argument);

            if ($parentClass !== false) {
                return $this->formatClassName($parentClass);
            }

            $interfaces = $reflection->getInterfaceNames();
            foreach ($interfaces as $interface) {
                if (str_starts_with($interface, 'PHPUnit\\')) {
                    continue;
                }
                if (str_starts_with($interface, 'Codeception\\')) {
                    continue;
                }
                return $this->formatClassName($interface);
            }
CODE;

// Development packages
$replacements = [
  [
    'file' => __DIR__ . '/../vendor/codeception/stub/src/Stub.php',
    'find' => [
      '  $mock->__mocked = $reflection->getName();',
    ],
    'replace' => [
      '  //$mock->__mocked = $reflection->getName();',
    ],
  ],
  [
    'file' => __DIR__ . '/../vendor/codeception/codeception/src/Codeception/Step.php',
    'find' => [
      '} elseif ($argument instanceof MockObject && isset($argument->__mocked)) {',
      'return $this->formatClassName($argument->__mocked);',
    ],
    'replace' => [
      '} elseif ($argument instanceof MockObject) {',
      $codeceptionStepReplacement,
    ],
  ],
];

// Apply replacements
foreach ($replacements as $singleFile) {
  $data = file_get_contents($singleFile['file']);
  $data = str_replace($singleFile['find'], $singleFile['replace'], $data);
  file_put_contents($singleFile['file'], $data);
}
