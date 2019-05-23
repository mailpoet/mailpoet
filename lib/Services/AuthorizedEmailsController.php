<?php

namespace MailPoet\Services;

use Carbon\Carbon;
use MailPoet\Settings\SettingsController;

if (!defined('ABSPATH')) exit;

class AuthorizedEmailsController {
  const AUTHORIZED_EMAIL_ADDRESSES_ERROR_SETTING = 'authorized_emails_addresses_check';

  /** @var Bridge */
  public $bridge;

  /** @var SettingsController */
  private $settings;

  function __construct(SettingsController $settingsController, Bridge $bridge) {
    $this->settings = $settingsController;
    $this->bridge = $bridge;
  }

  function checkAuthorizedEmailAddresses() {
    $installed_at = new Carbon($this->settings->get('installed_at'));
    $authorized_emails_release_date = new Carbon('2019-03-06');
    if (!Bridge::isMPSendingServiceEnabled() || $installed_at < $authorized_emails_release_date) {
      $this->settings->set(self::AUTHORIZED_EMAIL_ADDRESSES_ERROR_SETTING, null);
      return;
    }

    $authorized_emails = $this->bridge->getAuthorizedEmailAddresses();
    // Keep previous check result for an invalid response from API
    if ($authorized_emails === false) {
      return;
    }

    $result = $this->validateAddressesInSettings($authorized_emails);
    $this->settings->set(self::AUTHORIZED_EMAIL_ADDRESSES_ERROR_SETTING, $result ?: null);
  }

  function onSettingsSave($settings) {
    $sender_address_set = !empty($settings['sender']['address']);
    $confirmation_address_set = !empty($settings['signup_confirmation']['from']['address']);
    if ($sender_address_set || $confirmation_address_set) {
      $this->checkAuthorizedEmailAddresses();
    }
  }

  private function validateAddressesInSettings($authorized_emails, $result = []) {
    $default_sender_address = $this->settings->get('sender.address');
    $signup_confirmation_address = $this->settings->get('signup_confirmation.from.address');
    $authorized_emails = array_map('strtolower', $authorized_emails);

    if (!in_array(strtolower($default_sender_address), $authorized_emails, true)) {
      $result['invalid_sender_address'] = $default_sender_address;
    }
    if (!in_array(strtolower($signup_confirmation_address), $authorized_emails, true)) {
      $result['invalid_confirmation_address'] = $signup_confirmation_address;
    }

    return $result;
  }
}
