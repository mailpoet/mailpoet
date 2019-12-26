<?php

namespace MailPoet\Router\Endpoints;

use MailPoet\Config\AccessControl;
use MailPoet\Cron\CronHelper;
use MailPoet\Cron\DaemonHttpRunner;

class CronDaemon {
  const ENDPOINT = 'cron_daemon';
  const ACTION_RUN = 'run';
  const ACTION_PING = 'ping';
  const ACTION_PING_RESPONSE = 'pingResponse';
  public $allowed_actions = [
    self::ACTION_RUN,
    self::ACTION_PING,
    self::ACTION_PING_RESPONSE,
  ];
  public $data;
  public $permissions = [
    'global' => AccessControl::NO_ACCESS_RESTRICTION,
  ];

  /** @var DaemonHttpRunner */
  private $daemon_runner;

  /** @var CronHelper */
  private $cron_helper;

  public function __construct(DaemonHttpRunner $daemon_runner, CronHelper $cron_helper) {
    $this->daemon_runner = $daemon_runner;
    $this->cron_helper = $cron_helper;
  }

  public function run($data) {
    $this->daemon_runner->run($data);
  }

  public function ping() {
     die($this->cron_helper->pingDaemon());
  }

  public function pingResponse() {
    $this->daemon_runner->ping();
  }
}
