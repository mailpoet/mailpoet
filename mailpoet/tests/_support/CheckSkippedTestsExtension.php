<?php

use Codeception\Event\SuiteEvent;
use Codeception\Events;
use Codeception\Extension;

// phpcs:ignore PSR1.Classes.ClassDeclaration
class CheckSkippedTestsExtension extends Extension {
  public static $events = [
    Events::SUITE_AFTER => 'checkErrorsAfterTests',
  ];

  public function checkErrorsAfterTests(SuiteEvent $e) {
    $branch = getenv('CIRCLE_BRANCH');
    $skipped = $e->getResult()->skipped();
    if (in_array($branch, ['trunk', 'release']) && (count($skipped) !== 0)) {
      throw new \PHPUnit\Framework\ExpectationFailedException("Failed, Cannot skip tests on branch $branch.");
    }
  }
}
