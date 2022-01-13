<?php

namespace MailPoet\Mailer\WordPress;

use MailPoet\Mailer\Mailer;
use MailPoet\Mailer\MetaInfo;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\SubscribersRepository;

class WordpressMailerReplacer {

  /** @var Mailer */
  private $mailer;

  /** @var MetaInfo */
  private $mailerMetaInfo;

  /** @var SettingsController */
  private $settings;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  public function __construct(
    Mailer $mailer,
    MetaInfo $mailerMetaInfo,
    SettingsController $settings,
    SubscribersRepository $subscribersRepository
  ) {
    $this->mailer = $mailer;
    $this->mailerMetaInfo = $mailerMetaInfo;
    $this->settings = $settings;
    $this->subscribersRepository = $subscribersRepository;
  }

  public function replaceWordPressMailer() {
    global $phpmailer;
    // This code needs to be wrapped because it has to run in an early stage of plugin initialisation
    // and in some cases on multisite instance it may run before DB migrator and settings table is not ready at that time
    try {
      $sendTransactional = $this->settings->get('send_transactional_emails', false);
    } catch (\Exception $e) {
      $sendTransactional = false;
    }
    if ($sendTransactional) {
      $phpmailer = new WordPressMailer(
        $this->mailer,
        new FallbackMailer($this->settings),
        $this->mailerMetaInfo,
        $this->subscribersRepository
      );
    }
    return $phpmailer;
  }
}
