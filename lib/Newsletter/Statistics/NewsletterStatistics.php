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

  public function __construct($click_count, $open_count, $unsubscribe_count, $total_sent_count) {
    $this->click_count = $click_count;
    $this->open_count = $open_count;
    $this->unsubscribe_count = $unsubscribe_count;
    $this->total_sent_count = $total_sent_count;
  }

  /**
   * @return int
   */
  public function getClickCount() {
    return $this->click_count;
  }

  /**
   * @return int
   */
  public function getOpenCount() {
    return $this->open_count;
  }

  /**
   * @return int
   */
  public function getUnsubscribeCount() {
    return $this->unsubscribe_count;
  }

  /**
   * @return int
   */
  public function getTotalSentCount() {
    return $this->total_sent_count;
  }

}
