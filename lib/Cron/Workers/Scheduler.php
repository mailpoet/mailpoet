<?php
namespace MailPoet\Cron\Workers;

use MailPoet\Cron\CronHelper;
use MailPoet\Models\Setting;
use MailPoet\Util\Security;

if(!defined('ABSPATH')) exit;

class Scheduler {
  public $timer;

  function __construct($timer = false) {
    $this->timer = ($timer) ? $timer : microtime(true);
    CronHelper::checkExecutionTimer($this->timer);
  }

  function process() {
  }

  function checkExecutionTimer() {
    $elapsed_time = microtime(true) - $this->timer;
    if($elapsed_time >= CronHelper::daemon_execution_limit) {
      throw new \Exception(__('Maximum execution time reached.'));
    }
  }
}