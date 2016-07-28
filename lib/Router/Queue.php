<?php
namespace MailPoet\Router;

use MailPoet\Cron\Daemon;

if(!defined('ABSPATH')) exit;

class Queue {
  const ENDPOINT = 'queue';
  const ACTION_RUN = 'run';

  static function run($data) {
    $queue = new Daemon($data);
    $queue->run();
  }
}