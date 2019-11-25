<?php

namespace MailPoet\Cron;

use MailPoet\Models\ScheduledTask;

interface CronWorkerInterface {
  /** @return string */
  function getTaskType();

  /** @return bool */
  function scheduleAutomatically();

  /** @return bool */
  function supportsMultipleInstances();

  /** @return bool */
  function checkProcessingRequirements();

  function init();

  /**
   * @param ScheduledTask $task
   * @param float $timer
   * @return bool
   */
  function prepareTaskStrategy(ScheduledTask $task, $timer);

  /**
   * @param ScheduledTask $task
   * @param float $timer
   * @return bool
   */
  function processTaskStrategy(ScheduledTask $task, $timer);

  /** @return \DateTimeInterface */
  function getNextRunDate();
}
