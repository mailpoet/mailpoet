<?php

namespace MailPoet\API\JSON\v1;

use Carbon\Carbon;
use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\API\JSON\Error as APIError;
use MailPoet\Config\AccessControl;
use MailPoet\Cron\Workers\InactiveSubscribers;
use MailPoet\Cron\Workers\WooCommerceSync;
use MailPoet\Models\ScheduledTask;
use MailPoet\Services\AuthorizedEmailsController;
use MailPoet\Services\Bridge;
use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;
use MailPoet\Models\Form;

if (!defined('ABSPATH')) exit;

class Settings extends APIEndpoint {

  /** @var SettingsController */
  private $settings;

  /** @var Bridge */
  private $bridge;

  /** @var AuthorizedEmailsController */
  private $authorized_emails_controller;

  public $permissions = [
    'global' => AccessControl::PERMISSION_MANAGE_SETTINGS,
  ];


  public function __construct(
    SettingsController $settings,
    Bridge $bridge,
    AuthorizedEmailsController $authorized_emails_controller
  ) {
    $this->settings = $settings;
    $this->bridge = $bridge;
    $this->authorized_emails_controller = $authorized_emails_controller;
  }

  function get() {
    return $this->successResponse($this->settings->getAll());
  }

  function set($settings = []) {
    if (empty($settings)) {
      return $this->badRequest(
        [
          APIError::BAD_REQUEST =>
            WPFunctions::get()->__('You have not specified any settings to be saved.', 'mailpoet'),
        ]);
    } else {
      $old_settings = $this->settings->getAll();
      $signup_confirmation = $this->settings->get('signup_confirmation.enabled');
      foreach ($settings as $name => $value) {
        $this->settings->set($name, $value);
      }

      $this->onSettingsChange($old_settings, $this->settings->getAll());

      $this->bridge->onSettingsSave($settings);
      $this->authorized_emails_controller->onSettingsSave($settings);
      if ($signup_confirmation !== $this->settings->get('signup_confirmation.enabled')) {
        Form::updateSuccessMessages();
      }
      return $this->successResponse($this->settings->getAll());
    }
  }

  private function onSettingsChange($old_settings, $new_settings) {
    // Recalculate inactive subscribers
    $old_inactivation_interval = $old_settings['deactivate_subscriber_after_inactive_days'];
    $new_inactivation_interval = $new_settings['deactivate_subscriber_after_inactive_days'];
    if ($old_inactivation_interval !== $new_inactivation_interval) {
      $this->onInactiveSubscribersIntervalChange();
    }

    // Sync WooCommerce Customers list
    $old_subscribe_old_woocommerce_customers = isset($old_settings['mailpoet_subscribe_old_woocommerce_customers']['enabled'])
      ? $old_settings['mailpoet_subscribe_old_woocommerce_customers']['enabled']
      : '0';
    $new_subscribe_old_woocommerce_customers = isset($new_settings['mailpoet_subscribe_old_woocommerce_customers']['enabled'])
      ? $new_settings['mailpoet_subscribe_old_woocommerce_customers']['enabled']
      : '0';
    if ($old_subscribe_old_woocommerce_customers !== $new_subscribe_old_woocommerce_customers) {
      $this->onSubscribeOldWoocommerceCustomersChange();
    }
  }

  private function onSubscribeOldWoocommerceCustomersChange() {
    $task = ScheduledTask::where('type', WooCommerceSync::TASK_TYPE)
      ->whereRaw('status = ?', [ScheduledTask::STATUS_SCHEDULED])
      ->findOne();
    if (!$task) {
      $task = ScheduledTask::create();
      $task->type = WooCommerceSync::TASK_TYPE;
      $task->status = ScheduledTask::STATUS_SCHEDULED;
    }
    $datetime = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    $task->scheduled_at = $datetime->subMinute();
    $task->save();
  }

  private function onInactiveSubscribersIntervalChange() {
    $task = ScheduledTask::where('type', InactiveSubscribers::TASK_TYPE)
      ->whereRaw('status = ?', [ScheduledTask::STATUS_SCHEDULED])
      ->findOne();
    if (!$task) {
      $task = ScheduledTask::create();
      $task->type = InactiveSubscribers::TASK_TYPE;
      $task->status = ScheduledTask::STATUS_SCHEDULED;
    }
    $datetime = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    $task->scheduled_at = $datetime->subMinute();
    $task->save();
  }
}
