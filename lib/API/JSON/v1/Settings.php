<?php

namespace MailPoet\API\JSON\v1;

use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\API\JSON\Error as APIError;
use MailPoet\Config\AccessControl;
use MailPoet\Config\ServicesChecker;
use MailPoet\Mailer\MailerLog;
use MailPoet\Services\AuthorizedEmailsController;
use MailPoet\Services\Bridge;
use MailPoet\Settings\SettingsController;
use MailPoet\WooCommerce\TransactionalEmails;
use MailPoet\WP\Functions as WPFunctions;

class Settings extends APIEndpoint {

  /** @var SettingsController */
  private $settings;

  /** @var Bridge */
  private $bridge;

  /** @var AuthorizedEmailsController */
  private $authorizedEmailsController;

  /** @var TransactionalEmails */
  private $wcTransactionalEmails;

  /** @var ServicesChecker */
  private $servicesChecker;

  public $permissions = [
    'global' => AccessControl::PERMISSION_MANAGE_SETTINGS,
  ];

  public function __construct(
    SettingsController $settings,
    Bridge $bridge,
    AuthorizedEmailsController $authorizedEmailsController,
    TransactionalEmails $wcTransactionalEmails,
    ServicesChecker $servicesChecker
  ) {
    $this->settings = $settings;
    $this->bridge = $bridge;
    $this->authorizedEmailsController = $authorizedEmailsController;
    $this->wcTransactionalEmails = $wcTransactionalEmails;
    $this->servicesChecker = $servicesChecker;
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

      // when pending approval, leave this to cron / Key Activation tab logic
      if (!$this->servicesChecker->isMailPoetAPIKeyPendingApproval()) {
        $this->bridge->onSettingsSave($settings);
      }

      $this->authorizedEmailsController->onSettingsSave($settings);
      if ($signupConfirmation !== $this->settings->get('signup_confirmation.enabled')) {
        $this->settings->updateSuccessMessages();
      }
      return $this->successResponse($this->settings->getAll());
    }
  }

  public function setAuthorizedFromAddress($data = []) {
    $address = $data['address'] ?? null;
    if (!$address) {
      return $this->badRequest([
        APIError::BAD_REQUEST => WPFunctions::get()->__('No email address specified.', 'mailpoet'),
      ]);
    }
    $address = trim($address);

    try {
      $this->authorizedEmailsController->setFromEmailAddress($address);
    } catch (\InvalidArgumentException $e) {
      return $this->badRequest([
        APIError::UNAUTHORIZED => WPFunctions::get()->__('Can’t use this email yet! Please authorize it first.', 'mailpoet'),
      ]);
    }

    if (!$this->servicesChecker->isMailPoetAPIKeyPendingApproval()) {
      MailerLog::resumeSending();
    }
    return $this->successResponse();
  }

  private function onSettingsChange($oldSettings, $newSettings) {
    // Recalculate inactive subscribers
    $oldInactivationInterval = $oldSettings['deactivate_subscriber_after_inactive_days'];
    $newInactivationInterval = $newSettings['deactivate_subscriber_after_inactive_days'];
    if ($oldInactivationInterval !== $newInactivationInterval) {
      $this->settings->onInactiveSubscribersIntervalChange();
    }

    $oldSendingMethod = $oldSettings['mta_group'];
    $newSendingMethod = $newSettings['mta_group'];
    if (($oldSendingMethod !== $newSendingMethod) && ($newSendingMethod === 'mailpoet')) {
      $this->onMSSActivate($newSettings);
    }

    // Sync WooCommerce Customers list
    $oldSubscribeOldWoocommerceCustomers = isset($oldSettings['mailpoet_subscribe_old_woocommerce_customers']['enabled'])
      ? $oldSettings['mailpoet_subscribe_old_woocommerce_customers']['enabled']
      : '0';
    $newSubscribeOldWoocommerceCustomers = isset($newSettings['mailpoet_subscribe_old_woocommerce_customers']['enabled'])
      ? $newSettings['mailpoet_subscribe_old_woocommerce_customers']['enabled']
      : '0';
    if ($oldSubscribeOldWoocommerceCustomers !== $newSubscribeOldWoocommerceCustomers) {
      $this->settings->onSubscribeOldWoocommerceCustomersChange();
    }

    if (!empty($newSettings['woocommerce']['use_mailpoet_editor'])) {
      $this->wcTransactionalEmails->init();
    }
  }

  private function onMSSActivate($newSettings) {
    // see mailpoet/assets/js/src/wizard/create_sender_settings.jsx:freeAddress
    $domain = str_replace('www.', '', $_SERVER['HTTP_HOST']);
    if (
      isset($newSettings['sender']['address'])
      && !empty($newSettings['reply_to']['address'])
      && ($newSettings['sender']['address'] === ('wordpress@' . $domain))
    ) {
      $sender = [
        'name' => $newSettings['reply_to']['name'] ?? '',
        'address' => $newSettings['reply_to']['address'],
      ];
      $this->settings->set('sender', $sender);
      $this->settings->set('reply_to', null);
    }
  }
}
