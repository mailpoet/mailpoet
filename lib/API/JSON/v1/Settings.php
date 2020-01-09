<?php

namespace MailPoet\API\JSON\v1;

use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\API\JSON\Error as APIError;
use MailPoet\Config\AccessControl;
use MailPoet\Cron\Workers\InactiveSubscribers;
use MailPoet\Cron\Workers\WooCommerceSync;
use MailPoet\Models\Form;
use MailPoet\Models\ScheduledTask;
use MailPoet\Services\AuthorizedEmailsController;
use MailPoet\Services\Bridge;
use MailPoet\Settings\SettingsController;
use MailPoet\WooCommerce\TransactionalEmails;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

class Settings extends APIEndpoint {

  /** @var SettingsController */
  private $settings;

  /** @var Bridge */
  private $bridge;

  /** @var AuthorizedEmailsController */
  private $authorizedEmailsController;

  /** @var TransactionalEmails */
  private $wcTransactionalEmails;

  public $permissions = [
    'global' => AccessControl::PERMISSION_MANAGE_SETTINGS,
  ];


  public function __construct(
    SettingsController $settings,
    Bridge $bridge,
    AuthorizedEmailsController $authorizedEmailsController,
    TransactionalEmails $wcTransactionalEmails
  ) {
    $this->settings = $settings;
    $this->bridge = $bridge;
    $this->authorizedEmailsController = $authorizedEmailsController;
    $this->wcTransactionalEmails = $wcTransactionalEmails;
  }

  public function get() {
    return $this->successResponse($this->settings->getAll());
  }

  public function set($settings = []) {
    if (empty($settings)) {
      return $this->badRequest(
        [
          APIError::BAD_REQUEST =>
            WPFunctions::get()->__('You have not specified any settings to be saved.', 'mailpoet'),
        ]);
    } else {
      $oldSettings = $this->settings->getAll();
      $signupConfirmation = $this->settings->get('signup_confirmation.enabled');
      foreach ($settings as $name => $value) {
        $this->settings->set($name, $value);
      }

      $this->onSettingsChange($oldSettings, $this->settings->getAll());

      $this->bridge->onSettingsSave($settings);
      $this->authorizedEmailsController->onSettingsSave($settings);
      if ($signupConfirmation !== $this->settings->get('signup_confirmation.enabled')) {
        Form::updateSuccessMessages();
      }
      return $this->successResponse($this->settings->getAll());
    }
  }

  private function onSettingsChange($oldSettings, $newSettings) {
    // Recalculate inactive subscribers
    $oldInactivationInterval = $oldSettings['deactivate_subscriber_after_inactive_days'];
    $newInactivationInterval = $newSettings['deactivate_subscriber_after_inactive_days'];
    if ($oldInactivationInterval !== $newInactivationInterval) {
      $this->onInactiveSubscribersIntervalChange();
    }

    // Sync WooCommerce Customers list
    $oldSubscribeOldWoocommerceCustomers = isset($oldSettings['mailpoet_subscribe_old_woocommerce_customers']['enabled'])
      ? $oldSettings['mailpoet_subscribe_old_woocommerce_customers']['enabled']
      : '0';
    $newSubscribeOldWoocommerceCustomers = isset($newSettings['mailpoet_subscribe_old_woocommerce_customers']['enabled'])
      ? $newSettings['mailpoet_subscribe_old_woocommerce_customers']['enabled']
      : '0';
    if ($oldSubscribeOldWoocommerceCustomers !== $newSubscribeOldWoocommerceCustomers) {
      $this->onSubscribeOldWoocommerceCustomersChange();
    }

    if (!empty($newSettings['woocommerce']['use_mailpoet_editor'])) {
      $this->wcTransactionalEmails->init();
    }
  }

  private function onSubscribeOldWoocommerceCustomersChange() {
    $task = ScheduledTask::where('type', WooCommerceSync::TASK_TYPE)
      ->whereRaw('status = ?', [ScheduledTask::STATUS_SCHEDULED])
      ->findOne();
    if (!($task instanceof ScheduledTask)) {
      $task = ScheduledTask::create();
      $task->type = WooCommerceSync::TASK_TYPE;
      $task->status = ScheduledTask::STATUS_SCHEDULED;
    }
    $datetime = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    $task->scheduledAt = $datetime->subMinute();
    $task->save();
  }

  private function onInactiveSubscribersIntervalChange() {
    $task = ScheduledTask::where('type', InactiveSubscribers::TASK_TYPE)
      ->whereRaw('status = ?', [ScheduledTask::STATUS_SCHEDULED])
      ->findOne();
    if (!($task instanceof ScheduledTask)) {
      $task = ScheduledTask::create();
      $task->type = InactiveSubscribers::TASK_TYPE;
      $task->status = ScheduledTask::STATUS_SCHEDULED;
    }
    $datetime = Carbon::createFromTimestamp((int)WPFunctions::get()->currentTime('timestamp'));
    $task->scheduledAt = $datetime->subMinute();
    $task->save();
  }
}
