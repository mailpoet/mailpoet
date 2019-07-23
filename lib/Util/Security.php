<?php
namespace MailPoet\Util;

use MailPoet\WP\Functions as WPFunctions;

class Security {
  const HASH_LENGTH = 12;
  const UNSUBSCRIBE_TOKEN_LENGTH = 15;

  static function generateToken($action = 'mailpoet_token') {
    return WPFunctions::get()->wpCreateNonce($action);
  }

  /**
   * Generate random lowercase alphanumeric string.
   * 1 lowercase alphanumeric character = 6 bits (because log2(36) = 5.17)
   * So 3 bytes = 4 characters
   */
  static function generateRandomString($length = 5) {
    $length = max(5, (int)$length);
    $string = base_convert(bin2hex(random_bytes(ceil(3 * $length / 4))), 16, 36); // phpcs:ignore
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

  static public function generateUnsubscribeToken($model) {
    $token = self::generateRandomString(self::UNSUBSCRIBE_TOKEN_LENGTH);
    $found = $model::whereEqual('unsubscribe_token', $token)->count();
    while ($found > 0) {
      $token = self::generateRandomString(self::UNSUBSCRIBE_TOKEN_LENGTH);
      $found = $model::whereEqual('unsubscribe_token', $token)->count();
    }
    return $token;
  }
}
