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

  /** @var CronHelper */
  protected $cron_helper;

  /** @var CronWorkerScheduler */
  protected $cron_worker_scheduler;

  /** @var WPFunctions */
  protected $wp;

  public function __construct(WPFunctions $wp = null) {
    if (static::TASK_TYPE === null) {
      throw new \Exception('Constant TASK_TYPE is not defined on subclass ' . get_class($this));
    }

    if ($wp === null) $wp = ContainerWrapper::getInstance()->get(WPFunctions::class);
    $this->wp = $wp;
    $this->cron_helper = ContainerWrapper::getInstance()->get(CronHelper::class);
    $this->cron_worker_scheduler = ContainerWrapper::getInstance()->get(CronWorkerScheduler::class);
  }

  public function getTaskType() {
    return static::TASK_TYPE;
  }

  public function supportsMultipleInstances() {
    return static::SUPPORT_MULTIPLE_INSTANCES;
  }

  public function schedule() {
    $this->cron_worker_scheduler->schedule(static::TASK_TYPE, $this->getNextRunDate());
  }

  public function checkProcessingRequirements() {
    return true;
  }

  public function init() {
  }

  public function prepareTaskStrategy(ScheduledTask $task, $timer) {
    return true;
  }

  public function processTaskStrategy(ScheduledTask $task, $timer) {
    return true;
  }

  public function getNextRunDate() {
    // random day of the next week
    $date = Carbon::createFromTimestamp($this->wp->currentTime('timestamp'));
    $date->setISODate((int)$date->format('o'), ((int)$date->format('W')) + 1, mt_rand(1, 7));
    $date->startOfDay();
    return $date;
  }

  public function scheduleAutomatically() {
    return static::AUTOMATIC_SCHEDULING;
  }

  protected function getCompletedTasks() {
    return ScheduledTask::findCompletedByType(static::TASK_TYPE, CronWorkerRunner::TASK_BATCH_SIZE);
  }
}
