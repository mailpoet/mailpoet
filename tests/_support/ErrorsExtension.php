<?php

use Codeception\Events;
use Codeception\Extension;

class ErrorsExtension extends Extension { // phpcs:ignore PSR1.Classes.ClassDeclaration
  const ERROR_LOG_PATHS = [
    __DIR__ . '/../_output/exceptions/error.log',
    __DIR__ . '/../_output/exceptions/exception.log',
  ];

  private $known_error_counts = [];

  static $events = [
    Events::SUITE_BEFORE => 'loadErrorCountsBeforeSuite',
    Events::TEST_AFTER => 'checkErrorsAfterTest',
  ];

  function loadErrorCountsBeforeSuite() {
    foreach (self::ERROR_LOG_PATHS as $path) {
      $this->known_error_counts[$path] = count($this->readFileToArray($path));
    }
  }

  function checkErrorsAfterTest() {
    foreach (self::ERROR_LOG_PATHS as $path) {
      $errors = $this->readFileToArray($path);
      foreach (array_slice($errors, $this->known_error_counts[$path]) as $error) {
        $this->output->writeln("<error>$error</error>");
        $this->known_error_counts[$path]++;
      }
    }
  }

  private function readFileToArray($path) {
    return file_exists($path) ? file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
  }
}
