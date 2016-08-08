<?php
namespace MailPoet\API\Endpoints;

use MailPoet\Cron\CronHelper;
use MailPoet\Models\Setting;

if(!defined('ABSPATH')) exit;

class Cron {
  function getStatus() {
    $daemon = Setting::getValue(CronHelper::DAEMON_SETTING);
    return ($daemon) ?
      $daemon :
      array('status' => false);
  }
}