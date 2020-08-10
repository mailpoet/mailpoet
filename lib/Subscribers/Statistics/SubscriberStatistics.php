<?php

namespace MailPoet\Subscribers\Statistics;

use MailPoet\Newsletter\Statistics\WooCommerceRevenue;

class SubscriberStatistics {

  /** @var int */
  private $clickCount;

  /** @var int */
  private $openCount;

  /** @var int */
  private $totalSentCount;

  /** @var WooCommerceRevenue|null */
  private $wooCommerceRevenue;

  public function __construct($clickCount, $openCount, $totalSentCount, $wooCommerceRevenue = null) {
    $this->clickCount = $clickCount;
    $this->openCount = $openCount;
    $this->totalSentCount = $totalSentCount;
    $this->wooCommerceRevenue = $wooCommerceRevenue;
  }

  /**
   * @return int
   */
  public function getClickCount(): int {
    return $this->clickCount;
  }

  /**
   * @return int
   */
  public function getOpenCount(): int {
    return $this->openCount;
  }

  /**
   * @return int
   */
  public function getTotalSentCount(): int {
    return $this->totalSentCount;
  }

  /**
   * @return WooCommerceRevenue|null
   */
  public function getWooCommerceRevenue() {
    return $this->wooCommerceRevenue;
  }
}
