<?php declare(strict_types = 1);

namespace MailPoet\Test\Cron\Workers;

use MailPoet\Cron\Workers\LogCleanup;
use MailPoet\Entities\ScheduledTaskEntity;
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

  public function testItRespectsBatchSize() {
    $logFactory = new Log();

    // Create more logs than the batch size
    for ($i = 0; $i < LogCleanup::BATCH_SIZE + 10; $i++) {
      $logFactory->withCreatedAt(Carbon::now()->subDays(50))->create();
    }

    $task = new ScheduledTaskEntity();
    $this->worker->processTaskStrategy($task, microtime(true));

    // Should have deleted at least the batch size
    $remainingLogs = $this->entityManager->getRepository(\MailPoet\Entities\LogEntity::class)->findAll();
    verify(count($remainingLogs))->lessThan(10);
  }

  public function testItRespectsExecutionTimeLimit() {
    $logFactory = new Log();

    // Create many old logs to ensure the worker runs for a while
    for ($i = 0; $i < LogCleanup::BATCH_SIZE * 3; $i++) {
      $logFactory->withCreatedAt(Carbon::now()->subDays(50))->create();
    }

    $startTime = microtime(true);
    $task = new ScheduledTaskEntity();
    $this->worker->processTaskStrategy($task, microtime(true));
    $executionTime = microtime(true) - $startTime;

    // Should respect the execution time limit
    verify($executionTime)->lessThan(LogCleanup::MAX_EXECUTION_TIME + 1); // Allow 1 second buffer
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
