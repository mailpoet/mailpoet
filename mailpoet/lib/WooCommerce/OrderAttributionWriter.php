<?php declare(strict_types = 1);

namespace MailPoet\WooCommerce;

use Automattic\WooCommerce\Utilities\FeaturesUtil;
use MailPoet\Entities\StatisticsClickEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Settings\TrackingConfig;
use MailPoet\Statistics\StatisticsClicksRepository;
use MailPoet\Statistics\Track\Clicks;
use MailPoet\Statistics\Track\WooCommercePurchases;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Util\Cookies;
use MailPoet\WP\Functions as WPFunctions;
use WC_Order;

class OrderAttributionWriter {
  const WRITES_STARTED_AT_OPTION = 'mailpoet_woo_attribution_writes_started_at';

  // The meta key names are pinned by the migration contract (STOMAIL-8135), so Woo's
  // filterable wc_order_attribution_tracking_field_prefix is intentionally not applied.
  const META_PREFIX = '_wc_order_attribution_';

  // 'typein' is Woo's source type for direct traffic. A clear non-MailPoet source
  // (organic, referral, non-MailPoet utm, admin, mobile_app) is never overwritten.
  const OVERWRITABLE_SOURCE_TYPES = ['', 'typein', 'unknown'];

  /** @var WPFunctions */
  private $wp;

  /** @var Helper */
  private $wooHelper;

  /** @var TrackingConfig */
  private $trackingConfig;

  /** @var StatisticsClicksRepository */
  private $statisticsClicksRepository;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var Cookies */
  private $cookies;

  public function __construct(
    WPFunctions $wp,
    Helper $wooHelper,
    TrackingConfig $trackingConfig,
    StatisticsClicksRepository $statisticsClicksRepository,
    SubscribersRepository $subscribersRepository,
    Cookies $cookies
  ) {
    $this->wp = $wp;
    $this->wooHelper = $wooHelper;
    $this->trackingConfig = $trackingConfig;
    $this->statisticsClicksRepository = $statisticsClicksRepository;
    $this->subscribersRepository = $subscribersRepository;
    $this->cookies = $cookies;
  }

  /**
   * @param int|WC_Order $order
   */
  public function writeForOrder($order): void {
    if (!$this->isWritePathActive()) {
      return;
    }
    if (!$order instanceof WC_Order) {
      $order = $this->wooHelper->wcGetOrder($order);
    }
    if (!$order instanceof WC_Order) {
      return;
    }
    $this->markWritesStarted();

    $click = $this->resolveCanonicalClick($order);
    if (!$click) {
      $this->removeEmptyPlaceholders($order);
      $order->save_meta_data();
      return;
    }
    $this->writeMailPoetFields($order, $click);
    $this->writeStandardSourceFields($order, $click);
    $order->save_meta_data();
  }

  /**
   * woocommerce_new_order also fires during storefront checkout, before WooCommerce
   * captures its attribution data. Writing MailPoet meta at that point would make
   * Woo's has_attribution() check skip its own capture, so this path handles only
   * admin and (non-Store-API) REST requests; checkout orders are covered by the
   * woocommerce_order_save_attribution_data and order-status-changed paths.
   *
   * @param int|WC_Order $order
   */
  public function writeForNewOrder($order): void {
    if (!$this->isAdminOrRestApiRequest()) {
      return;
    }
    $this->writeForOrder($order);
  }

  private function isWritePathActive(): bool {
    return $this->wooHelper->isWooCommerceActive()
      && $this->isWooAttributionAvailable()
      && $this->trackingConfig->isEmailTrackingEnabled();
  }

  private function isWooAttributionAvailable(): bool {
    return class_exists(FeaturesUtil::class) && FeaturesUtil::feature_is_enabled('order_attribution');
  }

  private function isAdminOrRestApiRequest(): bool {
    if ($this->wp->isAdmin()) {
      return true;
    }
    $wc = $this->wooHelper->WC();
    if (!$wc instanceof \WooCommerce) {
      return false;
    }
    return $wc->is_rest_api_request() && !$wc->is_store_api_request();
  }

  /**
   * The historical read boundary defined by the migration contract (STOMAIL-8135):
   * persisted once when the write path first activates and never moved.
   */
  private function markWritesStarted(): void {
    if ($this->wp->getOption(self::WRITES_STARTED_AT_OPTION)) {
      return;
    }
    $this->wp->addOption(self::WRITES_STARTED_AT_OPTION, gmdate('Y-m-d H:i:s'));
  }

  /**
   * Last click wins (STOMAIL-8135): the most recent eligible click before order
   * creation, across billing-email-matched and cookie-matched candidates. The
   * candidate set mirrors WooCommercePurchases::trackPurchase; the legacy engine
   * is intentionally left untouched while both run in parallel for reconciliation.
   */
  private function resolveCanonicalClick(WC_Order $order): ?StatisticsClickEntity {
    $to = $order->get_date_created();
    if (is_null($to)) {
      return null;
    }
    $from = clone $to;
    $from->modify(-WooCommercePurchases::USE_CLICKS_SINCE_DAYS_AGO . ' days');

    $candidates = $this->getClicks($order->get_billing_email(), $from, $to);
    if ($this->trackingConfig->isCookieTrackingEnabled()) {
      $cookieEmail = $this->getSubscriberEmailFromCookie();
      if ($cookieEmail && $cookieEmail !== $order->get_billing_email()) {
        $candidates = array_merge($candidates, $this->getClicks($cookieEmail, $from, $to));
      }
    }

    if (!$candidates) {
      return null;
    }
    $latest = array_shift($candidates);
    foreach ($candidates as $click) {
      if ($this->isMoreRecent($click, $latest)) {
        $latest = $click;
      }
    }
    return $latest;
  }

