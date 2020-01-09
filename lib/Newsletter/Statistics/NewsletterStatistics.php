<?php

namespace MailPoet\Newsletter\Statistics;

class NewsletterStatistics {

  /** @var int */
  private $click_count;

  /** @var int */
  private $open_count;

  /** @var int */
  private $unsubscribe_count;

  /** @var int */
  private $total_sent_count;

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
