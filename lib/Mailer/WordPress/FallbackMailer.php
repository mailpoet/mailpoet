<?php

namespace MailPoet\Mailer\WordPress;

use MailPoet\Mailer\Mailer;

class FallbackMailer extends Mailer {
  const FALLBACK_METHOD = self::METHOD_PHPMAIL;

  public function init($mailer = false, $sender = false, $reply_to = false, $return_path = false) {
    // init is called lazily from when sending, we need to set correct sending method
    parent::init(['method' => self::FALLBACK_METHOD], $sender, $reply_to, $return_path);
  }
}
