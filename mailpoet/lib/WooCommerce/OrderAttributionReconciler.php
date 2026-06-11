<?php declare(strict_types = 1);

namespace MailPoet\WooCommerce;

use Automattic\WooCommerce\Utilities\FeaturesUtil;
use MailPoet\Entities\StatisticsWooCommercePurchaseEntity;
use MailPoet\Settings\TrackingConfig;
use MailPoet\Statistics\StatisticsWooCommercePurchasesRepository;
use MailPoet\WP\Functions as WPFunctions;
use WC_Order;

/**
 * Dual-run reconciliation for the Woo attribution migration (STOMAIL-8136).
 *
 * Compares MailPoet's legacy purchase attribution (statistics_woocommerce_purchases
 * rows) with the MailPoet attribution stored on the Woo order by OrderAttributionWriter
 * and records a structured result as order meta. The record feeds the rollout gate
 * ratified in STOMAIL-8135 (decision 8); it never changes reported revenue.
 */
class OrderAttributionReconciler {
  const RECONCILIATION_META_KEY = '_mailpoet_attribution_reconciliation';
  const RECORD_VERSION = 1;

  const OUTCOME_MATCH = 'match';
  const OUTCOME_DIVERGED = 'diverged';

  const CLASSIFICATION_INTENTIONAL = 'intentional';
  const CLASSIFICATION_UNEXPLAINED = 'unexplained';

  const REASON_MATCHED = 'matched';
  const REASON_NO_CLICK_CANDIDATE = 'no_click_candidate';
  // Intentional divergences per the STOMAIL-8135 contract.
  const REASON_LAST_CLICK_COLLAPSE = 'last_click_collapse';
  const REASON_FOREIGN_SOURCE_PRESERVED = 'foreign_source_preserved';
  // Unexplained unless proven otherwise.
  const REASON_CANONICAL_CLICK_MISMATCH = 'canonical_click_mismatch';
  const REASON_WOO_ATTRIBUTION_DISABLED = 'woo_attribution_disabled';
  const REASON_TRACKING_DISABLED = 'tracking_disabled';
  const REASON_WOO_ATTRIBUTION_MISSING = 'woo_attribution_missing';
  const REASON_LEGACY_ATTRIBUTION_MISSING = 'legacy_attribution_missing';

  const TRIGGER_STATUS_CHANGED = 'status_changed';
  const TRIGGER_REFUND = 'refund';

  /** @var WPFunctions */
  private $wp;

  /** @var Helper */
  private $wooHelper;

  /** @var TrackingConfig */
  private $trackingConfig;

  /** @var StatisticsWooCommercePurchasesRepository */
  private $purchasesRepository;

  public function __construct(
    WPFunctions $wp,
    Helper $wooHelper,
    TrackingConfig $trackingConfig,
    StatisticsWooCommercePurchasesRepository $purchasesRepository
  ) {
    $this->wp = $wp;
    $this->wooHelper = $wooHelper;
    $this->trackingConfig = $trackingConfig;
    $this->purchasesRepository = $purchasesRepository;
  }

  /**
   * Runs on woocommerce_order_status_changed/woocommerce_order_refunded after both
   * the legacy purchase tracker and the attribution writer (priority 10), so both
   * attribution results are available and the legacy rows are synced to the live
   * order status.
   *
   * @param int|WC_Order $order
   */
  public function reconcileForOrder($order, string $trigger = self::TRIGGER_STATUS_CHANGED): void {
    if (!$this->wooHelper->isWooCommerceActive()) {
      return;
    }
    if (!$order instanceof WC_Order) {
      $order = $this->wooHelper->wcGetOrder($order);
    }
    if (!$order instanceof WC_Order) {
      return;
    }
    if (!$this->isAfterWritesStartedBoundary($order)) {
      return;
    }

    $purchases = $this->getPurchasesWithClicks($order);
    $wooClickId = $this->getWooClickId($order);
    $trackingEnabled = $this->trackingConfig->isEmailTrackingEnabled();

    // Neither system attributed the order and the writer cannot run: there is
    // no migration signal to record, so avoid stamping every order with meta.
    if (!$purchases && $wooClickId === null && !$trackingEnabled) {
      return;
    }

    $record = $this->buildRecord($order, $purchases, $wooClickId, $trackingEnabled, $trigger);
    $order->update_meta_data(self::RECONCILIATION_META_KEY, (string)$this->wp->wpJsonEncode($record));
    $order->save_meta_data();
  }

  /**
   * Orders created before the writer first activated have no Woo-stored attribution
   * by design (STOMAIL-8135, decision 6) and must not be counted as divergences.
   */
  private function isAfterWritesStartedBoundary(WC_Order $order): bool {
    $boundary = $this->wp->getOption(OrderAttributionWriter::WRITES_STARTED_AT_OPTION);
    $createdAt = $order->get_date_created();
    if (!is_string($boundary) || $boundary === '' || is_null($createdAt)) {
      return false;
    }
    try {
      $boundaryDate = new \DateTimeImmutable($boundary, new \DateTimeZone('UTC'));
    } catch (\Exception $e) {
      return false;
    }
    return $createdAt->getTimestamp() >= $boundaryDate->getTimestamp();
  }

