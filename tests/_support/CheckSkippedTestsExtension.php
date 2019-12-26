<?php

use Codeception\Event\SuiteEvent;
use Codeception\Events;
use Codeception\Extension;

class CheckSkippedTestsExtension extends Extension { // phpcs:ignore PSR1.Classes.ClassDeclaration
  static $events = [
    Events::SUITE_AFTER => 'checkErrorsAfterTests',
  ];

  public function checkErrorsAfterTests(SuiteEvent $e) {
    $branch = getenv('CIRCLE_BRANCH');
    $skipped = $e->getResult()->skipped();
    if (in_array($branch, ['master', 'release']) && (count($skipped) !== 0)) {
      throw new PHPUnit_Framework_ExpectationFailedException("Failed, Cannot skip tests on branch $branch.");
    }
  }
}
