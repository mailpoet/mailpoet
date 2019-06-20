<?php
namespace MailPoet\Cron\Workers;

use Carbon\Carbon;
use MailPoet\Models\ScheduledTask;
use MailPoet\Cron\Workers\SimpleWorker;
use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;

if (!defined('ABSPATH')) exit;

class Beamer extends SimpleWorker {
  const TASK_TYPE = 'beamer';

  /** @var SettingsController */
  private $settings;

  function __construct(SettingsController $settings, $timer = false) {
    parent::__construct($timer);
    $this->settings = $settings;
  }

  function processTaskStrategy(ScheduledTask $task) {
    $this->settings->set('last_announcement_date', 'timestamp');
    return true;
  }

  // static function getNextRunDate() {
  //   $wp = new WPFunctions();
  //   $date = Carbon::createFromTimestamp($wp->currentTime('timestamp'));
  //   return $date->addMinute();
  //   // return $date->hour(11)->minute(00)->second(00)->addDay();
  // }
}
