<?php
namespace MailPoet\Util\License;

class License {
  static function getLicense($license = false) {
    if(!$license) {
      $license = defined('MAILPOET_PREMIUM_LICENSE') ?
      MAILPOET_PREMIUM_LICENSE :
      false;
    }
    return $license;
  }
}