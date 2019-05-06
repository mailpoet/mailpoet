<?php

namespace MailPoet\Test\DataFactories;

use MailPoet\Models\StatisticsClicks;

class StatisticsWooCommercePurchases {
  protected $data;

  public function __construct(StatisticsClicks $click, $order) {
    $this->data = [
      'newsletter_id' => $click->newsletter_id,
      'subscriber_id' => $click->subscriber_id,
      'queue_id' => $click->queue_id,
      'click_id' => $click->id,
      'order_id' => $order['id'],
      'order_currency' => $order['currency'],
      'order_price_total' => $order['total'],
    ];
  }

  /** @return \MailPoet\Models\StatisticsWooCommercePurchases */
  public function create() {
    return \MailPoet\Models\StatisticsWooCommercePurchases::createOrUpdate($this->data);
  }
}
