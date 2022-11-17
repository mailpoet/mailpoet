<?php declare(strict_types = 1);

namespace MailPoet\Cron\Workers;

use MailPoet\Config\ServicesChecker;
use MailPoet\Cron\CronWorkerScheduler;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Services\Bridge;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

class SubscribersStatsReport extends SimpleWorker {
  const TASK_TYPE = 'subscribers_stats_report';

  /** @var Bridge */
  private $bridge;

  /** @var ServicesChecker */
  private $serviceChecker;

  /** @var CronWorkerScheduler */
  private $workerScheduler;

  public function __construct(
    Bridge $bridge,
    ServicesChecker $servicesChecker,
    CronWorkerScheduler $workerScheduler,
    WPFunctions $wp
  ) {
    parent::__construct($wp);
    $this->bridge = $bridge;
    $this->serviceChecker = $servicesChecker;
    $this->workerScheduler = $workerScheduler;
  }

  public function checkProcessingRequirements() {
    return (bool)$this->serviceChecker->getAnyValidKey();
  }

  public function processTaskStrategy(ScheduledTaskEntity $task, $timer): bool {
    $key = $this->serviceChecker->getAnyValidKey();
    if ($key === null) {
      return false;
    }
    $result = $this->bridge->updateSubscriberCount($key);
    // We have a valid key, but request failed
    if ($result === false) {
      $this->workerScheduler->rescheduleProgressively($task);
    }
    return $result;
  }

  public function getNextRunDate() {
    $date = Carbon::createFromTimestamp($this->wp->currentTime('timestamp'));
    // Spread the check within 6 hours after midnight so that all plugins don't ping the service at the same time
    return $date->startOfDay()
      ->addDay()
      ->addHours(rand(0, 5))
      ->addMinutes(rand(0, 59))
      ->addSeconds(rand(0, 59));
  }
}
