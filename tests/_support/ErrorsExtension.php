<?php

use Codeception\Event\SuiteEvent;
use Codeception\Event\TestEvent;
use Codeception\Events;
use Codeception\Extension;
use PHPUnit\Framework\AssertionFailedError;

class ErrorsExtension extends Extension { // phpcs:ignore PSR1.Classes.ClassDeclaration
  const ERROR_LOG_PATHS = [
    __DIR__ . '/../_output/exceptions/error.log',
    __DIR__ . '/../_output/exceptions/exception.log',
  ];

  private $known_error_counts = [];
  private $errors = [];

  static $events = [
    Events::SUITE_BEFORE => 'loadErrorCountsBeforeSuite',
    Events::TEST_AFTER => 'checkErrorsAfterTest',
    Events::SUITE_AFTER => 'processErrorsAfterSuite',
  ];

  function loadErrorCountsBeforeSuite() {
    foreach (self::ERROR_LOG_PATHS as $path) {
      $this->known_error_counts[$path] = count($this->readFileToArray($path));
    }
  }

  function checkErrorsAfterTest(TestEvent $e) {
    foreach (self::ERROR_LOG_PATHS as $path) {
      $errors = $this->readFileToArray($path);
      foreach (array_slice($errors, $this->known_error_counts[$path]) as $error) {
        $this->output->writeln("<error>$error</error>");
        $this->errors[] = [$e->getTest(), new AssertionFailedError($error)];
        $this->known_error_counts[$path]++;
      }
    }
  }

  function processErrorsAfterSuite(SuiteEvent $event) {
    foreach ($this->errors as $error) {
      list($test, $exception) = $error;
      $event->getResult()->addFailure($test, $exception, microtime(true));
    }
  }

  private function readFileToArray($path) {
    return file_exists($path) ? file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
  }
}
