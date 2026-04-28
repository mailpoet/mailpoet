<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet\Coupons;

class CouponBlockAttributeParser {
  public static function toBoolean($value): bool {
    if (is_bool($value)) {
      return $value;
    }

    if (is_string($value)) {
      return in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true);
    }

    return (bool)$value;
  }
}
