<?php

// throw exception if anything fails
set_error_handler(function ($severity, $message, $file, $line) {
  throw new ErrorException($message, 0, $severity, $file, $line);
});

// Fixes for PHP8.3 Compatibility when Generator classes use dynamic properties which are deprecated
$codeceptionActionsFind = <<<'CODE'
    protected $name;
    protected $settings;
    protected $modules = [];
    protected $actions;
    protected $numMethods = 0;
CODE;

$codeceptionActorFind = <<<'CODE'
    protected $settings;
    protected $modules;
    protected $actions;
CODE;

$codeceptionActorReplacement = <<<'CODE'
    protected $settings;
    protected $di;
    protected $moduleContainer;
    protected $modules;
    protected $actions;
CODE;

$codeceptionActionsReplacement = <<<'CODE'
    protected $name;
    protected $di;
    protected $moduleContainer;
    protected $settings;
    protected $modules = [];
    protected $actions;
    protected $numMethods = 0;
CODE;

// Development packages
$replacements = [
  [
    'file' => __DIR__ . '/../vendor/codeception/codeception/src/Codeception/Lib/Generator/Actions.php',
    'find' => [
      $codeceptionActionsFind
    ],
    'replace' => [
      $codeceptionActionsReplacement,
    ],
  ],
  [
    'file' => __DIR__ . '/../vendor/codeception/codeception/src/Codeception/Lib/Generator/Actor.php',
    'find' => [
      $codeceptionActorFind
    ],
    'replace' => [
      $codeceptionActorReplacement,
    ],
  ],
];

// Apply replacements
foreach ($replacements as $singleFile) {
  $data = file_get_contents($singleFile['file']);
  $data = str_replace($singleFile['find'], $singleFile['replace'], $data);
  file_put_contents($singleFile['file'], $data);
}
