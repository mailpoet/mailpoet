<?php
namespace MailPoet\Util;

class Security {
  static function generateToken() {
    return wp_create_nonce('mailpoet_token');
  }
}