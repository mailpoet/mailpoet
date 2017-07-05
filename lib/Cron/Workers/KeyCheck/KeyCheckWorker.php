<?php
namespace MailPoet\Cron\Workers\KeyCheck;

use MailPoet\Cron\CronHelper;
use MailPoet\Cron\Workers\SimpleWorker;
use MailPoet\Models\ScheduledTask;
use MailPoet\Services\Bridge;

if(!defined('ABSPATH')) exit;

abstract class KeyCheckWorker extends SimpleWorker {
  const UNAVAILABLE_SERVICE_RESCHEDULE_TIMEOUT = 60;

  public $bridge;

  function init() {
    if(!$this->bridge) {
      $this->bridge = new Bridge();
    }
  }

  function processTaskStrategy(ScheduledTask $task) {
    try {
      $result = $this->checkKey();
    } catch (\Exception $e) {
      $result = false;
    }

    if(empty($result['code']) || $result['code'] == Bridge::CHECK_ERROR_UNAVAILABLE) {
      $this->reschedule($task, self::UNAVAILABLE_SERVICE_RESCHEDULE_TIMEOUT);
      return false;
    }

    return true;
  }

  abstract function checkKey();
}
