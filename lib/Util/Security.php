<?php
namespace MailPoet\Util;

require_once(ABSPATH . 'wp-includes/pluggable.php');

class Security {
  static function generateToken() {
    return wp_create_nonce('mailpoet_token');
  }

  static function generateRandomString($length) {
    return substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $length);
  }
}