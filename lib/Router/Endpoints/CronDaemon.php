<?php
namespace MailPoet\Router\Endpoints;

use MailPoet\Cron\CronHelper;
use MailPoet\Cron\Daemon;

if(!defined('ABSPATH')) exit;

class CronDaemon {
  const ENDPOINT = 'cron_daemon';
  const ACTION_RUN = 'run';
  const ACTION_PING = 'ping';
  const ACTION_PING_RESPONSE = 'pingResponse';
  public $allowed_actions = array(
    self::ACTION_RUN,
    self::ACTION_PING,
    self::ACTION_PING_RESPONSE
  );
  public $data;

  function __construct($data) {
    $this->data = $data;
  }

  function run() {
    $queue = new Daemon($this->data);
    $queue->run();
  }

  function ping() {
     die(CronHelper::pingDaemon());
  }

  function pingResponse() {
    $queue = new Daemon();
    $queue->ping();
  }
}