<?php
namespace MailPoet\Util;

use MailPoet\WP\Functions as WPFunctions;

class Security {
  const HASH_LENGTH = 12;

  static function generateToken($action = 'mailpoet_token') {
    return WPFunctions::get()->wpCreateNonce($action);
  }

  static function generateRandomString($length = 5) {
    $length = max(5, (int)$length);
    $string = bin2hex(random_bytes($length)); // phpcs:ignore
    return substr($string, 0, $length);
  }

  static function generateHash($length = false) {
    $length = ($length) ? $length : self::HASH_LENGTH;
    $auth_key = '';
    if (defined('AUTH_KEY')) {
      $auth_key = AUTH_KEY;
    }
    return substr(
      md5($auth_key . self::generateRandomString(64)),
      0,
      $length
    );
  }
}
