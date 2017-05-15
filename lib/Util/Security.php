<?php
namespace MailPoet\Util;

if(!defined('ABSPATH')) exit;
require_once(ABSPATH . 'wp-includes/pluggable.php');

class Security {
  const HASH_LENGTH = 12;

  static function generateToken($action = 'mailpoet_token') {
    return wp_create_nonce($action);
  }

  static function generateRandomString($length = 5) {
    // non-cryptographically strong random generator
    return substr(
      md5(uniqid(mt_rand(), true)),
      0,
      min(max(5, (int)$length), 32)
    );
  }

  static function generateHash($length = false) {
    $length = ($length) ? $length : self::HASH_LENGTH;
    return substr(
      md5(AUTH_KEY . self::generateRandomString(64)),
      0,
      $length
    );
  }
}