<?php
namespace MailPoet\Router\Endpoints;

use MailPoet\Cron\Daemon;

if(!defined('ABSPATH')) exit;

class Queue {
  const ENDPOINT = 'queue';
  const ACTION_RUN = 'run';
  public $allowed_actions = array(self::ACTION_RUN);
  public $data;

  function __construct($data) {
    $this->data = $data;
  }

  function run() {
    $queue = new Daemon($this->data);
    $queue->run();
  }
}