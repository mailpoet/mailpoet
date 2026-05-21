<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet\PersonalizationTags;

use MailPoet\WooCommerce\Helper as WooCommerceHelper;

class OrderReviewUrl {
  private WooCommerceHelper $wooCommerceHelper;

  public function __construct(
    WooCommerceHelper $wooCommerceHelper
  ) {
    $this->wooCommerceHelper = $wooCommerceHelper;
  }

  public function getUrl(array $context, array $args = []): string {
    $order = $context['order'] ?? null;
    if (!$order instanceof \WC_Order) {
      return '';
    }

    if ($order->get_id() < 1 || $order->get_order_key() === '') {
      return '';
    }

    if (!$this->isSupported()) {
      return '';
    }

    if (!$this->wooCommerceHelper->wcOrderHasActionableReviewItems($order)) {
      return '';
    }

    return $this->wooCommerceHelper->wcGetReviewOrderUrl($order);
  }

  public function isSupported(): bool {
    return $this->wooCommerceHelper->wcSupportsOrderReviewUrl();
  }
}
