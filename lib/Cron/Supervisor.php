<?php
namespace MailPoet\Cron;

if (!defined('ABSPATH')) exit;

class Supervisor {
  public $daemon;
  public $token;

  function __construct() {
    $this->token = CronHelper::createToken();
    $this->daemon = $this->getDaemon();
  }

  function checkDaemon() {
    $daemon = $this->daemon;
    $execution_timeout_exceeded =
      (time() - (int)$daemon['updated_at']) >= CronHelper::DAEMON_EXECUTION_TIMEOUT;
    $daemon_is_inactive =
      isset($daemon['status']) && $daemon['status'] === CronHelper::DAEMON_STATUS_INACTIVE;
    if ($execution_timeout_exceeded || $daemon_is_inactive) {
      CronHelper::restartDaemon($this->token);
      return $this->runDaemon();
    }
    return $daemon;
  }

  function runDaemon() {
    CronHelper::accessDaemon($this->token);
    $daemon = CronHelper::getDaemon();
    return $daemon;
  }

  function getDaemon() {
    $daemon = CronHelper::getDaemon();
    if (!$daemon) {
      CronHelper::createDaemon($this->token);
      return $this->runDaemon();
    }
    return $daemon;
  }
}
