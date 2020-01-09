<?php

namespace MailPoet\Services;

use MailPoet\Mailer\MailerError;
use MailPoet\Mailer\MailerLog;
use MailPoet\Models\Newsletter;
use MailPoet\Settings\SettingsController;
use MailPoetVendor\Carbon\Carbon;

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

  public function __construct(SettingsController $settingsController, Bridge $bridge) {
    $this->settings = $settingsController;
    $this->bridge = $bridge;
  }

  public function checkAuthorizedEmailAddresses() {
    if (!Bridge::isMPSendingServiceEnabled()) {
      $this->settings->set(self::AUTHORIZED_EMAIL_ADDRESSES_ERROR_SETTING, null);
      $this->updateMailerLog();
      return;
    }

    $authorizedEmails = $this->bridge->getAuthorizedEmailAddresses();
    // Keep previous check result for an invalid response from API
    if ($authorizedEmails === false) {
      return;
    }
    $authorizedEmails = array_map('strtolower', $authorizedEmails);

    $result = [];
    $result = $this->validateAddressesInSettings($authorizedEmails, $result);
    $result = $this->validateAddressesInScheduledAndAutomaticEmails($authorizedEmails, $result);
    $this->settings->set(self::AUTHORIZED_EMAIL_ADDRESSES_ERROR_SETTING, $result ?: null);
    $this->updateMailerLog($result);
  }

  public function onSettingsSave($settings) {
    $senderAddressSet = !empty($settings['sender']['address']);
    if ($senderAddressSet) {
      $this->checkAuthorizedEmailAddresses();
    }
  }

  public function onNewsletterUpdate(Newsletter $newsletter, Newsletter $oldNewsletter = null) {
    if ($oldNewsletter === null || $newsletter->senderAddress === $oldNewsletter->senderAddress) {
      return;
    }
    if ($newsletter->type === Newsletter::TYPE_STANDARD && $newsletter->status === Newsletter::STATUS_SCHEDULED) {
      $this->checkAuthorizedEmailAddresses();
    }
    if (in_array($newsletter->type, $this->automaticEmailTypes, true) && $newsletter->status === Newsletter::STATUS_ACTIVE) {
      $this->checkAuthorizedEmailAddresses();
    }
  }

  private function validateAddressesInSettings($authorizedEmails, $result = []) {
    $defaultSenderAddress = $this->settings->get('sender.address');

    if (!$this->validateAuthorizedEmail($authorizedEmails, $defaultSenderAddress)) {
      $result['invalid_sender_address'] = $defaultSenderAddress;
    }

    return $result;
  }

  private function validateAddressesInScheduledAndAutomaticEmails($authorizedEmails, $result = []) {
    $condittion = sprintf(
      "(`type` = '%s' AND `status` = '%s') OR (`type` IN ('%s') AND `status` = '%s')",
      Newsletter::TYPE_STANDARD,
      Newsletter::STATUS_SCHEDULED,
      implode("', '", $this->automaticEmailTypes),
      Newsletter::STATUS_ACTIVE
    );

    $newsletters = Newsletter::whereRaw($condittion)->findMany();

    $invalidSendersInNewsletters = [];
    foreach ($newsletters as $newsletter) {
      if ($this->validateAuthorizedEmail($authorizedEmails, $newsletter->senderAddress)) {
        continue;
      }
      $invalidSendersInNewsletters[] = [
        'newsletter_id' => $newsletter->id,
        'subject' => $newsletter->subject,
        'sender_address' => $newsletter->senderAddress,
      ];
    }

    if (!count($invalidSendersInNewsletters)) {
      return $result;
    }

    $result['invalid_senders_in_newsletters'] = $invalidSendersInNewsletters;
    return $result;
  }

  /**
   * @param array|null $error
   */
  private function updateMailerLog(array $error = null) {
    if ($error) {
      return;
    }
    $mailerLogError = MailerLog::getError();
    if ($mailerLogError && $mailerLogError['operation'] === MailerError::OPERATION_AUTHORIZATION) {
      MailerLog::resumeSending();
    }
  }

  private function validateAuthorizedEmail($authorizedEmails, $email) {
    return in_array(strtolower($email), $authorizedEmails, true);
  }
}
