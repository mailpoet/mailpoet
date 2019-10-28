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
  private $total_sent_count;

  function __construct($clickCount, $openCount, $unsubscribeCount, $total_sent_count) {
    $this->clickCount = $clickCount;
    $this->openCount = $openCount;
    $this->unsubscribeCount = $unsubscribeCount;
    $this->total_sent_count = $total_sent_count;
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
    return $this->total_sent_count;
  }

}
