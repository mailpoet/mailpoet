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

  function checkProcessingRequirements() {
    $settings = $this->settings->get(Worker::SETTINGS_KEY);
    if (!is_array($settings)) {
      return false;
    }
    if (!isset($settings['automated'])) {
      return false;
    }
    if (!isset($settings['address'])) {
      return false;
    }
    if (empty(trim($settings['address']))) {
      return false;
    }
    if (!(bool)$this->settings->get('tracking.enabled')) {
      return false;
    }
    return (bool)$settings['automated'];
  }

  function processTaskStrategy(ScheduledTask $task) {
    // TODO
    // TODO refactor \MailPoet\Cron\Workers\StatsNotifications\Worker  and Scheduler and share the code
    // TODO settings isn't saved properly :(
  }

  static function getNextRunDate() {
    $wp = new WPFunctions;
    $date = Carbon::createFromTimestamp($wp->currentTime('timestamp'));
    return $date->next(Carbon::MONDAY)->midDay();
  }
}
