<?php

// throw exception if anything fails
set_error_handler(function ($severity, $message, $file, $line) {
  throw new ErrorException($message, 0, $severity, $file, $line);
});

// Fixes for PHP8.4 Compatibility - E_STRICT constant is deprecated

// Replacement for ErrorHandler.php line 46
$errorHandlerConstructorFind = <<<'CODE'
    public function __construct()
    {
        $this->errorLevel = E_ALL & ~E_STRICT & ~E_DEPRECATED;
    }
CODE;

$errorHandlerConstructorReplace = <<<'CODE'
    public function __construct()
    {
      $this->errorLevel = PHP_VERSION_ID >= 80400 ? (E_ALL & ~E_DEPRECATED) : (E_ALL & ~E_STRICT & ~E_DEPRECATED);
    }
CODE;

// Replacement for ErrorHandler.php line 100 - Remove case E_STRICT
$errorHandlerSwitchFind = <<<'CODE'
                case E_DEPRECATED:
                case E_USER_DEPRECATED:
                    // Renamed to Deprecation in PHPUnit 10
                    throw new Deprecated($errstr, $errno, $errfile, $errline);
                case E_NOTICE:
                case E_STRICT:
                case E_USER_NOTICE:
                    throw new Notice($errstr, $errno, $errfile, $errline);
CODE;

$errorHandlerSwitchReplace = <<<'CODE'
                case E_DEPRECATED:
                case E_USER_DEPRECATED:
                    // Renamed to Deprecation in PHPUnit 10
                    throw new Deprecated($errstr, $errno, $errfile, $errline);
                case E_NOTICE:
                case E_USER_NOTICE:
                    throw new Notice($errstr, $errno, $errfile, $errline);
CODE;

// Replacement for Configuration.php line 135
$configurationErrorLevelFind = <<<'CODE'
        'error_level' => 'E_ALL & ~E_STRICT & ~E_DEPRECATED',
CODE;

$configurationErrorLevelReplace = <<<'CODE'
        'error_level' => PHP_VERSION_ID >= 80400 ? 'E_ALL & ~E_DEPRECATED' : 'E_ALL & ~E_STRICT & ~E_DEPRECATED',
CODE;

// Apply replacements
$replacements = [
  [
    'file' => __DIR__ . '/../vendor/codeception/codeception/src/Codeception/Subscriber/ErrorHandler.php',
    'find' => [
      $errorHandlerConstructorFind,
      $errorHandlerSwitchFind,
    ],
    'replace' => [
      $errorHandlerConstructorReplace,
      $errorHandlerSwitchReplace,
    ],
  ],
  [
    'file' => __DIR__ . '/../vendor/codeception/codeception/src/Codeception/Configuration.php',
    'find' => [
      $configurationErrorLevelFind,
    ],
    'replace' => [
      $configurationErrorLevelReplace,
    ],
  ],
];

// Apply replacements
foreach ($replacements as $singleFile) {
  $data = file_get_contents($singleFile['file']);
  $data = str_replace($singleFile['find'], $singleFile['replace'], $data);
  file_put_contents($singleFile['file'], $data);
}
