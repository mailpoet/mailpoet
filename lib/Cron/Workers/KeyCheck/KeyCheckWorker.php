<?php
namespace MailPoet\Cron\Workers\KeyCheck;

use MailPoet\Cron\Workers\SimpleWorker;
use MailPoet\Models\ScheduledTask;
use MailPoet\Services\Bridge;

if (!defined('ABSPATH')) exit;

abstract class KeyCheckWorker extends SimpleWorker {
  public $bridge;

  function init() {
    if (!$this->bridge) {
      $this->bridge = new Bridge();
    }
  }

  function processTaskStrategy(ScheduledTask $task) {
    try {
      $result = $this->checkKey();
    } catch (\Exception $e) {
      $result = false;
    }

    if (empty($result['code']) || $result['code'] == Bridge::CHECK_ERROR_UNAVAILABLE) {
      $task->rescheduleProgressively();
      return false;
    }

    return true;
  }

  abstract function checkKey();
}
