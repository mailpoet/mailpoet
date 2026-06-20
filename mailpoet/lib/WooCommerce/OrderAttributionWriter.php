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
      return;
    }
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
    return $this->wooHelper->isWooCommerceRestApiRequest() && !$this->wooHelper->isWooCommerceStoreApiRequest();
  }

  /**
   * Persists the historical read boundary defined by the migration contract
   * (STOMAIL-8135): set once when the write path first activates and never
   * moved. Runs on init so the boundary predates every post-activation order;
   * if it were first persisted inside writeForOrder, the triggering order's
   * date_created would fall before the boundary and the reconciler would skip
   * the first post-activation orders.
   */
  public function markWritesStartedIfActive(): void {
    if (!$this->isWritePathActive()) {
      return;
    }
    $this->markWritesStarted();
  }

  // Backstop for the order-save paths; normally already persisted on init.
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

  private function writeStandardSourceFields(WC_Order $order, StatisticsClickEntity $click): void {
    $sourceType = $this->getMetaString($order, OrderAttributionFields::getMetaKey('source_type'));
    $utmSource = $this->getMetaString($order, OrderAttributionFields::getMetaKey('utm_source'));
    $sessionStartTime = $this->getMetaString($order, OrderAttributionFields::getMetaKey('session_start_time'));
    if (!self::shouldWriteStandardSourceFields($sourceType, $utmSource, $sessionStartTime, $click->getUpdatedAt(), $this->wp->wpTimezone())) {
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
      $order->update_meta_data(OrderAttributionFields::getMetaKey($fieldName), $this->wp->sanitizeTextField($value));
    }
  }

  public static function shouldWriteStandardSourceFields(
    string $sourceType,
    string $utmSource,
    string $sessionStartTime,
    \DateTimeInterface $clickUpdatedAt,
    \DateTimeZone $wooSessionTimeZone
  ): bool {
    if (in_array($sourceType, self::OVERWRITABLE_SOURCE_TYPES, true) || $utmSource === 'mailpoet') {
      return true;
    }
    $wooSessionStart = self::parseWooSessionStartTime($sessionStartTime, $wooSessionTimeZone);
    return $wooSessionStart && $clickUpdatedAt->getTimestamp() >= $wooSessionStart->getTimestamp();
  }

  /**
   * Woo stores session_start_time as sourcebuster's `current_add.fd`, a wall-clock
   * string with no timezone. It is the visitor's browser-local time, which the server
   * cannot recover exactly, so we interpret it in the site timezone as the best
   * single-region approximation. Multi-region skew is accepted within the documented
   * last-click tolerance (STOMAIL-8186).
   */
  private static function parseWooSessionStartTime(string $sessionStartTime, \DateTimeZone $timeZone): ?\DateTimeImmutable {
    $sessionStartTime = trim($sessionStartTime);
    if ($sessionStartTime === '') {
      return null;
    }
    $date = \DateTimeImmutable::createFromFormat(
      '!Y-m-d H:i:s',
      $sessionStartTime,
      $timeZone
    );
    $errors = \DateTimeImmutable::getLastErrors();
    if (!$date || ($errors !== false && ((int)$errors['warning_count'] > 0 || (int)$errors['error_count'] > 0))) {
      return null;
    }
    return $date;
  }

  private function getMetaString(WC_Order $order, string $metaKey): string {
    $value = $order->get_meta($metaKey);
    return is_scalar($value) ? (string)$value : '';
  }
}
