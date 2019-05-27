<?php

namespace MailPoet\Services;

use Carbon\Carbon;
use MailPoet\Mailer\MailerError;
use MailPoet\Mailer\MailerLog;
use MailPoet\Models\Newsletter;
use MailPoet\Settings\SettingsController;

if (!defined('ABSPATH')) exit;

class AuthorizedEmailsController {
  const AUTHORIZED_EMAIL_ADDRESSES_ERROR_SETTING = 'authorized_emails_addresses_check';

  /** @var Bridge */
  private $bridge;

  /** @var SettingsController */
  private $settings;

  private $automatic_email_types = [
    Newsletter::TYPE_WELCOME,
    Newsletter::TYPE_NOTIFICATION,
    Newsletter::TYPE_AUTOMATIC,
  ];

  function __construct(SettingsController $settingsController, Bridge $bridge) {
    $this->settings = $settingsController;
    $this->bridge = $bridge;
  }

  function checkAuthorizedEmailAddresses() {
    $installed_at = new Carbon($this->settings->get('installed_at'));
    $authorized_emails_release_date = new Carbon('2019-03-06');
    if (!Bridge::isMPSendingServiceEnabled() || $installed_at < $authorized_emails_release_date) {
      $this->settings->set(self::AUTHORIZED_EMAIL_ADDRESSES_ERROR_SETTING, null);
      $this->updateMailerLog();
      return;
    }

    $authorized_emails = $this->bridge->getAuthorizedEmailAddresses();
    // Keep previous check result for an invalid response from API
    if ($authorized_emails === false) {
      return;
    }
    $authorized_emails = array_map('strtolower', $authorized_emails);

    $result = [];
    $result = $this->validateAddressesInSettings($authorized_emails, $result);
    $result = $this->validateAddressesInScheduledAndAutomaticEmails($authorized_emails, $result);
    $this->settings->set(self::AUTHORIZED_EMAIL_ADDRESSES_ERROR_SETTING, $result ?: null);
    $this->updateMailerLog($result);
  }

  function onSettingsSave($settings) {
    $sender_address_set = !empty($settings['sender']['address']);
    $confirmation_address_set = !empty($settings['signup_confirmation']['from']['address']);
    if ($sender_address_set || $confirmation_address_set) {
      $this->checkAuthorizedEmailAddresses();
    }
  }

  function onNewsletterUpdate(Newsletter $newsletter, Newsletter $old_newsletter = null) {
    if ($old_newsletter === null || $newsletter->sender_address === $old_newsletter->sender_address) {
      return;
    }
    if ($newsletter->type === Newsletter::TYPE_STANDARD && $newsletter->status === Newsletter::STATUS_SCHEDULED) {
      $this->checkAuthorizedEmailAddresses();
    }
    if (in_array($newsletter->type, $this->automatic_email_types, true) && $newsletter->status === Newsletter::STATUS_ACTIVE) {
      $this->checkAuthorizedEmailAddresses();
    }
  }

  private function validateAddressesInSettings($authorized_emails, $result = []) {
    $default_sender_address = $this->settings->get('sender.address');
    $signup_confirmation_address = $this->settings->get('signup_confirmation.from.address');

    if (!$this->validateAuthorizedEmail($authorized_emails, $default_sender_address)) {
      $result['invalid_sender_address'] = $default_sender_address;
    }
    if (!$this->validateAuthorizedEmail($authorized_emails, $signup_confirmation_address)) {
      $result['invalid_confirmation_address'] = $signup_confirmation_address;
    }

    return $result;
  }

  private function validateAddressesInScheduledAndAutomaticEmails($authorized_emails, $result = []) {
    $condittion = sprintf(
      "(`type` = '%s' AND `status` = '%s') OR (`type` IN ('%s') AND `status` = '%s')",
      Newsletter::TYPE_STANDARD,
      Newsletter::STATUS_SCHEDULED,
      implode("', '", $this->automatic_email_types),
      Newsletter::STATUS_ACTIVE
    );

    $newsletters = Newsletter::whereRaw($condittion)->findMany();

    $invalid_senders_in_newsletters = [];
    foreach ($newsletters as $newsletter) {
      if ($this->validateAuthorizedEmail($authorized_emails, $newsletter->sender_address)) {
        continue;
      }
      $invalid_senders_in_newsletters[] = [
        'newsletter_id' => $newsletter->id,
        'subject' => $newsletter->subject,
        'sender_address' => $newsletter->sender_address,
      ];
    }

    if (!count($invalid_senders_in_newsletters)) {
      return $result;
    }

    $result['invalid_senders_in_newsletters'] = $invalid_senders_in_newsletters;
    return $result;
  }

  /**
   * @param array|null $error
   */
  private function updateMailerLog(array $error = null) {
    if ($error) {
      return;
    }
    $mailer_log_error = MailerLog::getError();
    if ($mailer_log_error && $mailer_log_error['operation'] === MailerError::OPERATION_AUTHORIZATION) {
      MailerLog::resumeSending();
    }
  }

  private function validateAuthorizedEmail($authorized_emails, $email) {
    return in_array(strtolower($email), $authorized_emails, true);
  }
}
