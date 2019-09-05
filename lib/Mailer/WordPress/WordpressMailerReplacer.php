<?php

namespace MailPoet\Mailer\WordPress;

use MailPoet\Features\FeaturesController;
use MailPoet\Mailer\Mailer;

class WordpressMailerReplacer {

  /** @var FeaturesController */
  private $features_controller;

  /** @var Mailer */
  private $mailer;

  function __construct(FeaturesController $features_controller, Mailer $mailer) {
    $this->features_controller = $features_controller;
    $this->mailer = $mailer;
  }

  public function replaceWordPressMailer() {
    global $phpmailer;

    if ($this->features_controller->isSupported(FeaturesController::SEND_WORDPRESS_MAILS_WITH_MP3)) {
      return $this->replaceWithCustomPhpMailer($phpmailer);
    }
    return $phpmailer;
  }

  private function replaceWithCustomPhpMailer(&$obj = null) {
    $obj = new WordPressMailer($this->mailer);
    return $obj;
  }
}
