<?php declare(strict_types = 1);

namespace MailPoet\Test\Cron\Workers;

use Codeception\Stub;
use Codeception\Stub\Expected;
use Codeception\Stub\StubMarshaler;
use MailPoet\Cron\Workers\LogCleanup;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Logging\LogRepository;
use MailPoet\Test\DataFactories\Log;
use MailPoetVendor\Carbon\Carbon;

class LogCleanupTest extends \MailPoetTest {
  /** @var LogCleanup */
  private $worker;

  public function _before() {
    parent::_before();
    $this->worker = $this->diContainer->get(LogCleanup::class);
  }

  public function testItDeletesOldLogs() {
    $logFactory = new Log();

    // Freeze 'now' to avoid drift during assertions
    $now = Carbon::now();
    Carbon::setTestNow($now);

    // Create old logs that should be deleted
    $logFactory->withCreatedAt($now->copy()->subDays(50))->create();
    $logFactory->withCreatedAt($now->copy()->subDays(40))->create();

    // Create newer logs that should be kept
    $logFactory->withCreatedAt($now->copy()->subDays(20))->create();
    $logFactory->withCreatedAt($now->copy())->create();

    $task = new ScheduledTaskEntity();
    $this->worker->processTaskStrategy($task, microtime(true));

    // Verify old logs were deleted
    $remainingLogs = $this->entityManager->getRepository(\MailPoet\Entities\LogEntity::class)->findAll();
    verify(count($remainingLogs))->equals(2);

    // Verify only newer logs remain
    $cutoffDate = $now->copy()->subDays(LogCleanup::DAYS_TO_KEEP_LOGS);
    foreach ($remainingLogs as $log) {
      $createdAt = $log->getCreatedAt();
      verify($createdAt)->notNull();
      // After verify($createdAt)->notNull(), we know it's not null
      /** @var \DateTimeInterface $createdAt */
      verify($createdAt->getTimestamp())->greaterThan($cutoffDate->getTimestamp());
    }
  }

  public function testItKeepsDeletingUntilABatchComesBackShort() {
    $batches = [LogCleanup::BATCH_SIZE, LogCleanup::BATCH_SIZE, 10];
    $worker = $this->createWorkerWithPurge(Expected::exactly(3, function () use (&$batches) {
      return array_shift($batches);
    }));

    $worker->processTaskStrategy(new ScheduledTaskEntity(), microtime(true));
  }

  public function testItStopsAfterTheExecutionTimeLimit() {
    Carbon::setTestNow(Carbon::now());
    // every batch is full and takes just over half the limit, so the second one crosses it
    $worker = $this->createWorkerWithPurge(Expected::exactly(2, function () {
      Carbon::setTestNow(Carbon::now()->addSeconds((int)(LogCleanup::MAX_EXECUTION_TIME / 2) + 1));
      return LogCleanup::BATCH_SIZE;
    }));

    $worker->processTaskStrategy(new ScheduledTaskEntity(), microtime(true));
  }

  private function createWorkerWithPurge(StubMarshaler $purgeOldLogs): LogCleanup {
    $logRepository = Stub::make(LogRepository::class, ['purgeOldLogs' => $purgeOldLogs], $this);
    return new LogCleanup($logRepository);
  }

  public function testItSchedulesNextRun() {
    $nextRunDate = $this->worker->getNextRunDate();
    verify($nextRunDate)->notNull();
    verify($nextRunDate->getTimestamp())->greaterThan(Carbon::now()->getTimestamp());

    // Verify it's scheduled within the next 24 hours
    $tomorrow = Carbon::now()->addDay();
    verify($nextRunDate->getTimestamp())->lessThan($tomorrow->getTimestamp());
  }

  public function testItSchedulesMultipleTimesPerDay() {
    // Test that multiple calls can schedule for different times
    $schedules = [];
    for ($i = 0; $i < 10; $i++) {
      $schedule = $this->worker->getNextRunDate();
      $schedules[] = $schedule->format('H');
    }

    // Should have schedules in different time slots (0-5, 6-11, 12-17, 18-23)
    $timeSlots = array_unique(array_map(function($hour) {
      return intdiv(intval($hour), 6);
    }, $schedules));

    // Should have at least 2 different time slots
    verify(count($timeSlots))->greaterThan(1);
  }
}
