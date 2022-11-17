<?php declare(strict_types = 1);

namespace MailPoet\Cron\Workers;

use MailPoet\Config\ServicesChecker;
use MailPoet\Cron\CronWorkerScheduler;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Services\Bridge;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Note: The test is written as a unit test but it can't be moved to unit test test suite because
 * the parent class (SimpleWorker) requires DB connection and some dependencies can't be mocked.
 * We can move the test to the unit test after SimpleWorker is fully refactored to DI
 */
class SubscribersStatsReportTest extends \MailPoetTest {

  /** @var SubscribersStatsReport */
  private $worker;

  /** @var Bridge & MockObject */
  private $bridgeMock;

  /** @var CronWorkerScheduler & MockObject */
  private $schedulerMock;

  /** @var ServicesChecker & MockObject */
  private $servicesCheckerMock;

  /** @var WPFunctions & MockObject */
  private $wpMock;

  public function _before() {
    parent::_before();
    $this->bridgeMock = $this->createMock(Bridge::class);
    $this->schedulerMock = $this->createMock(CronWorkerScheduler::class);
    $this->servicesCheckerMock = $this->createMock(ServicesChecker::class);
    $this->wpMock = $this->createMock(WPFunctions::class);
    $this->worker = new SubscribersStatsReport(
      $this->bridgeMock,
      $this->servicesCheckerMock,
      $this->schedulerMock,
      $this->wpMock
    );
  }

  public function testItFailsRequirementsCheckIfThereIsNoValidKey() {
    $this->servicesCheckerMock->expects($this->once())
      ->method('getAnyValidKey')
      ->willReturn(null);
    expect($this->worker->checkProcessingRequirements())->false();
  }

  public function testItSucceedsRequirementsCheckIfThereIsValidKey() {
    $this->servicesCheckerMock->expects($this->once())
      ->method('getAnyValidKey')
      ->willReturn('a_valid_key');
    expect($this->worker->checkProcessingRequirements())->true();
  }

  public function testItReportsCountToBridge() {
    $task = new ScheduledTaskEntity();
    $timer = time();
    $this->servicesCheckerMock->expects($this->once())
      ->method('getAnyValidKey')
      ->willReturn('a_valid_key');
    $this->bridgeMock->expects($this->once())
      ->method('updateSubscriberCount')
      ->willReturn(true);
    expect($this->worker->processTaskStrategy($task, $timer))->true();
  }

  public function testItDontReportCountToBridgeIfThereIsNoValidKey() {
    $task = new ScheduledTaskEntity();
    $timer = time();
    $this->servicesCheckerMock->expects($this->once())
      ->method('getAnyValidKey')
      ->willReturn(null);
    $this->bridgeMock->expects($this->never())
      ->method('updateSubscriberCount');
    expect($this->worker->processTaskStrategy($task, $timer))->false();
  }

  public function testItRescheduleTaskInCaseTheStatsReportFailed() {
    $task = new ScheduledTaskEntity();
    $timer = time();
    $this->servicesCheckerMock->expects($this->once())
      ->method('getAnyValidKey')
      ->willReturn('a_valid_key');
    $this->bridgeMock->expects($this->once())
      ->method('updateSubscriberCount')
      ->willReturn(false);
    $this->schedulerMock->expects($this->once())
      ->method('rescheduleProgressively');
    expect($this->worker->processTaskStrategy($task, $timer))->false();
  }

  public function testItGeneratesRandomNextRunDate() {
    $time = time();
    $maxExpectedScheduleInterval = 30 * 60 * 60; // 30 hours
    $this->wpMock->expects($this->exactly(2))
      ->method('currentTime')
      ->willReturn($time);
    $result = $this->worker->getNextRunDate();
    expect($result)->isInstanceOf(Carbon::class);
    expect($result->getTimestamp())->greaterThan($time);
    expect($result->getTimestamp())->lessThan($time + $maxExpectedScheduleInterval);
    $result2 = $this->worker->getNextRunDate();
    expect($result2)->isInstanceOf(Carbon::class);
    expect($result2->getTimestamp())->greaterThan($time);
    expect($result2->getTimestamp())->lessThan($time + $maxExpectedScheduleInterval);
    expect($result2->getTimestamp())->notEquals($result->getTimestamp());
  }
}
