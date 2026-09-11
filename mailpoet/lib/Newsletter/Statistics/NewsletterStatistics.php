<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Newsletter\Statistics;

class NewsletterStatistics {

  /** @var int */
  private $clickCount;

  /** @var int */
  private $openCount;

  /** @var int */
  private $machineOpenCount;

  /** @var int */
  private $unsubscribeCount;

  /** @var int */
  private $bounceCount;

  /** @var int */
  private $totalSentCount;

  /**
   * Recipients we were not allowed to measure (opted out before the send, or
   * never asked on a strict-mode site). Zero unless the site captures consent,
   * so every screen looks exactly as it does today for sites with no opt-outs.
   *
   * @var int
   */
  private $notTrackedCount = 0;

  /** @var WooCommerceRevenue|null */
  private $wooCommerceRevenue;

  public function __construct(
    $clickCount,
    $openCount,
    $unsubscribeCount,
    $bounceCount,
    $totalSentCount,
    $wooCommerceRevenue
  ) {
    $this->clickCount = $clickCount;
    $this->openCount = $openCount;
    $this->unsubscribeCount = $unsubscribeCount;
    $this->bounceCount = $bounceCount;
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

  public function getBounceCount(): int {
    return $this->bounceCount;
  }

  public function getTotalSentCount(): int {
    return $this->totalSentCount;
  }

  public function setNotTrackedCount(int $notTrackedCount): void {
    $this->notTrackedCount = max(0, $notTrackedCount);
  }

  /**
   * Bounded to the sent count: count_processed and the per-recipient rows have
   * different writers (a failed send writes a row without bumping
   * count_processed), so the raw count can exceed the audience it describes.
   */
  public function getNotTrackedCount(): int {
    return min($this->notTrackedCount, $this->totalSentCount);
  }

  /** The denominator for open and click rates: the recipients we could measure. */
  public function getTrackedSentCount(): int {
    return $this->totalSentCount - $this->getNotTrackedCount();
  }

  /** 0-100. Share of the audience the open and click rates rest on. */
  public function getTrackingCoverage(): float {
    if ($this->totalSentCount <= 0) {
      return 100.0;
    }
    return ($this->getTrackedSentCount() * 100) / $this->totalSentCount;
  }

  public function getWooCommerceRevenue(): ?WooCommerceRevenue {
    return $this->wooCommerceRevenue;
  }

  public function setMachineOpenCount(int $machineOpenCount): void {
    $this->machineOpenCount = $machineOpenCount;
  }

  public function getMachineOpenCount(): int {
    return $this->machineOpenCount;
  }

  public function asArray(): array {
    return [
      'clicked' => $this->clickCount,
      'opened' => $this->openCount,
      'machineOpened' => $this->machineOpenCount,
      'unsubscribed' => $this->unsubscribeCount,
      'bounced' => $this->bounceCount,
      // Inside asArray() on purpose: MailPoet Premium re-emits this array
      // verbatim, so keeping the keys here is what makes this a free-plugin-only change.
      'notTracked' => $this->getNotTrackedCount(),
      'trackedSent' => $this->getTrackedSentCount(),
      'trackingCoverage' => $this->getTrackingCoverage(),
      'revenue' => empty($this->wooCommerceRevenue) ? null : $this->wooCommerceRevenue->asArray(),
    ];
  }
}
