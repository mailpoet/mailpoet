<?php

namespace MailPoet\Subscribers\Statistics;

class SubscriberStatistics {

  /** @var int */
  private $clickCount;

  /** @var int */
  private $openCount;

  /** @var int */
  private $totalSentCount;

  public function __construct($clickCount, $openCount, $totalSentCount) {
    $this->clickCount = $clickCount;
    $this->openCount = $openCount;
    $this->totalSentCount = $totalSentCount;
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
}
