<?php

namespace MailPoet\Cron\Workers;

use MailPoet\Cron\CronHelper;
use MailPoet\Cron\CronWorkerInterface;
use MailPoet\Cron\CronWorkerRunner;
use MailPoet\Cron\CronWorkerScheduler;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Models\ScheduledTask;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

abstract class SimpleWorker implements CronWorkerInterface {
  const TASK_TYPE = null;
  const AUTOMATIC_SCHEDULING = true;
  const SUPPORT_MULTIPLE_INSTANCES = true;

  public $timer;

  /** @var WPFunctions */
  private $wp;

  /** @var CronHelper */
  protected $cron_helper;

  /** @var CronWorkerScheduler */
  protected $cron_worker_scheduler;

  function __construct() {
    if (static::TASK_TYPE === null) {
      throw new \Exception('Constant TASK_TYPE is not defined on subclass ' . get_class($this));
    }

    $this->wp = new WPFunctions();
    $this->cron_helper = ContainerWrapper::getInstance()->get(CronHelper::class);
    $this->cron_worker_scheduler = ContainerWrapper::getInstance()->get(CronWorkerScheduler::class);
  }

  function getTaskType() {
    return static::TASK_TYPE;
  }

  function supportsMultipleInstances() {
    return static::SUPPORT_MULTIPLE_INSTANCES;
  }

  function schedule() {
    $this->cron_worker_scheduler->schedule(static::TASK_TYPE, $this->getNextRunDate());
  }

  function checkProcessingRequirements() {
    return true;
  }

  function init() {
  }

  function prepareTaskStrategy(ScheduledTask $task, $timer) {
    return true;
  }

  function processTaskStrategy(ScheduledTask $task, $timer) {
    return true;
  }

  function getNextRunDate() {
    // random day of the next week
    $date = Carbon::createFromTimestamp($this->wp->currentTime('timestamp'));
    $date->setISODate((int)$date->format('o'), ((int)$date->format('W')) + 1, mt_rand(1, 7));
    $date->startOfDay();
    return $date;
  }

  function scheduleAutomatically() {
    return static::AUTOMATIC_SCHEDULING;
  }

  protected function getCompletedTasks() {
    return ScheduledTask::findCompletedByType(static::TASK_TYPE, CronWorkerRunner::TASK_BATCH_SIZE);
  }
}
