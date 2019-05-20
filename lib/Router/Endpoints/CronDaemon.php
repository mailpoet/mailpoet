<?php

namespace MailPoet\Router\Endpoints;

use MailPoet\Config\AccessControl;
use MailPoet\Cron\CronHelper;
use MailPoet\Cron\DaemonHttpRunner;

if (!defined('ABSPATH')) exit;

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

  function __construct(DaemonHttpRunner $daemon_runner) {
    $this->daemon_runner = $daemon_runner;
  }

  function run($data) {
    $this->daemon_runner->run($data);
  }

  function ping() {
     die(CronHelper::pingDaemon());
  }

  function pingResponse() {
    $this->daemon_runner->ping();
  }
}
