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
   * Recipients whose tracking consent, as it stood when we sent, did not let us
   * measure them. Zero unless the site captures consent, which keeps every
   * screen looking exactly as it does today for sites with no opt-outs.
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
    $this->notTrackedCount = $notTrackedCount;
  }

  public function getNotTrackedCount(): int {
    return $this->notTrackedCount;
  }

  /**
   * The denominator for open and click rates: the recipients we were allowed
   * to measure.
   *
   * Clamped at zero because the two counters have different writers —
   * count_processed is recomputed from the database on every batch, while
   * statistics_newsletters rows are written even for a failed send — so they
   * can genuinely drift apart.
   */
  public function getTrackedSentCount(): int {
    return max(0, $this->totalSentCount - $this->notTrackedCount);
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
      // Deliberately inside asArray() rather than beside it: MailPoet Premium
      // re-emits this array verbatim, so keeping the new keys here is what
      // makes tracked-only rates a free-plugin-only change.
      'notTracked' => $this->notTrackedCount,
      'trackedSent' => $this->getTrackedSentCount(),
      'revenue' => empty($this->wooCommerceRevenue) ? null : $this->wooCommerceRevenue->asArray(),
    ];
  }
}