  private function isMoreRecent(StatisticsClickEntity $click, StatisticsClickEntity $other): bool {
    $clickUpdatedAt = $click->getUpdatedAt();
    $otherUpdatedAt = $other->getUpdatedAt();
    if ($clickUpdatedAt->getTimestamp() === $otherUpdatedAt->getTimestamp()) {
      return (int)$click->getId() > (int)$other->getId();
    }
    return $clickUpdatedAt > $otherUpdatedAt;
  }

  /**
   * @return StatisticsClickEntity[]
   */
  private function getClicks(?string $email, \DateTimeInterface $from, \DateTimeInterface $to): array {
    if (!$email) {
      return [];
    }
    $subscriber = $this->subscribersRepository->findOneBy(['email' => $email]);
    if (!$subscriber instanceof SubscriberEntity) {
      return [];
    }
    return $this->statisticsClicksRepository->findLatestPerNewsletterBySubscriber($subscriber, $from, $to);
  }

  private function getSubscriberEmailFromCookie(): ?string {
    $cookieData = $this->cookies->get(Clicks::REVENUE_TRACKING_COOKIE_NAME);
    if (!$cookieData || !isset($cookieData['statistics_clicks'])) {
      return null;
    }
    try {
      $click = $this->statisticsClicksRepository->findOneById($cookieData['statistics_clicks']);
    } catch (\Exception $e) {
      return null;
    }
    if (!$click instanceof StatisticsClickEntity) {
      return null;
    }
    $subscriber = $click->getSubscriber();
    return $subscriber instanceof SubscriberEntity ? $subscriber->getEmail() : null;
  }

  private function writeMailPoetFields(WC_Order $order, StatisticsClickEntity $click): void {
    $newsletter = $click->getNewsletter();
    $queue = $click->getQueue();
    $subscriber = $click->getSubscriber();
    $values = [
      OrderAttributionFields::FIELD_CLICK_ID => (string)$click->getId(),
      OrderAttributionFields::FIELD_NEWSLETTER_ID => $newsletter ? (string)$newsletter->getId() : '',
      OrderAttributionFields::FIELD_QUEUE_ID => $queue ? (string)$queue->getId() : '',
      OrderAttributionFields::FIELD_SUBSCRIBER_ID => $subscriber ? (string)$subscriber->getId() : '',
    ];
    foreach ($values as $fieldName => $value) {
      if ($value === '') {
        $this->removeEmptyPlaceholder($order, $fieldName);
        continue;
      }
      $order->update_meta_data(self::META_PREFIX . $fieldName, $value);
    }
  }

  private function writeStandardSourceFields(WC_Order $order, StatisticsClickEntity $click): void {
    $sourceType = $this->getMetaString($order, self::META_PREFIX . 'source_type');
    $utmSource = $this->getMetaString($order, self::META_PREFIX . 'utm_source');
    $isOverwritable = in_array($sourceType, self::OVERWRITABLE_SOURCE_TYPES, true) || $utmSource === 'mailpoet';
    if (!$isOverwritable) {
      return;
    }
    $values = [
      'source_type' => 'utm',
      'utm_source' => 'mailpoet',
      'utm_medium' => 'email',
      'utm_source_platform' => 'mailpoet',
    ];
    $newsletter = $click->getNewsletter();
    if ($newsletter) {
      $subject = (string)$newsletter->getSubject();
      $values['utm_campaign'] = $subject !== '' ? $subject : 'newsletter-' . $newsletter->getId();
    }
    foreach ($values as $fieldName => $value) {
      $order->update_meta_data(self::META_PREFIX . $fieldName, $this->wp->sanitizeTextField($value));
    }
  }

  /**
   * WooCommerce persists the registered MailPoet fields as empty strings on checkout
   * orders (the placeholders from STOMAIL-7487). When no attribution is resolved,
   * the empty placeholders are removed so "no eligible click" leaves no MailPoet
   * meta behind. Non-empty values are never removed.
   */
  private function removeEmptyPlaceholders(WC_Order $order): void {
    foreach (OrderAttributionFields::FIELD_NAMES as $fieldName) {
      $this->removeEmptyPlaceholder($order, $fieldName);
    }
  }

  private function removeEmptyPlaceholder(WC_Order $order, string $fieldName): void {
    $metaKey = self::META_PREFIX . $fieldName;
    if ($order->meta_exists($metaKey) && $this->getMetaString($order, $metaKey) === '') {
      $order->delete_meta_data($metaKey);
    }
  }

  private function getMetaString(WC_Order $order, string $metaKey): string {
    $value = $order->get_meta($metaKey);
    return is_scalar($value) ? (string)$value : '';
  }
}
