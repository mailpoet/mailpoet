<?php
namespace MailPoet\Util;

use MailPoet\WP\Functions as WPFunctions;

if (!defined('ABSPATH')) exit;

class Security {
  const HASH_LENGTH = 12;

  static function generateToken($action = 'mailpoet_token') {
    return WPFunctions::get()->wpCreateNonce($action);
  }

  static function generateRandomString($length = 5) {
    // non-cryptographically strong random generator
    return substr(
      md5(uniqid((string)mt_rand(), true)),
      0,
      min(max(5, (int)$length), 32)
    );
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
