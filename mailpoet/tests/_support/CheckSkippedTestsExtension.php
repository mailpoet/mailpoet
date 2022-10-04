<?php

use Codeception\Event\FailEvent;
use Codeception\Events;
use Codeception\Extension;

// phpcs:ignore PSR1.Classes.ClassDeclaration
class CheckSkippedTestsExtension extends Extension {
  public static $events = [
    Events::TEST_SKIPPED => 'checkSkippedTests',
  ];

  public function checkSkippedTests(FailEvent $event) {
    $branch = getenv('CIRCLE_BRANCH');
    $testName = $event->getTest()->getName();

    // list of tests that are allowed to be skipped on trunk and release branches
    $allowedToSkipList = [];

    if (in_array($branch, ['trunk', 'release']) && !in_array($testName, $allowedToSkipList)) {
      throw new \PHPUnit\Framework\ExpectationFailedException("Failed, Cannot skip tests on branch $branch.");
    }
  }
}
