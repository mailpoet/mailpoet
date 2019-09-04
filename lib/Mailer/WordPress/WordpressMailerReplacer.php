<?php

namespace MailPoet\Mailer\WordPress;

class WordpressMailerReplacer {
  
  public function replaceWordPressMailer() {
    global $phpmailer;

    return $this->replaceWithCustomPhpMailer($phpmailer);
  }

  private function replaceWithCustomPhpMailer(&$obj = null) {
    $obj = new WordpressMailer(true);
    return $obj;
  }
}
