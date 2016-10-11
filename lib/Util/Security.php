<?php
namespace MailPoet\Util;

if(!defined('ABSPATH')) exit;
require_once(ABSPATH . 'wp-includes/pluggable.php');

class Security {
  static function generateToken() {
    return wp_create_nonce('mailpoet_token');
  }

  static function generateRandomString($length = 5) {
    // non-cryptographically strong random generator
    return substr(
      md5(uniqid(mt_rand(), true)),
      0,
      min(max(5, (int)$length), 32)
    );
  }
}