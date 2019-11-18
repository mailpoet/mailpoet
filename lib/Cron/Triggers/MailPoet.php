<?php

namespace MailPoet\Cron\Triggers;

use MailPoet\Cron\Supervisor;

class MailPoet {
  /** @var Supervisor */
  private $supervisor;

  function __construct(Supervisor $supervisor) {
    $this->supervisor = $supervisor;
  }

  function run() {
    $this->supervisor->init();
    return $this->supervisor->checkDaemon();
  }
}