  /**
   * @return StatisticsWooCommercePurchaseEntity[]
   */
  private function getPurchasesWithClicks(WC_Order $order): array {
    $purchases = $this->purchasesRepository->findBy(['orderId' => $order->get_id()]);
    return array_values(array_filter($purchases, function(StatisticsWooCommercePurchaseEntity $purchase): bool {
      return !is_null($purchase->getClick());
    }));
  }

  private function getWooClickId(WC_Order $order): ?int {
    $value = $order->get_meta(OrderAttributionWriter::META_PREFIX . OrderAttributionFields::FIELD_CLICK_ID);
    if (!is_scalar($value) || (string)$value === '') {
      return null;
    }
    return (int)$value;
  }

  /**
   * @param StatisticsWooCommercePurchaseEntity[] $purchases
   * @return array<string, mixed>
   */
  private function buildRecord(
    WC_Order $order,
    array $purchases,
    ?int $wooClickId,
    bool $trackingEnabled,
    string $trigger
  ): array {
    $legacyClickIds = $this->getLegacyClickIds($purchases);
    $lastClickPurchase = $this->getLastClickPurchase($purchases);
    $lastClick = $lastClickPurchase ? $lastClickPurchase->getClick() : null;
    $legacyLastClickId = $lastClick ? (int)$lastClick->getId() : null;
    $foreignSourcePreserved = $this->isForeignSourcePreserved($order, $wooClickId);

    [$outcome, $reason, $classification] = $this->resolveOutcome(
      $legacyClickIds,
      $legacyLastClickId,
      $wooClickId,
      $trackingEnabled,
      $foreignSourcePreserved
    );

    $purchaseStates = $this->wooHelper->getPurchaseStates();
    $createdAt = $order->get_date_created();

    return [
      'version' => self::RECORD_VERSION,
      'trigger' => $trigger,
      'outcome' => $outcome,
      'reason' => $reason,
      'classification' => $classification,
      'legacy_click_ids' => $legacyClickIds,
      'legacy_min_click_id' => $legacyClickIds ? min($legacyClickIds) : null,
      'legacy_last_click_id' => $legacyLastClickId,
      'woo_click_id' => $wooClickId,
      'legacy_revenue' => $this->getLegacyDedupedRevenue($purchases, $purchaseStates),
      'legacy_revenue_per_newsletter' => $this->getLegacyPerNewsletterRevenue($purchases, $purchaseStates),
      'woo_revenue' => $this->getWooRevenue($order, $wooClickId, $purchaseStates),
      'currency' => $order->get_currency(),
      'order_status' => $order->get_status(),
      'foreign_source_preserved' => $foreignSourcePreserved,
      'order_created_at' => $createdAt ? gmdate('Y-m-d H:i:s', $createdAt->getTimestamp()) : null,
      'reconciled_at' => gmdate('Y-m-d H:i:s'),
    ];
  }

  /**
   * @param int[] $legacyClickIds
   * @return array{0: string, 1: string, 2: string|null}
   */
  private function resolveOutcome(
    array $legacyClickIds,
    ?int $legacyLastClickId,
    ?int $wooClickId,
    bool $trackingEnabled,
    bool $foreignSourcePreserved
  ): array {
    if (!$legacyClickIds && $wooClickId === null) {
      $reason = $trackingEnabled ? self::REASON_NO_CLICK_CANDIDATE : self::REASON_TRACKING_DISABLED;
      return [self::OUTCOME_MATCH, $reason, null];
    }

    if ($legacyClickIds && $wooClickId === null) {
      if (!$this->isWooAttributionAvailable()) {
        return [self::OUTCOME_DIVERGED, self::REASON_WOO_ATTRIBUTION_DISABLED, self::CLASSIFICATION_UNEXPLAINED];
      }
      if (!$trackingEnabled) {
        return [self::OUTCOME_DIVERGED, self::REASON_TRACKING_DISABLED, self::CLASSIFICATION_UNEXPLAINED];
      }
      return [self::OUTCOME_DIVERGED, self::REASON_WOO_ATTRIBUTION_MISSING, self::CLASSIFICATION_UNEXPLAINED];
    }

    if (!$legacyClickIds) {
      return [self::OUTCOME_DIVERGED, self::REASON_LEGACY_ATTRIBUTION_MISSING, self::CLASSIFICATION_UNEXPLAINED];
    }

    if ($wooClickId !== $legacyLastClickId) {
      return [self::OUTCOME_DIVERGED, self::REASON_CANONICAL_CLICK_MISMATCH, self::CLASSIFICATION_UNEXPLAINED];
    }

    if (count($legacyClickIds) > 1) {
      return [self::OUTCOME_DIVERGED, self::REASON_LAST_CLICK_COLLAPSE, self::CLASSIFICATION_INTENTIONAL];
    }

    if ($foreignSourcePreserved) {
      return [self::OUTCOME_DIVERGED, self::REASON_FOREIGN_SOURCE_PRESERVED, self::CLASSIFICATION_INTENTIONAL];
    }

    return [self::OUTCOME_MATCH, self::REASON_MATCHED, null];
  }

