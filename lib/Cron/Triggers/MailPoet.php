<?php

namespace MailPoet\Cron\Triggers;

use MailPoet\Cron\Supervisor;
use MailPoet\DI\ContainerWrapper;

class MailPoet {
  static function run() {
    $supervisor = ContainerWrapper::getInstance()->get(Supervisor::class);
    $supervisor->init();
    return $supervisor->checkDaemon();
  }
}
