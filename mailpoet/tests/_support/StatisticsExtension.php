<?php declare(strict_types = 1);

/**
 * @package     teststatistics
 * @subpackage
 *
 * @copyright   Copyright (C) 2005 - 2015 redCOMPONENT.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

namespace MailPoet\TestsSupport;

use Codeception\Event\StepEvent;
use Codeception\Event\SuiteEvent;
use Codeception\Event\TestEvent;
use Codeception\Events;
use Codeception\Extension;
use Codeception\Test\Descriptor;
use Codeception\Test\Interfaces\Descriptive;
use PHPUnit\Framework\SelfDescribing;

class StatisticsExtension extends Extension {
  /**
   * Maximum time in second allowed for a step to be performant
   * @var int
   */
  public static $maxStepPerformantTime = 3;

  public static $testTimes = [];
  public static $notPerformantStepsByTest = [];
  public static $tmpCurrentTest = 0;
  public static $tmpStepStartTime = 0;

  // we are listening for events
  public static $events = [
    Events::TEST_BEFORE => 'beforeTest',
    Events::TEST_END => 'afterTest',
    Events::SUITE_AFTER => 'afterSuite',
    Events::STEP_BEFORE => 'beforeStep',
    Events::STEP_AFTER => 'afterStep',
  ];

  public function _initialize() {
    $this->options['silent'] = false; // turn on printing for this extension
  }

  // we are printing test status and time taken
  public function beforeTest(TestEvent $e) {
    self::$tmpCurrentTest = $this->testName($e);
  }

  // we are printing test status and time taken
  public function beforeStep(StepEvent $e) {
    self::$tmpStepStartTime = microtime(true);
  }

  // we are printing test status and time taken
  public function afterStep(StepEvent $e) {
    $stepTime = microtime(true) - self::$tmpStepStartTime;

    // If the Step has taken more than 5 seconds
    if ($stepTime > self::$maxStepPerformantTime) {
      $step = new \stdClass;
      $currentStep = (string)$e->getStep();
      $step->name = $currentStep;
      $step->time = $stepTime;

      self::$notPerformantStepsByTest[self::$tmpCurrentTest][] = $step;
    }
  }

  public function afterTest(TestEvent $e) {
    $test = new \stdClass;
    $test->name = $this->testName($e);
    $test->time = round($e->getTime(), 1);
    self::$testTimes[] = $test;
  }

  private function testName(TestEvent $e): string {
    $test = $e->getTest();
    if ($test instanceof Descriptive) {
      return $test->getSignature();
    }
    // PHPUnit-style tests: class and method
    return $test instanceof SelfDescribing ? Descriptor::getTestSignature($test) : get_class($test);
  }

  public function afterSuite(SuiteEvent $e) {
    $this->writeln("");
    $this->writeln("Tests Performance times");
    $this->writeln("-----------------------------------------------");

    usort(self::$testTimes, function ($a, $b) {
      return $b->time <=> $a->time;
    });
    foreach (self::$testTimes as $test) {
      $this->writeln(str_pad($test->name, 100) . ' ' . $test->time . 's');
    }

    $this->writeln("");
    $this->writeln("");
    $this->writeln("Slow Steps (Steps taking more than " . self::$maxStepPerformantTime . "s)");
    $this->writeln("-----------------------------------------------");
    foreach (self::$notPerformantStepsByTest as $testname => $steps) {
      $this->writeln("");
      $this->writeln("  TEST: " . $testname);
      $this->writeln("  ------------------------------------------");
      foreach ($steps as $step) {
        $this->writeln('    ' . $step->name . ' (' . round($step->time, 1) . 's)');
      }
    }
  }
}
