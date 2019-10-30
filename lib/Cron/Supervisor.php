<?php

namespace MailPoet\Cron;

class Supervisor {
  public $daemon;
  public $token;

  /** @var CronHelper */
  private $cron_helper;

  function __construct(CronHelper $cron_helper) {
    $this->cron_helper = $cron_helper;
  }

  function init() {
    $this->token = $this->cron_helper->createToken();
    $this->daemon = $this->getDaemon();
  }

  function checkDaemon() {
    $daemon = $this->daemon;
    $execution_timeout_exceeded =
      (time() - (int)$daemon['updated_at']) >= $this->cron_helper->getDaemonExecutionTimeout();
    $daemon_is_inactive =
      isset($daemon['status']) && $daemon['status'] === CronHelper::DAEMON_STATUS_INACTIVE;
    if ($execution_timeout_exceeded || $daemon_is_inactive) {
      $this->cron_helper->restartDaemon($this->token);
      return $this->runDaemon();
    }
    return $daemon;
  }

  function runDaemon() {
    $this->cron_helper->accessDaemon($this->token);
    $daemon = $this->cron_helper->getDaemon();
    return $daemon;
  }

  function getDaemon() {
    $daemon = $this->cron_helper->getDaemon();
    if (!$daemon) {
      $this->cron_helper->createDaemon($this->token);
      return $this->runDaemon();
    }
    return $daemon;
  }
}
