<?php declare(strict_types = 1);

namespace MailPoet\Test\Newsletter\Editor;

use MailPoet\Newsletter\Scheduler\Scheduler;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;
use PHPUnit\Framework\MockObject\MockObject;

class SchedulerTest extends \MailPoetUnitTest {
  /** @var WPFunctions */
  private $wp;

  /** @var Carbon */
  private $currentTime;

  /** @var Scheduler */
  private $testee;

  public function _before() {
    parent::_before();
    $this->currentTime = Carbon::now();
    Carbon::setTestNow($this->currentTime);

    /** @var WPFunctions|MockObject $wp - for phpstan*/
    $wp = $this->makeEmpty(WPFunctions::class, [
      'currentTime' => $this->currentTime->getTimestamp(),
    ]);
    $this->wp = $wp;
    $this->testee = new Scheduler($this->wp);
  }

  public function testItScheduleTimeWithDelayByHours(): void {
    $scheduledAt = $this->testee->getScheduledTimeWithDelay('hours', 6, $this->wp);
    $expectedDate = (Carbon::createFromTimestamp($this->currentTime->timestamp))->addHours(6);
    expect($scheduledAt)->equals($expectedDate);

    $scheduledAt = $this->testee->getScheduledTimeWithDelay('hours', 38, $this->wp);
    $expectedDate = (Carbon::createFromTimestamp($this->currentTime->timestamp))->addHours(38);
    expect($scheduledAt)->equals($expectedDate);
  }

  public function testItScheduleTimeWithDelayByDays(): void {
    $scheduledAt = $this->testee->getScheduledTimeWithDelay('days', 23, $this->wp);
    $expectedDate = (Carbon::createFromTimestamp($this->currentTime->timestamp))->addDays(23);
    expect($scheduledAt)->equals($expectedDate);
  }

  public function testItScheduleTimeWithDelayByWeek(): void {
    $scheduledAt = $this->testee->getScheduledTimeWithDelay('weeks', 2, $this->wp);
    $expectedDate = (Carbon::createFromTimestamp($this->currentTime->timestamp))->addWeeks(2);
    expect($scheduledAt)->equals($expectedDate);

    $scheduledAt = $this->testee->getScheduledTimeWithDelay('weeks', 14, $this->wp);
    $expectedDate = (Carbon::createFromTimestamp($this->currentTime->timestamp))->addWeeks(14);
    expect($scheduledAt)->equals($expectedDate);
  }

  public function testItDoesNotScheduleTimeWithDelayOutOfRange(): void {
    $scheduledAt = $this->testee->getScheduledTimeWithDelay('weeks', 4000, $this->wp);
    $maxDate = Carbon::createFromFormat('Y-m-d H:i:s', Scheduler::MYSQL_TIMESTAMP_MAX);
    expect($scheduledAt)->equals($maxDate);
  }
}