  /**
   * @param StatisticsWooCommercePurchaseEntity[] $purchases
   * @return int[]
   */
  private function getLegacyClickIds(array $purchases): array {
    $clickIds = [];
    foreach ($purchases as $purchase) {
      $click = $purchase->getClick();
      if ($click) {
        $clickIds[] = (int)$click->getId();
      }
    }
    sort($clickIds);
    return $clickIds;
  }

  /**
   * Mirrors OrderAttributionWriter::isMoreRecent so the legacy last-click candidate
   * is computed with the same tie-breaking as the writer's canonical click.
   *
   * @param StatisticsWooCommercePurchaseEntity[] $purchases
   */
  private function getLastClickPurchase(array $purchases): ?StatisticsWooCommercePurchaseEntity {
    $latest = null;
    foreach ($purchases as $purchase) {
      if (is_null($latest) || $this->isMoreRecent($purchase, $latest)) {
        $latest = $purchase;
      }
    }
    return $latest;
  }

  private function isMoreRecent(
    StatisticsWooCommercePurchaseEntity $purchase,
    StatisticsWooCommercePurchaseEntity $other
  ): bool {
    $click = $purchase->getClick();
    $otherClick = $other->getClick();
    if (is_null($click) || is_null($otherClick)) {
      return !is_null($click);
    }
    $clickUpdatedAt = $click->getUpdatedAt();
    $otherUpdatedAt = $otherClick->getUpdatedAt();
    if ($clickUpdatedAt->getTimestamp() === $otherUpdatedAt->getTimestamp()) {
      return (int)$click->getId() > (int)$otherClick->getId();
    }
    return $clickUpdatedAt > $otherUpdatedAt;
  }

  /**
   * Today's campaign-revenue dedup counts each order once via its MIN(click_id) row
   * (StatisticsWooCommercePurchasesRepository::getRevenuesByCampaigns).
   *
   * @param StatisticsWooCommercePurchaseEntity[] $purchases
   * @param string[] $purchaseStates
   */
  private function getLegacyDedupedRevenue(array $purchases, array $purchaseStates): float {
    $minClickPurchase = null;
    $minClickId = null;
    foreach ($purchases as $purchase) {
      $click = $purchase->getClick();
      if (is_null($click)) {
        continue;
      }
      if (is_null($minClickId) || (int)$click->getId() < $minClickId) {
        $minClickId = (int)$click->getId();
        $minClickPurchase = $purchase;
      }
    }
    if (is_null($minClickPurchase) || !in_array($minClickPurchase->getStatus(), $purchaseStates, true)) {
      return 0.0;
    }
    return $minClickPurchase->getOrderPriceTotal();
  }

  /**
   * Today's per-newsletter crediting counts the order total once per credited
   * newsletter row.
   *
   * @param StatisticsWooCommercePurchaseEntity[] $purchases
   * @param string[] $purchaseStates
   */
  private function getLegacyPerNewsletterRevenue(array $purchases, array $purchaseStates): float {
    $revenue = 0.0;
    foreach ($purchases as $purchase) {
      if (in_array($purchase->getStatus(), $purchaseStates, true)) {
        $revenue += $purchase->getOrderPriceTotal();
      }
    }
    return $revenue;
  }

  /**
   * The Woo-backed read model (STOMAIL-8138) credits the live refund-aware order
   * value once to the canonical click when the order is in a purchase state.
   *
   * @param string[] $purchaseStates
   */
  private function getWooRevenue(WC_Order $order, ?int $wooClickId, array $purchaseStates): float {
    if ($wooClickId === null || !in_array($order->get_status(), $purchaseStates, true)) {
      return 0.0;
    }
    return (float)$order->get_remaining_refund_amount();
  }

  private function isForeignSourcePreserved(WC_Order $order, ?int $wooClickId): bool {
    if ($wooClickId === null) {
      return false;
    }
    $sourceType = $order->get_meta(OrderAttributionWriter::META_PREFIX . 'source_type');
    $utmSource = $order->get_meta(OrderAttributionWriter::META_PREFIX . 'utm_source');
    $sourceType = is_scalar($sourceType) ? (string)$sourceType : '';
    $utmSource = is_scalar($utmSource) ? (string)$utmSource : '';
    $isOverwritable = in_array($sourceType, OrderAttributionWriter::OVERWRITABLE_SOURCE_TYPES, true)
      || $utmSource === 'mailpoet';
    return !$isOverwritable;
  }

  private function isWooAttributionAvailable(): bool {
    return class_exists(FeaturesUtil::class) && FeaturesUtil::feature_is_enabled('order_attribution');
  }
}
