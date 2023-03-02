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
    $sub->add_product(wc_get_product($subscriptionProductId));
    return $sub;
  }
}
