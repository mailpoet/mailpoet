<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\WooCommerce;

use MailPoet\WooCommerce\Helper as WooCommerceHelper;

class ContextFactory {

  /** @var WooCommerceHelper */
  private $woocommerceHelper;

  public function __construct(
    WooCommerceHelper $woocommerceHelper
  ) {
    $this->woocommerceHelper = $woocommerceHelper;
  }

  /** @return mixed[] */
  public function getContextData(): array {

    if (!$this->woocommerceHelper->isWooCommerceActive()) {
      return [];
    }

    $context = [
      'order_statuses' => $this->woocommerceHelper->getOrderStatuses(),
    ];
    return $context;
  }
}
