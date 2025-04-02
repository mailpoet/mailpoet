<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\WooCommerce\Payloads;

use MailPoet\Automation\Engine\Integration\Payload;
use WC_Order_Item_Product;
use WC_Product;

class OrderPayload implements Payload {

  /** @var \WC_Order */
  private $order;

  public function __construct(
    \WC_Order $order
  ) {
    $this->order = $order;
  }

  public function getOrder(): \WC_Order {
    return $this->order;
  }

  public function getEmail(): string {
    return $this->order->get_billing_email();
  }

  public function getId(): int {
    return $this->order->get_id();
  }

  public function getProductIds(): array {
    $productIds = [];
    foreach ($this->order->get_items() as $item) {
      if (!$item instanceof WC_Order_Item_Product) {
        continue;
      }
      $productIds[] = $item->get_product_id();
    }
    return array_unique($productIds);
  }

  public function getCrossSellIds(): array {
    $crossSellIds = [];
    foreach ($this->order->get_items() as $item) {
      if (!$item instanceof WC_Order_Item_Product) {
        continue;
      }
      $product = $item->get_product();
      if (!$product instanceof WC_Product) {
        continue;
      }
      $crossSellIds = array_merge($crossSellIds, $product->get_cross_sell_ids());
    }
    return array_unique($crossSellIds);
  }
}
