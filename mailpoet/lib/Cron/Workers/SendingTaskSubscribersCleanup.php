<?php declare(strict_types = 1);

namespace MailPoet\Cron\Workers;

use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Newsletter\Sending\ScheduledTaskSubscribersRepository;
use MailPoet\Settings\SettingsController;
use MailPoetVendor\Carbon\Carbon;

/**
 * Purges old rows from scheduled_task_subscribers for completed sending tasks.
 *
 * Runs 4 times per day in random 6-hour slots (0–5h, 6–11h, 12–17h, 18–23h).
 * Each run loops in batches: first selects up to TASK_BATCH_SIZE completed tasks
 * older than the configured retention period that still have subscriber rows,
 * then deletes up to ROW_BATCH_SIZE rows per iteration. The loop continues until
 * fewer rows are deleted than the limit or MAX_EXECUTION_TIME is exceeded.
 * A 100ms pause between iterations throttles I/O on shared hosting.
 *
 * Retention is controlled by the 'sending_status_retention_days' setting.
 * Empty string means "Never" — the worker skips all deletions.
 */
class SendingTaskSubscribersCleanup extends SimpleWorker {
  const TASK_TYPE = 'sending_task_subscribers_cleanup';
  const TASK_BATCH_SIZE = 200;
  const ROW_BATCH_SIZE = 10000;
  const MAX_EXECUTION_TIME = 30;
  const SUPPORT_MULTIPLE_INSTANCES = false;

  /** @var ScheduledTaskSubscribersRepository */
  private $scheduledTaskSubscribersRepository;

  /** @var SettingsController */
  private $settings;

  public function __construct(
    ScheduledTaskSubscribersRepository $scheduledTaskSubscribersRepository,
    SettingsController $settings
  ) {
    $this->scheduledTaskSubscribersRepository = $scheduledTaskSubscribersRepository;
    $this->settings = $settings;
    parent::__construct();
  }

  public function processTaskStrategy(ScheduledTaskEntity $task, $timer) {
    $retentionDays = (int)$this->settings->get('sending_status_retention_days', '');

    if ($retentionDays <= 0) {
      return true;
    }

    $startTime = microtime(true);

    do {
      $this->cronHelper->enforceExecutionLimit($timer);

      $deleted = $this->scheduledTaskSubscribersRepository->purgeOldTaskSubscribers(
        $retentionDays,
        self::TASK_BATCH_SIZE,
        self::ROW_BATCH_SIZE
      );

      if (
        $deleted < self::ROW_BATCH_SIZE ||
          (microtime(true) - $startTime) > self::MAX_EXECUTION_TIME
      ) {
        break;
      }

      usleep(100000);
    } while (true);

    return true;
  }

  public function schedule() {
    $baseDate = Carbon::now()->millisecond(0)->startOfDay();

    for ($slot = 0; $slot < 4; $slot++) {
      $hour = $slot * 6 + mt_rand(0, 5);
      $minute = mt_rand(0, 59);
      $second = mt_rand(0, 59);

      $scheduleDate = clone $baseDate;
      $scheduleDate->setTime($hour, $minute, $second);

      if ($scheduleDate->isPast()) {
        $scheduleDate->addDay();
      }

      $this->cronWorkerScheduler->scheduleMultiple(static::TASK_TYPE, $scheduleDate);
    }
  }

  public function getNextRunDate() {
    $date = Carbon::now()->millisecond(0);
    $timeSlot = mt_rand(0, 3);
    $hour = $timeSlot * 6 + mt_rand(0, 5);
    $minute = mt_rand(0, 59);

    $date->setTime($hour, $minute, mt_rand(0, 59));

    if ($date->isPast()) {
      $date->addDay();
    }

    return $date;
  }
}
