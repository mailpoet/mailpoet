<?php

namespace MailPoet\Mailer\WordPress;

// Load PHPMailer class, so we can subclass it.
if (!class_exists('PHPMailer')) {
  require_once ABSPATH . WPINC . '/class-phpmailer.php';
}

class WordPressMailer extends \PHPMailer {

  public function send() {

  }

}
