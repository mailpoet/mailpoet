<?php

namespace MailPoet\Mailer\WordPress;

use MailPoet\Features\FeaturesController;
use MailPoet\Mailer\Mailer;
use MailPoet\Mailer\MetaInfo;

class WordpressMailerReplacer {

  /** @var FeaturesController */
  private $features_controller;

  /** @var Mailer */
  private $mailer;

  /** @var MetaInfo */
  private $mailerMetaInfo;

  function __construct(FeaturesController $features_controller, Mailer $mailer, MetaInfo $mailerMetaInfo) {
    $this->features_controller = $features_controller;
    $this->mailer = $mailer;
    $this->mailerMetaInfo = $mailerMetaInfo;
  }

  public function replaceWordPressMailer() {
    global $phpmailer;

    if ($this->features_controller->isSupported(FeaturesController::SEND_WORDPRESS_MAILS_WITH_MP3)) {
      return $this->replaceWithCustomPhpMailer($phpmailer);
    }
    return $phpmailer;
  }

  private function replaceWithCustomPhpMailer(&$obj = null) {
    $obj = new WordPressMailer($this->mailer, $this->mailerMetaInfo);
    return $obj;
  }
}
