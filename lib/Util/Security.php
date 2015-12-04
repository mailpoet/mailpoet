<?php
namespace MailPoet\Util;

require_once(ABSPATH . 'wp-includes/pluggable.php');

class Security {
  static function generateToken() {
    return wp_create_nonce('mailpoet_token');
  }

  static function generateRandomString($length = 5) {
    // non-cryptographically strong random generator
    return substr(
      md5(
        uniqid(
          mt_rand(), true)
      ),
      0,
      (!is_int($length) || $length <= 5 || $length >= 32) ? 5 : $length);
  }
}