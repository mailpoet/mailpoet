<?php

namespace MailPoet\Cron\Workers\StatsNotifications;

use Carbon\Carbon;
use MailPoet\Cron\Workers\SimpleWorker;
use MailPoet\Models\ScheduledTask;
use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;

if (!defined('ABSPATH')) exit;

class AutomatedEmails extends SimpleWorker {
  const TASK_TYPE = 'stats_notification_automated_emails';

  /** @var SettingsController */
  private $settings;

  function __construct(SettingsController $settings, $timer = false) {
    parent::__construct($timer);
    $this->settings = $settings;
  }

  function processTaskStrategy(ScheduledTask $task) {
    // TODO
  }

  static function getNextRunDate() {
    $wp = new WPFunctions;
    $date = Carbon::createFromTimestamp($wp->currentTime('timestamp'));
    return $date->next(Carbon::MONDAY)->midDay();
  }
}
