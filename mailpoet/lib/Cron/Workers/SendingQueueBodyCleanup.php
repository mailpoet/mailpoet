<?php declare(strict_types = 1);

namespace MailPoet\Cron\Workers;

use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Newsletter\Sending\SendingQueuesRepository;
use MailPoet\Settings\SettingsController;
use MailPoetVendor\Carbon\Carbon;

/**
 * Runs 4x daily in staggered 6-hour slots. Each run NULLs newsletter_rendered_body
 * for completed sending queues older than the configured retention window (default 30 days).
 * Processes in batches of 250 rows with a 30s time limit per run.
 * Setting the retention to '' (Never) disables cleanup entirely.
 */
class SendingQueueBodyCleanup extends SimpleWorker {
  const TASK_TYPE = 'sending_queue_body_cleanup';
  const BATCH_SIZE = 250;
  const MAX_EXECUTION_TIME = 30;
  const SUPPORT_MULTIPLE_INSTANCES = false;

  /** @var SendingQueuesRepository */
  private $sendingQueuesRepository;

  /** @var SettingsController */
  private $settings;

  public function __construct(
    SendingQueuesRepository $sendingQueuesRepository,
    SettingsController $settings
  ) {
    $this->sendingQueuesRepository = $sendingQueuesRepository;
    $this->settings = $settings;
    parent::__construct();
  }

  public function processTaskStrategy(ScheduledTaskEntity $task, $timer) {
    $retentionDays = (int)$this->settings->get('sending_queue_body_retention_days');
    if ($retentionDays <= 0) {
      return true;
    }

    $startTime = microtime(true);
    do {
      $this->cronHelper->enforceExecutionLimit($timer);

      $updated = $this->sendingQueuesRepository->nullRenderedBodyForOldCompletedQueues(
        $retentionDays,
        self::BATCH_SIZE
      );

      if ($updated < self::BATCH_SIZE || (microtime(true) - $startTime) > self::MAX_EXECUTION_TIME) {
        break;
      }
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
