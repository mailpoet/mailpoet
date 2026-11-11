<?php declare(strict_types = 1);

namespace MailPoet\Test\DataFactories;

class WooCommerceSubscription {
  public function createSubscription(int $userId, int $subscriptionProductId, string $status = 'active'): \WC_Subscription {
    $args = [
      'status' => $status,
      'customer_id' => $userId,
      'billing_period' => 'month',
      'billing_interval' => 1,
    ];
    $sub = wcs_create_subscription($args);
    if ($sub instanceof \WP_Error) {
      throw new \RuntimeException('Could not create the subscription: ' . $sub->get_error_message());
    }
    $product = wc_get_product($subscriptionProductId);
    if (!$product instanceof \WC_Product) {
      throw new \RuntimeException('Product ' . $subscriptionProductId . ' does not exist.');
    }
    $sub->add_product($product);
    return $sub;
  }
}
