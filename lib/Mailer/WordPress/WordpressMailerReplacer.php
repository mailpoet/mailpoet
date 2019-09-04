<?php

namespace MailPoet\Mailer\WordPress;

use MailPoet\Features\FeaturesController;

class WordpressMailerReplacer {

  /** @var FeaturesController */
  private $features_controller;

  function __construct(FeaturesController $features_controller) {
    $this->features_controller = $features_controller;
  }

  public function replaceWordPressMailer() {
    global $phpmailer;

    if ($this->features_controller->isSupported(FeaturesController::SEND_WORDPRESS_MAILS_WITH_MP3)) {
      return $this->replaceWithCustomPhpMailer($phpmailer);
    }
    return $phpmailer;
  }

  private function replaceWithCustomPhpMailer(&$obj = null) {
    $obj = new WordpressMailer(true);
    return $obj;
  }
}
