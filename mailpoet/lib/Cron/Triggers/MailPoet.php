<?php

namespace MailPoet\Cron\Triggers;

use MailPoet\Cron\Supervisor;

class MailPoet {
  /** @var Supervisor */
  private $supervisor;

  public function __construct(
    Supervisor $supervisor
  ) {
    $this->supervisor = $supervisor;
  }

  public function run() {
    $this->supervisor->init();
    return $this->supervisor->checkDaemon();
  }
}
