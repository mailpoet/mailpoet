<?php

namespace MailPoet\Newsletter\Statistics;

use MailPoet\WooCommerce\Helper;

class WooCommerceRevenue {

  /** @var string */
  private $currency;

  /** @var float */
  private $value;

  /** @var int */
  private $ordersCount;

  /** @var Helper */
  private $wooCommerceHelper;

  public function __construct($currency, $value, $ordersCount, Helper $wooCommerceHelper) {
    $this->currency = $currency;
    $this->value = $value;
    $this->ordersCount = $ordersCount;
    $this->wooCommerceHelper = $wooCommerceHelper;
  }

  /** @return string */
  public function getCurrency() {
    return $this->currency;
  }

  /** @return int */
  public function getOrdersCount() {
    return $this->ordersCount;
  }

  /** @return float */
  public function getValue() {
    return $this->value;
  }

  /** @return string */
  public function getFormattedValue() {
    return $this->wooCommerceHelper->getRawPrice($this->value, ['currency' => $this->currency]);
  }

  /**
   * @return array
   */
  public function asArray() {
    return [
      'currency' => $this->currency,
      'value' => (float)$this->value,
      'count' => (int)$this->ordersCount,
      'formatted' => $this->getFormattedValue(),
    ];
  }
}
