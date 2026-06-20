<?php declare(strict_types = 1);

namespace MailPoet\WooCommerce;

class OrderAttributionFields {
  // WooCommerce persists order attribution data under this meta prefix. MailPoet
  // reads and writes only the standard source fields (utm_source, source_type,
  // session_start_time) through it; it no longer stores its own namespaced fields.
  const META_PREFIX = '_wc_order_attribution_';

  public static function getMetaKey(string $fieldName): string {
    return self::META_PREFIX . $fieldName;
  }
}
