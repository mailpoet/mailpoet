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
    self::$tmpCurrentTest = (new \ReflectionClass($e->getTest()))->getFileName();
  }

  // we are printing test status and time taken
  public function beforeStep(StepEvent $e) {
    list($usec, $sec) = explode(" ", microtime());
    self::$tmpStepStartTime = (float)$sec;
  }

  // we are printing test status and time taken
  public function afterStep(StepEvent $e) {
    list($usec, $sec) = explode(" ", microtime());
    $stepEndTime = (float)$sec;

    $stepTime = $stepEndTime - self::$tmpStepStartTime;

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
    $test->name = (new \ReflectionClass($e->getTest()))->getFileName();
    // stack overflow: http://stackoverflow.com/questions/16825240/how-to-convert-microtime-to-hhmmssuu
    $seconds_input = $e->getTime();
    $seconds = (int)($milliseconds = (int)($seconds_input * 1000)) / 1000;
    $time = ((int)$seconds % 60);
    $test->time = $time;
    self::$testTimes[] = $test;
  }

  public function afterSuite(SuiteEvent $e) {
    $this->writeln("");
    $this->writeln("Tests Performance times");
    $this->writeln("-----------------------------------------------");

    foreach (self::$testTimes as $test) {
      $this->writeln(str_pad($test->name, 35) . ' ' . $test->time . 's');
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
        $this->writeln('    ' . $step->name . '(' . $step->time . 's)');
      }
    }
  }
}
