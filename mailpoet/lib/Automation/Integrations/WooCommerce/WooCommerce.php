<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\WooCommerce;

use Automattic\WooCommerce\Utilities\OrderUtil;

class WooCommerce {
  public function isWooCommerceActive(): bool {
    return class_exists('WooCommerce');
  }

  public function wcGetIsPaidStatuses(): array {
    return wc_get_is_paid_statuses();
  }

  public function isWooCommerceCustomOrdersTableEnabled(): bool {
    return $this->isWooCommerceActive()
      && method_exists(OrderUtil::class, 'custom_orders_table_usage_is_enabled')
      && OrderUtil::custom_orders_table_usage_is_enabled();
  }
}
