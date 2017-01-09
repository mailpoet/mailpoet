<?php
namespace MailPoet\Util;

use MailPoet\API\API;
use MailPoet\Models\Setting;

if(!defined('ABSPATH')) exit;
require_once(ABSPATH . 'wp-includes/pluggable.php');

class Security {
  const SETTING_VALUE = 'security_key';

  static function generateToken($type, $length = 6, $unique_string = false) {
    switch($type) {
      case 'subscriber':
        return substr(
          md5(self::getOrCreateSecurityKey() . $unique_string),
          0,
          $length
        );
      case 'newsletter':
        return substr(
          md5(self::getOrCreateSecurityKey() . self::generateRandomString()),
          0,
          $length
        );
      case 'API':
        return wp_create_nonce(API::TOKEN_NAME);
      default:
        return substr(
          self::generateRandomString(),
          0,
          $length
        );
    }
  }

  static function generateRandomString($length = 15) {
    // non-cryptographically strong random generator
    return substr(
      md5(uniqid(mt_rand(), true)),
      0,
      min(max(5, (int)$length), 32)
    );
  }

  static function getOrCreateSecurityKey($default_key = 'AUTH_KEY') {
    $security_key = Setting::getValue(self::SETTING_VALUE);
    if($security_key) return $security_key;
    // if security key does not exist, check if WP's AUTH_KEY is defined and use it
    $security_key = (defined($default_key)) ?
      constant($default_key) :
      Security::generateRandomString();
    Setting::setValue(self::SETTING_VALUE, $security_key);
    return $security_key;
  }
}