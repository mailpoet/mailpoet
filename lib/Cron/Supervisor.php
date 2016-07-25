<?php
namespace MailPoet\Cron;

if(!defined('ABSPATH')) exit;

class Supervisor {
  public $daemon;
  public $token;
  public $force_run;

  function __construct($force_run = false) {
    $this->daemon = CronHelper::getDaemon();
    $this->token = CronHelper::createToken();
    $this->force_run = $force_run;
  }

  function checkDaemon() {
    $daemon = $this->daemon;
    $execution_timeout_exceeded = ($daemon) ?
      (time() - (int)$daemon['updated_at']) > CronHelper::DAEMON_EXECUTION_TIMEOUT :
      false;
    if(!$daemon || $execution_timeout_exceeded) {
      CronHelper::createOrRestartDaemon($this->token);
      return $this->runDaemon();
    }
    return $daemon;
  }

  function runDaemon() {
    CronHelper::accessDaemon($this->token);
    $daemon = CronHelper::getDaemon();
    return $daemon;
  }
}