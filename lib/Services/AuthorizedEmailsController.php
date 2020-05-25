<?php

namespace MailPoet\Services;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Mailer\Mailer;
use MailPoet\Mailer\MailerError;
use MailPoet\Mailer\MailerLog;
use MailPoet\Models\Newsletter;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Settings\SettingsController;

class AuthorizedEmailsController {
  const AUTHORIZED_EMAIL_ADDRESSES_ERROR_SETTING = 'authorized_emails_addresses_check';

  /** @var Bridge */
  private $bridge;

  /** @var SettingsController */
  private $settings;

  /** @var NewslettersRepository */
  private $newslettersRepository;

  private $automaticEmailTypes = [
    Newsletter::TYPE_WELCOME,
    Newsletter::TYPE_NOTIFICATION,
    Newsletter::TYPE_AUTOMATIC,
  ];

  public function __construct(
    SettingsController $settingsController,
    Bridge $bridge,
    NewslettersRepository $newslettersRepository
  ) {
    $this->settings = $settingsController;
    $this->bridge = $bridge;
    $this->newslettersRepository = $newslettersRepository;
  }

  public function setFromEmailAddress(string $address) {
    $authorizedEmails = array_map('strtolower', $this->bridge->getAuthorizedEmailAddresses() ?: []);
    $isAuthorized = $this->validateAuthorizedEmail($authorizedEmails, $address);
    if (!$isAuthorized) {
      throw new \InvalidArgumentException("Email address '$address' is not authorized");
    }

    // update FROM address in settings & all scheduled and active emails
    $this->settings->set('sender.address', $address);
    $result = $this->validateAddressesInScheduledAndAutomaticEmails($authorizedEmails);
    foreach ($result['invalid_senders_in_newsletters'] ?? [] as $item) {
      $newsletter = $this->newslettersRepository->findOneById((int)$item['newsletter_id']);
      if ($newsletter) {
        $newsletter->setSenderAddress($address);
      }
    }
    $this->newslettersRepository->flush();
    $this->settings->set(self::AUTHORIZED_EMAIL_ADDRESSES_ERROR_SETTING, null);
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
    $mailpoetSendingMethodSet = ($settings[Mailer::MAILER_CONFIG_SETTING_NAME]['method'] ?? null) === Mailer::METHOD_MAILPOET;
    if ($senderAddressSet || $mailpoetSendingMethodSet) {
      $this->checkAuthorizedEmailAddresses();
    }
  }

  public function onNewsletterSenderAddressUpdate(NewsletterEntity $newsletter, string $oldSenderAddress = null) {
    if ($newsletter->getSenderAddress() === $oldSenderAddress) {
      return;
    }
    if ($newsletter->getType() === NewsletterEntity::TYPE_STANDARD && $newsletter->getStatus() === NewsletterEntity::STATUS_SCHEDULED) {
      $this->checkAuthorizedEmailAddresses();
    }
    if (in_array($newsletter->getType(), $this->automaticEmailTypes, true) && $newsletter->getStatus() === Newsletter::STATUS_ACTIVE) {
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
