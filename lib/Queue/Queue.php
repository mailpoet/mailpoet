<?php
namespace MailPoet\Queue;

if(!defined('ABSPATH')) exit;

class Queue {
  function __construct() {
  }

  function create() {
  }

  function startQueue() {
/*    ignore_user_abort();
    set_time_limit(0);
    header('Connection: close');
    header('X-MailPoet-Queue: started');
    ob_end_flush();
    ob_flush();
    flush();*/
  }

  function process() {
  }

  function pause() {
  }

  function stop() {
  }
}