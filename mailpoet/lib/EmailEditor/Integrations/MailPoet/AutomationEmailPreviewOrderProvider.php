<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet;

use MailPoet\Config\AccessControl;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;
use MailPoet\WP\Functions as WPFunctions;

class AutomationEmailPreviewOrderProvider {
  private WooCommerceHelper $wooCommerceHelper;

  private WPFunctions $wp;

  public function __construct(
    WooCommerceHelper $wooCommerceHelper,
    WPFunctions $wp
  ) {
    $this->wooCommerceHelper = $wooCommerceHelper;
    $this->wp = $wp;
  }

  public function getOrder(): ?\WC_Order {
    if (!class_exists(\WC_Order::class)) {
      return null;
    }

    // sample data for users who can edit emails
    if (!$this->wp->currentUserCan(AccessControl::PERMISSION_MANAGE_EMAILS)) {
      return null;
    }

    try {
      $order = $this->getExistingReviewableOrder();
      if ($order instanceof \WC_Order) {
        return $order;
      }
    } catch (\Throwable $e) {
      return null;
    }

    return null;
  }

  private function getExistingReviewableOrder(): ?\WC_Order {
    $orders = $this->wooCommerceHelper->wcGetOrders([
      'type' => 'shop_order',
      'status' => 'completed',
      'limit' => 10,
      'orderby' => 'date',
      'order' => 'DESC',
    ]);

    if (!is_array($orders)) {
      return null;
    }

    foreach ($orders as $order) {
      if (!$order instanceof \WC_Order) {
        continue;
      }

      if ($order->get_order_key() === '' || count($order->get_items()) === 0) {
        continue;
      }

      if ($this->wooCommerceHelper->wcOrderHasActionableReviewItems($order)) {
        return $order;
      }
    }

    return null;
  }
}
