<?php

namespace MailPoet\Cron\Workers;

use MailPoet\Cron\CronHelper;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\Subscriber;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\InactiveSubscribersController;

class InactiveSubscribers extends SimpleWorker {
  const TASK_TYPE = 'inactive_subscribers';
  const BATCH_SIZE = 1000;
  const SUPPORT_MULTIPLE_INSTANCES = false;

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
    $meta = $task->getMeta();
    $last_subscriber_id = isset($meta['last_subscriber_id']) ? $meta['last_subscriber_id'] : 0;
    $max_subscriber_id = isset($meta['max_subscriber_id']) ? $meta['max_subscriber_id'] : (int)Subscriber::max('id');
    while ($last_subscriber_id <= $max_subscriber_id) {
      $count = $this->inactive_subscribers_controller->markInactiveSubscribers($days_to_inactive, self::BATCH_SIZE, $last_subscriber_id);
      if ($count === false) {
        break;
      }
      $last_subscriber_id += self::BATCH_SIZE;
      $task->meta = ['last_subscriber_id' => $last_subscriber_id];
      $task->save();
      $this->cron_helper->enforceExecutionLimit($this->timer);
    };
    while ($this->inactive_subscribers_controller->markActiveSubscribers($days_to_inactive, self::BATCH_SIZE) === self::BATCH_SIZE) {
      $this->cron_helper->enforceExecutionLimit($this->timer);
    };
    self::schedule();
    return true;
  }
}
