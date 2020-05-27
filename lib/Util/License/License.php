<?php

namespace MailPoet\Util\License;

class License {
  const FREE_PREMIUM_SUBSCRIBERS_LIMIT = 1000;

  public static function getLicense($license = false) {
    if (!$license) {
      $license = defined('MAILPOET_PREMIUM_LICENSE') ?
      MAILPOET_PREMIUM_LICENSE :
      false;
    }
    return $license;
  }

  public function hasLicense(): bool {
    return (bool)self::getLicense();
  }
}
