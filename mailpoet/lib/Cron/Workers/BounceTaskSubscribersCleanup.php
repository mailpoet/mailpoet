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
 * Runs once an hour at a random minute past the hour (to spread DB load
 * across sites). Each run loops in batches: first selects up to
 * TASK_BATCH_SIZE completed bounce tasks that still have subscriber rows, then
 * deletes up to ROW_BATCH_SIZE rows per iteration. The loop continues until
 * fewer rows are deleted than the limit or MAX_EXECUTION_TIME is exceeded. A
 * 100ms pause between iterations throttles I/O on shared hosting.
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

  public function getNextRunDate() {
    return Carbon::now()->millisecond(0)
      ->startOfHour()
      ->addHour()
      ->addMinutes(mt_rand(0, 59))
      ->addSeconds(mt_rand(0, 59));
  }
}
