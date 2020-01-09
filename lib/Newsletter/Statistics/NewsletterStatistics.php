<?php

namespace MailPoet\Newsletter\Statistics;

class NewsletterStatistics {

  /** @var int */
  private $clickCount;

  /** @var int */
  private $openCount;

  /** @var int */
  private $unsubscribeCount;

  /** @var int */
  private $totalSentCount;

  public function __construct($clickCount, $openCount, $unsubscribeCount, $totalSentCount) {
    $this->clickCount = $clickCount;
    $this->openCount = $openCount;
    $this->unsubscribeCount = $unsubscribeCount;
    $this->totalSentCount = $totalSentCount;
  }

  /**
   * @return int
   */
  public function getClickCount() {
    return $this->clickCount;
  }

  /**
   * @return int
   */
  public function getOpenCount() {
    return $this->openCount;
  }

  /**
   * @return int
   */
  public function getUnsubscribeCount() {
    return $this->unsubscribeCount;
  }

  /**
   * @return int
   */
  public function getTotalSentCount() {
    return $this->totalSentCount;
  }

}
