<?php
namespace MailPoet\Cron\Triggers;

use MailPoet\Cron\Supervisor;

if (!defined('ABSPATH')) exit;

class MailPoet {
  static function run() {
    $supervisor = new Supervisor();
    return $supervisor->checkDaemon();
  }
}
