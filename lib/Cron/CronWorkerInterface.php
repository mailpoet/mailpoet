<?php

namespace MailPoet\Cron;

use MailPoet\Models\ScheduledTask;

interface CronWorkerInterface {
  /** @return string */
  public function getTaskType();

  /** @return bool */
  public function scheduleAutomatically();

  /** @return bool */
  public function supportsMultipleInstances();

  /** @return bool */
  public function checkProcessingRequirements();

  public function init();

  /**
   * @param ScheduledTask $task
   * @param float $timer
   * @return bool
   */
  public function prepareTaskStrategy(ScheduledTask $task, $timer);

  /**
   * @param ScheduledTask $task
   * @param float $timer
   * @return bool
   */
  public function processTaskStrategy(ScheduledTask $task, $timer);

  /** @return \DateTimeInterface */
  public function getNextRunDate();
}
