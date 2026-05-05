<?php declare(strict_types = 1);

namespace MailPoet\Subscribers;

use MailPoet\Cron\CronWorkerScheduler;
use MailPoet\Cron\Workers\SubscriberLimitNotificationWorker;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\WP\Functions as WPFunctions;

class SubscriberLimitNotificationScheduler {

  /** @var CronWorkerScheduler */
  private $cronWorkerScheduler;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    CronWorkerScheduler $cronWorkerScheduler,
    WPFunctions $wp
  ) {
    $this->cronWorkerScheduler = $cronWorkerScheduler;
    $this->wp = $wp;
  }

  public function setupHooks(): void {
    $this->wp->addAction(
      SubscriberEntity::HOOK_SUBSCRIBERS_COUNT_CHANGED,
      [$this, 'schedule'],
      10,
      1
    );
  }

  /**
   * @param int[] $subscriberIds Subscriber IDs passed by the hook; the worker fetches a fresh count.
   */
  public function schedule(array $subscriberIds = []): void {
    unset($subscriberIds);
    $this->cronWorkerScheduler->scheduleImmediatelyIfNotRunning(SubscriberLimitNotificationWorker::TASK_TYPE);
  }
}
