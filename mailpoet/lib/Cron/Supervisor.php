<?php

namespace MailPoet\Cron;

use MailPoet\WP\Functions as WPFunctions;

class Supervisor {
  public $daemon;
  public $token;

  /** @var CronHelper */
  private $cronHelper;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    CronHelper $cronHelper,
    WPFunctions $wp
  ) {
    $this->cronHelper = $cronHelper;
    $this->wp = $wp;
  }

  public function init() {
    $this->token = $this->cronHelper->createToken();
    $this->daemon = $this->getDaemon();
  }

  public function checkDaemon() {
    $daemon = $this->daemon;
    $updatedAt = $daemon ? (int)$daemon['updated_at'] : 0;
    $executionTimeoutExceeded =
      (time() - $updatedAt) >= $this->cronHelper->getDaemonExecutionTimeout();
    $daemonIsInactive =
      isset($daemon['status']) && $daemon['status'] === CronHelper::DAEMON_STATUS_INACTIVE;
    if ($executionTimeoutExceeded || $daemonIsInactive) {
      $this->cronHelper->restartDaemon($this->token);
      return $this->runDaemon();
    }
    return $daemon;
  }

  public function runDaemon() {
    $daemon = $this->cronHelper->getDaemon();
    // Cleanup previous cron events in case some left hanging
    $this->wp->wpUnscheduleHook(CronTrigger::CRON_TRIGGER_ACTION);
    $secondAgo = $this->wp->currentTime('timestamp') - 1;
    $this->wp->wpScheduleSingleEvent($secondAgo, CronTrigger::CRON_TRIGGER_ACTION, [$this->token]);
    return $daemon;
  }

  public function getDaemon() {
    $daemon = $this->cronHelper->getDaemon();
    if (!$daemon) {
      $this->cronHelper->createDaemon($this->token);
      return $this->runDaemon();
    }
    return $daemon;
  }
}
