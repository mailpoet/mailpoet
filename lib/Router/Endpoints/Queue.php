<?php
namespace MailPoet\Router\Endpoints;

use MailPoet\Cron\Daemon;

if(!defined('ABSPATH')) exit;

class Queue {
  const ENDPOINT = 'queue';
  const ACTION_RUN = 'run';

  function run($data) {
    $queue = new Daemon($data);
    $queue->run();
  }
}