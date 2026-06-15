<?php declare(strict_types = 1);

namespace MailPoet\Cron\Workers;

use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Newsletter\Sending\ScheduledTaskSubscribersRepository;
use MailPoetVendor\Carbon\Carbon;

/**
 * Purges leftover rows from scheduled_task_subscribers for completed bounce tasks.
 *
 * Bounce tasks are recurring and no longer need their subscriber rows once
 * completed, so unlike SendingTaskSubscribersCleanup there is no retention
 * period — every completed, non-deleted bounce task that still has rows is
 * eligible.
 *
 * Runs 4 times per day in random 6-hour slots (0–5h, 6–11h, 12–17h, 18–23h).
 * Each run loops in batches: first selects up to TASK_BATCH_SIZE completed
 * bounce tasks that still have subscriber rows, then deletes up to
 * ROW_BATCH_SIZE rows per iteration. The loop continues until fewer rows are
 * deleted than the limit or MAX_EXECUTION_TIME is exceeded. A 100ms pause
 * between iterations throttles I/O on shared hosting.
 *
 * This is kept separate from the bounce task itself, which is expected to be
 * replaced by a more efficient one. Once that lands, the cleanup can stop as
 * soon as no bounce rows are found — which the batch loop already does.
 */
class BounceTaskSubscribersCleanup extends SimpleWorker {
  const TASK_TYPE = 'bounce_task_subscribers_cleanup';
  const TASK_BATCH_SIZE = 200;
  const ROW_BATCH_SIZE = 10000;
  const MAX_EXECUTION_TIME = 30;
  const SUPPORT_MULTIPLE_INSTANCES = false;

  /** @var ScheduledTaskSubscribersRepository */
  private $scheduledTaskSubscribersRepository;

  public function __construct(
    ScheduledTaskSubscribersRepository $scheduledTaskSubscribersRepository
  ) {
    $this->scheduledTaskSubscribersRepository = $scheduledTaskSubscribersRepository;
    parent::__construct();
  }

  public function processTaskStrategy(ScheduledTaskEntity $task, $timer) {
    $startTime = microtime(true);

    do {
      $this->cronHelper->enforceExecutionLimit($timer);

      $deleted = $this->scheduledTaskSubscribersRepository->purgeCompletedBounceTaskSubscribers(
        self::TASK_BATCH_SIZE,
        self::ROW_BATCH_SIZE
      );

      if (
        $deleted === 0 ||
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
