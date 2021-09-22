<?php

namespace MailPoet\Cron\Workers\KeyCheck;

use MailPoet\Cron\Workers\SimpleWorker;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Newsletter\Sending\ScheduledTasks;
use MailPoet\Services\Bridge;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

abstract class KeyCheckWorker extends SimpleWorker {
  public $bridge;

  /** @var ScheduledTasks */
  protected $scheduledTasks;

  public function __construct(
    ScheduledTasks $scheduledTasks,
    WPFunctions $wp = null
  ) {
    parent::__construct($wp);
    $this->scheduledTasks = $scheduledTasks;
  }

  public function init() {
    if (!$this->bridge) {
      $this->bridge = new Bridge();
    }
  }

  public function processTaskStrategy(ScheduledTaskEntity $task, $timer) {
    try {
      $result = $this->checkKey();
    } catch (\Exception $e) {
      $result = false;
    }

    if (empty($result['code']) || $result['code'] == Bridge::CHECK_ERROR_UNAVAILABLE) {
      $this->scheduledTasks->rescheduleProgressively($task);
      return false;
    }

    return true;
  }

  public function getNextRunDate() {
    $date = Carbon::createFromTimestamp($this->wp->currentTime('timestamp'));
    return $date->startOfDay()
      ->addDay()
      ->addHours(rand(0, 5))
      ->addMinutes(rand(0, 59))
      ->addSeconds(rand(0, 59));
  }

  public abstract function checkKey();
}
