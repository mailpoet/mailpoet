<?php

namespace MailPoet\Mailer\WordPress;

use MailPoet\Mailer\Mailer;
use MailPoet\Mailer\MetaInfo;
use MailPoet\Settings\SettingsController;

class WordpressMailerReplacer {

  /** @var Mailer */
  private $mailer;

  /** @var MetaInfo */
  private $mailerMetaInfo;

  /** @var SettingsController */
  private $settings;

  function __construct(Mailer $mailer, MetaInfo $mailerMetaInfo, SettingsController $settings) {
    $this->mailer = $mailer;
    $this->mailerMetaInfo = $mailerMetaInfo;
    $this->settings = $settings;
  }

  public function replaceWordPressMailer() {
    global $phpmailer;
    if ($this->settings->get('send_transactional_emails', false)) {
      $phpmailer = new WordPressMailer($this->mailer, new FallbackMailer($this->settings), $this->mailerMetaInfo);
    }
    return $phpmailer;
  }
}
