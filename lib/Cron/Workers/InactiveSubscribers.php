<?php
namespace MailPoet\Cron\Workers;

use MailPoet\Cron\CronHelper;
use MailPoet\Models\ScheduledTask;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\InactiveSubscribersController;

if (!defined('ABSPATH')) exit;

class InactiveSubscribers extends SimpleWorker {
  const TASK_TYPE = 'inactive_subscribers';
  const BATCH_SIZE = 1000;

  /** @var InactiveSubscribersController */
  private $inactive_subscribers_controller;

  /** @var SettingsController */
  private $settings;

  function __construct(
    InactiveSubscribersController $inactive_subscribers_controller,
    SettingsController $settings,
    $timer = false
  ) {
    $this->inactive_subscribers_controller = $inactive_subscribers_controller;
    $this->settings = $settings;
    parent::__construct($timer);
  }


  function processTaskStrategy(ScheduledTask $task) {
    $tracking_enabled = (bool)$this->settings->get('tracking.enabled');
    if (!$tracking_enabled) {
      self::schedule();
      return true;
    }
    $days_to_inactive = (int)$this->settings->get('deactivate_subscriber_after_inactive_days');
    // Activate all inactive subscribers in case the feature is turned off
    if ($days_to_inactive === 0) {
      $this->inactive_subscribers_controller->reactivateInactiveSubscribers();
      self::schedule();
      return true;
    }
    // Handle activation/deactivation within interval
    while ($this->inactive_subscribers_controller->markInactiveSubscribers($days_to_inactive, self::BATCH_SIZE) === self::BATCH_SIZE) {
      CronHelper::enforceExecutionLimit($this->timer);
    };
    while ($this->inactive_subscribers_controller->markActiveSubscribers($days_to_inactive, self::BATCH_SIZE) === self::BATCH_SIZE) {
      CronHelper::enforceExecutionLimit($this->timer);
    };
    self::schedule();
    return true;
  }
}
