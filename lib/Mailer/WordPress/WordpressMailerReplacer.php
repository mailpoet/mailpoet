<?php

namespace MailPoet\Mailer\WordPress;

use MailPoet\Features\FeaturesController;
use MailPoet\Mailer\Mailer;
use MailPoet\Mailer\MetaInfo;
use MailPoet\Settings\SettingsController;

class WordpressMailerReplacer {

  /** @var FeaturesController */
  private $features_controller;

  /** @var Mailer */
  private $mailer;

  /** @var MetaInfo */
  private $mailerMetaInfo;

  /** @var SettingsController */
  private $settings;

  function __construct(FeaturesController $features_controller, Mailer $mailer, MetaInfo $mailerMetaInfo, SettingsController $settings) {
    $this->features_controller = $features_controller;
    $this->mailer = $mailer;
    $this->mailerMetaInfo = $mailerMetaInfo;
    $this->settings = $settings;
  }

  public function replaceWordPressMailer() {
    global $phpmailer;

    if ($this->features_controller->isSupported(FeaturesController::SEND_WORDPRESS_MAILS_WITH_MP3)) {
      $phpmailer = new WordPressMailer($this->mailer, $this->createFallbackMailer(), $this->mailerMetaInfo);
    }
    return $phpmailer;
  }

  private function createFallbackMailer() {
    $fallback_mailer = new Mailer($this->settings);
    $fallback_mailer->init(
      ['method' => Mailer::METHOD_PHPMAIL],
      $this->mailer->sender,
      $this->mailer->reply_to,
      $this->mailer->return_path
    );
    return $fallback_mailer;
  }
}
