<?php declare(strict_types = 1);

namespace MailPoet\Cron\Workers;

use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Logging\LogRepository;
use MailPoetVendor\Carbon\Carbon;

class LogCleanup extends SimpleWorker {
  const TASK_TYPE = 'log_cleanup';
  const DAYS_TO_KEEP_LOGS = 30;
  const BATCH_SIZE = 5000;
  const MAX_EXECUTION_TIME = 30;

  /** @var LogRepository */
  private $logRepository;

  public function __construct(
    LogRepository $logRepository
  ) {
    $this->logRepository = $logRepository;
    parent::__construct();
  }

  public function processTaskStrategy(ScheduledTaskEntity $task, $timer) {
    $startTime = microtime(true);

    do {
      $this->cronHelper->enforceExecutionLimit($timer);

      $deleted = $this->logRepository->purgeOldLogs(
        self::DAYS_TO_KEEP_LOGS,
        self::BATCH_SIZE
      );

      // Stop if we've deleted less than the batch size (meaning we're done)
      // or if we've exceeded the maximum execution time
      if (
        $deleted < self::BATCH_SIZE ||
          (microtime(true) - $startTime) > self::MAX_EXECUTION_TIME
      ) {
        break;
      }

    } while (true);

    return true;
  }

  public function schedule() {
    // Schedule four tasks per day in different 6-hour time slots
    $baseDate = Carbon::now()->millisecond(0)->startOfDay();

    // Schedule tasks for each 6-hour time slot
    for ($slot = 0; $slot < 4; $slot++) {
      $hour = $slot * 6 + mt_rand(0, 5); // 0-5, 6-11, 12-17, 18-23
      $minute = mt_rand(0, 59);
      $second = mt_rand(0, 59);

      $scheduleDate = clone $baseDate;
      $scheduleDate->setTime($hour, $minute, $second);

      // If the time has already passed today, schedule for tomorrow
      if ($scheduleDate->isPast()) {
        $scheduleDate->addDay();
      }

      $this->cronWorkerScheduler->schedule(static::TASK_TYPE, $scheduleDate);
    }
  }

  public function getNextRunDate() {
    // Return a single next run date for backward compatibility
    // The actual scheduling is handled by the overridden schedule() method
    $date = Carbon::now()->millisecond(0);

    // Choose one of four 6-hour time slots
    $timeSlot = mt_rand(0, 3);
    $hour = $timeSlot * 6 + mt_rand(0, 5); // 0-5, 6-11, 12-17, 18-23
    $minute = mt_rand(0, 59);

    $date->setTime($hour, $minute, mt_rand(0, 59));

    // If the chosen time has already passed today, schedule for tomorrow
    if ($date->isPast()) {
      $date->addDay();
    }

    return $date;
  }
}
