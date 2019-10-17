<?php

use Codeception\Event\TestEvent;
use Codeception\Events;
use Codeception\Extension;

class ErrorsExtension extends Extension { // phpcs:ignore PSR1.Classes.ClassDeclaration
  const ERROR_LOG_PATH = __DIR__ . '/../_output/exceptions/error.log';

  private $errors = [];

  static $events = [
    Events::TEST_BEFORE => 'checkErrorsBeforeTests',
    Events::TEST_AFTER => 'checkErrorsAfterTests',
  ];

  function checkErrorsBeforeTests(TestEvent $e) {
    if (!file_exists(self::ERROR_LOG_PATH)) {
      return;
    }
    $this->errors = file(self::ERROR_LOG_PATH, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
  }

  function checkErrorsAfterTests(TestEvent $e) {
    if (!file_exists(self::ERROR_LOG_PATH)) {
      return;
    }

    $errors = file(self::ERROR_LOG_PATH, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (count($this->errors) === count($errors)) {
      return;
    }

    foreach (array_slice($errors, count($this->errors)) as $error) {
      $this->output->writeln("<error>$error</error>");
    }
  }
}
