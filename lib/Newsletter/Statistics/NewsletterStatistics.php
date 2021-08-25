<?php

namespace MailPoet\Newsletter\Statistics;

class NewsletterStatistics {

  /** @var int */
  private $clickCount;

  /** @var int */
  private $openCount;

  /** @var int */
  private $machineOpens;

  /** @var int */
  private $unsubscribeCount;

  /** @var int */
  private $totalSentCount;

  /** @var WooCommerceRevenue|null */
  private $wooCommerceRevenue;

  public function __construct($clickCount, $openCount, $unsubscribeCount, $totalSentCount, $wooCommerceRevenue) {
    $this->clickCount = $clickCount;
    $this->openCount = $openCount;
    $this->unsubscribeCount = $unsubscribeCount;
    $this->totalSentCount = $totalSentCount;
    $this->wooCommerceRevenue = $wooCommerceRevenue;
  }

  public function getClickCount(): int {
    return $this->clickCount;
  }

  public function getOpenCount(): int {
    return $this->openCount;
  }

  public function getUnsubscribeCount(): int {
    return $this->unsubscribeCount;
  }

  public function getTotalSentCount(): int {
    return $this->totalSentCount;
  }

  public function getWooCommerceRevenue(): ?WooCommerceRevenue {
    return $this->wooCommerceRevenue;
  }

  public function setMachineOpens(int $machineOpens): void {
    $this->machineOpens = $machineOpens;
  }

  public function getMachineOpens(): int {
    return $this->machineOpens;
  }

  public function asArray(): array {
    return [
      'clicked' => $this->clickCount,
      'opened' => $this->openCount,
      'machineOpens' => $this->machineOpens,
      'unsubscribed' => $this->unsubscribeCount,
      'revenue' => empty($this->wooCommerceRevenue) ? null : $this->wooCommerceRevenue->asArray(),
    ];
  }
}
