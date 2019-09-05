<?php
namespace MailPoet\Cron\Triggers;

use MailPoet\Cron\Supervisor;

class MailPoet {
  static function run() {
    $supervisor = new Supervisor();
    return $supervisor->checkDaemon();
  }
}
