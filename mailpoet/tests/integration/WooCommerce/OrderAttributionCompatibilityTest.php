<?php declare(strict_types = 1);

namespace MailPoet\WooCommerce;

use Codeception\Stub;
use DateTime;
use DateTimeImmutable;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterLinkEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\StatisticsClickEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Features\FeaturesController;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\TrackingConfig;
use MailPoet\Statistics\StatisticsClicksRepository;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Test\DataFactories\Features;
use MailPoet\Util\Cookies;
use MailPoet\WP\Functions as WPFunctions;
use WC_Order;

/**
 * STOMAIL-8144 compatibility matrix for the MailPoet <-> Woo attribution write + read paths.
 *
 * Each data set is one matrix cell: a write context crossed with a consent state and a
 * customer type. The test writes through that context, then reads back through the
 * Woo-backed read model, so every cell exercises the full write -> read round trip and
 * reports an independent pass/fail.
 *
 * Two matrix axes are environment-level in this repo and are covered by re-running this
 * test under each environment rather than by an in-process toggle:
 *   - HPOS on/off: flipped at container boot via ENABLE_HPOS / DISABLE_HPOS
 *     (tests_env/docker/codeception/docker-entrypoint.sh).
 *   - WooCommerce 10.7 vs trunk: run the suite against each version.
 * Express-payment buttons and headless checkouts reach the same server-side write path as
 * Block checkout (woocommerce_order_save_attribution_data) and are covered by equivalence.
 *
 * @phpstan-import-type OrderRow from OrderAttributionRevenueReader
 * @group woo
 */
class OrderAttributionCompatibilityTest extends \MailPoetTest {
  private const CONTEXT_CLASSIC_BLOCK_CHECKOUT = 'classic_block_checkout';
  private const CONTEXT_ORDER_STATUS_CHANGE = 'order_status_change';
  private const CONTEXT_ADMIN_ORDER = 'admin_order';
  private const CONTEXT_REST_API_ORDER = 'rest_api_order';
  private const CONTEXT_STORE_API_ORDER = 'store_api_order';

  /** @var SettingsController */
  private $settings;

  /** @var SubscriberEntity */
  private $subscriber;

  /** @var NewsletterEntity */
  private $newsletter;

  /** @var SendingQueueEntity */
  private $queue;

  /** @var NewsletterLinkEntity */
  private $link;

  public function _before() {
    parent::_before();
    delete_option(OrderAttributionWriter::WRITES_STARTED_AT_OPTION);
    unset($_COOKIE['mailpoet_revenue_tracking']);
    $this->settings = SettingsController::getInstance();
    $this->subscriber = $this->createSubscriber('matrix@example.com');
    $this->newsletter = $this->createNewsletter('Matrix Campaign');
    $this->queue = $this->createQueue($this->newsletter, $this->subscriber);
    $this->link = $this->createLink($this->newsletter, $this->queue);
  }

  /**
   * @dataProvider matrixProvider
   */
  public function testAttributionWriteReadMatrix(
    string $context,
    bool $consentGranted,
    bool $loggedIn,
    bool $expectWrite
  ): void {
    $this->settings->set('tracking.level', $consentGranted ? TrackingConfig::LEVEL_FULL : TrackingConfig::LEVEL_BASIC);
    $click = $this->createClick($this->link, $this->subscriber);
    $this->entityManager->flush();

    $order = $this->createOrder($this->subscriber->getEmail(), $loggedIn);
    $this->writeThroughContext($context, $order);

    $order = $this->reloadOrder($order);
    $clickIdKey = OrderAttributionFields::getMetaKey(OrderAttributionFields::FIELD_CLICK_ID);

    if (!$expectWrite) {
      verify($order->meta_exists($clickIdKey))->false();
      $this->assertArrayNotHasKey($order->get_id(), $this->readNewsletterOrderIds());
      return;
    }

    verify($order->get_meta($clickIdKey))->equals((string)$click->getId());
    verify($order->get_meta(OrderAttributionFields::getMetaKey(OrderAttributionFields::FIELD_NEWSLETTER_ID)))
      ->equals((string)$this->newsletter->getId());
    verify($order->get_meta(OrderAttributionFields::getMetaKey(OrderAttributionFields::FIELD_SUBSCRIBER_ID)))
      ->equals((string)$this->subscriber->getId());

    $row = $this->readNewsletterOrderRow($order->get_id());
    $this->assertNotNull($row);
    verify($row['newsletter_id'])->equals((int)$this->newsletter->getId());
    verify($row['subscriber_id'])->equals((int)$this->subscriber->getId());
  }

  /**
   * @return array<string, array{0: string, 1: bool, 2: bool, 3: bool}>
   */
  public function matrixProvider(): array {
    return [
      'classic/Block checkout, consent granted, logged-in' => [self::CONTEXT_CLASSIC_BLOCK_CHECKOUT, true, true, true],
      'classic/Block checkout, consent granted, guest' => [self::CONTEXT_CLASSIC_BLOCK_CHECKOUT, true, false, true],
      'classic/Block checkout, consent denied, logged-in' => [self::CONTEXT_CLASSIC_BLOCK_CHECKOUT, false, true, false],
      'order status change, consent granted, logged-in' => [self::CONTEXT_ORDER_STATUS_CHANGE, true, true, true],
      'admin order, consent granted, guest' => [self::CONTEXT_ADMIN_ORDER, true, false, true],
      'admin order, consent denied, guest' => [self::CONTEXT_ADMIN_ORDER, false, false, false],
      'REST-API order, consent granted, logged-in' => [self::CONTEXT_REST_API_ORDER, true, true, true],
      'REST-API order, consent granted, guest' => [self::CONTEXT_REST_API_ORDER, true, false, true],
      'Store-API order, consent granted, logged-in' => [self::CONTEXT_STORE_API_ORDER, true, true, false],
    ];
  }

  public function testWooBackedRevenueMatchesWooMailPoetSourceForArbitratedOrders(): void {
    $this->settings->set('tracking.level', TrackingConfig::LEVEL_FULL);
    (new Features())->withFeatureEnabled(FeaturesController::FEATURE_WOO_BACKED_REVENUE_REPORTING);
    $this->diContainer->get(FeaturesController::class)->resetCache();
    update_option(OrderAttributionWriter::WRITES_STARTED_AT_OPTION, '2000-01-01 00:00:00');

    $click = $this->createClick($this->link, $this->subscriber);
    $this->entityManager->flush();

    $newerEmailClickOrder = $this->createOrder($this->subscriber->getEmail(), false, 30);
    $this->completeOrderWithWooSource(
      $newerEmailClickOrder,
      'utm',
      'google',
      $this->formatWooSessionStartTime($click->getUpdatedAt(), -DAY_IN_SECONDS)
    );

    $olderEmailClickOrder = $this->createOrder($this->subscriber->getEmail(), false, 40);
    $this->completeOrderWithWooSource(
      $olderEmailClickOrder,
      'utm',
      'google',
      $this->formatWooSessionStartTime($click->getUpdatedAt(), DAY_IN_SECONDS)
    );

    $unknownSourceOrder = $this->createOrder($this->subscriber->getEmail(), false, 20);
    $this->completeOrderWithWooSource($unknownSourceOrder, 'unknown', '', null);

    $unparseableSessionOrder = $this->createOrder($this->subscriber->getEmail(), false, 50);
    $this->completeOrderWithWooSource($unparseableSessionOrder, 'utm', 'google', 'not-a-date');

    $reader = $this->diContainer->get(OrderAttributionRevenueReader::class);
    $revenues = $reader->getNewsletterRevenues(
      [(int)$this->newsletter->getId()],
      new DateTimeImmutable('@0'),
      new DateTimeImmutable('+1 day')
    );
    $mailPoetRevenue = $revenues[(int)$this->newsletter->getId()] ?? ['total' => 0.0, 'count' => 0];
    $wooMailPoetRevenue = $this->getWooMailPoetSourceRevenue([
      $newerEmailClickOrder,
      $olderEmailClickOrder,
      $unknownSourceOrder,
      $unparseableSessionOrder,
    ]);

    verify($mailPoetRevenue)->equals($wooMailPoetRevenue);
    verify($mailPoetRevenue['total'])->equals(50.0);
    verify($mailPoetRevenue['count'])->equals(2);

    $olderEmailClickOrder = $this->reloadOrder($olderEmailClickOrder);
    verify($olderEmailClickOrder->get_meta(OrderAttributionFields::getMetaKey(OrderAttributionFields::FIELD_CLICK_ID)))
      ->equals((string)$click->getId());
    verify($olderEmailClickOrder->get_meta(OrderAttributionFields::getMetaKey('utm_source')))->equals('google');
  }

  private function writeThroughContext(string $context, WC_Order $order): void {
    switch ($context) {
      case self::CONTEXT_CLASSIC_BLOCK_CHECKOUT:
        do_action('woocommerce_order_save_attribution_data', $order, $this->checkoutAttributionParams());
        return;
      case self::CONTEXT_ORDER_STATUS_CHANGE:
        $order->set_status('processing');
        $order->save();
        return;
      case self::CONTEXT_ADMIN_ORDER:
        $this->createWriterForRequestContext(true, false, false)->writeForNewOrder($order->get_id());
        return;
      case self::CONTEXT_REST_API_ORDER:
        $this->createWriterForRequestContext(false, true, false)->writeForNewOrder($order->get_id());
        return;
      case self::CONTEXT_STORE_API_ORDER:
        $this->createWriterForRequestContext(false, true, true)->writeForNewOrder($order->get_id());
        return;
    }
  }

  /**
   * Mirrors Woo's classic/Block checkout payload: Woo's priority-10 handler persists the
   * registered MailPoet fields as empty placeholders, which the MailPoet handler overwrites.
   *
   * @return array<string, string>
   */
  private function checkoutAttributionParams(): array {
    $params = array_fill_keys([
      'source_type', 'referrer', 'utm_campaign', 'utm_source', 'utm_medium', 'utm_content',
      'utm_id', 'utm_term', 'utm_source_platform', 'utm_creative_format', 'utm_marketing_tactic',
      'session_entry', 'session_start_time', 'session_pages', 'session_count', 'user_agent',
    ], '(none)');
    foreach (OrderAttributionFields::FIELD_NAMES as $fieldName) {
      $params[$fieldName] = '';
    }
    return $params;
  }

  /**
   * @return array<int, int> map of order id => order id, for membership assertions
   */
  private function readNewsletterOrderIds(): array {
    $ids = [];
    foreach ($this->readNewsletterOrderRows() as $row) {
      $ids[$row['order_id']] = $row['order_id'];
    }
    return $ids;
  }

  /**
   * @return OrderRow|null
   */
  private function readNewsletterOrderRow(int $orderId): ?array {
    foreach ($this->readNewsletterOrderRows() as $row) {
      if ($row['order_id'] === $orderId) {
        return $row;
      }
    }
    return null;
  }

  /**
   * @return OrderRow[]
   */
  private function readNewsletterOrderRows(): array {
    (new Features())->withFeatureEnabled(FeaturesController::FEATURE_WOO_BACKED_REVENUE_REPORTING);
    $this->diContainer->get(FeaturesController::class)->resetCache();
    update_option(OrderAttributionWriter::WRITES_STARTED_AT_OPTION, '2000-01-01 00:00:00');

    $reader = $this->diContainer->get(OrderAttributionRevenueReader::class);
    $rows = $reader->getNewsletterOrderRows(
      [(int)$this->newsletter->getId()],
      new DateTimeImmutable('@0'),
      new DateTimeImmutable('+1 day')
    );
    return $rows ?? [];
  }

  private function createOrder(string $billingEmail, bool $loggedIn, float $total = 15.0): WC_Order {
    $order = wc_create_order();
    $this->assertInstanceOf(WC_Order::class, $order);
    $order->set_billing_email($billingEmail);
    $order->set_total((string)$total);
    if ($loggedIn) {
      $order->set_customer_id($this->ensureWpUser($billingEmail));
    }
    $order->save();
    return $order;
  }

  private function completeOrderWithWooSource(
    WC_Order $order,
    string $sourceType,
    string $utmSource,
    ?string $sessionStartTime
  ): void {
    $order->update_meta_data(OrderAttributionFields::getMetaKey('source_type'), $sourceType);
    $order->update_meta_data(OrderAttributionFields::getMetaKey('utm_source'), $utmSource);
    if ($sessionStartTime !== null) {
      $order->update_meta_data(OrderAttributionFields::getMetaKey('session_start_time'), $sessionStartTime);
    }
    $order->save_meta_data();
    $order->set_status('completed');
    $order->save();
  }

  /**
   * @param WC_Order[] $orders
   * @return array{total: float, count: int}
   */
  private function getWooMailPoetSourceRevenue(array $orders): array {
    $revenue = ['total' => 0.0, 'count' => 0];
    foreach ($orders as $order) {
      $order = $this->reloadOrder($order);
      if ($order->get_meta(OrderAttributionFields::getMetaKey('utm_source')) !== 'mailpoet') {
        continue;
      }
      $revenue['total'] += (float)$order->get_remaining_refund_amount();
      $revenue['count']++;
    }
    return $revenue;
  }

  private function formatWooSessionStartTime(\DateTimeInterface $date, int $offsetSeconds = 0): string {
    return (new \DateTimeImmutable('@' . ($date->getTimestamp() + $offsetSeconds)))
      ->setTimezone(wp_timezone())
      ->format('Y-m-d H:i:s');
  }

  private function ensureWpUser(string $email): int {
    $existing = get_user_by('email', $email);
    if ($existing) {
      return (int)$existing->ID;
    }
    $userId = wp_insert_user([
      'user_login' => 'matrix_' . md5($email),
      'user_email' => $email,
      'user_pass' => 'password',
    ]);
    $this->assertIsInt($userId);
    return $userId;
  }

  private function reloadOrder(WC_Order $order): WC_Order {
    $reloaded = wc_get_order($order->get_id());
    $this->assertInstanceOf(WC_Order::class, $reloaded);
    return $reloaded;
  }

  private function createWriterForRequestContext(
    bool $isAdmin,
    bool $isRestApiRequest,
    bool $isStoreApiRequest
  ): OrderAttributionWriter {
    $wooHelper = Stub::make(Helper::class, [
      'isWooCommerceActive' => true,
      'wcGetOrder' => function($order) {
        return wc_get_order($order);
      },
      'isWooCommerceRestApiRequest' => $isRestApiRequest,
      'isWooCommerceStoreApiRequest' => $isStoreApiRequest,
    ], $this);

    return new OrderAttributionWriter(
      Stub::make(WPFunctions::class, ['isAdmin' => $isAdmin]),
      $wooHelper,
      $this->diContainer->get(TrackingConfig::class),
      $this->diContainer->get(StatisticsClicksRepository::class),
      $this->diContainer->get(SubscribersRepository::class),
      new Cookies()
    );
  }

  private function createNewsletter(string $subject): NewsletterEntity {
    $newsletter = new NewsletterEntity();
    $newsletter->setType(NewsletterEntity::TYPE_STANDARD);
    $newsletter->setSubject($subject);
    $this->entityManager->persist($newsletter);
    return $newsletter;
  }

  private function createQueue(NewsletterEntity $newsletter, SubscriberEntity $subscriber): SendingQueueEntity {
    $task = new ScheduledTaskEntity();
    $this->entityManager->persist($task);
    $queue = new SendingQueueEntity();
    $queue->setNewsletter($newsletter);
    $queue->setTask($task);
    $this->entityManager->persist($queue);
    $sendingTaskSubscriber = new ScheduledTaskSubscriberEntity($task, $subscriber, 1);
    $this->entityManager->persist($sendingTaskSubscriber);
    return $queue;
  }

  private function createSubscriber(string $email): SubscriberEntity {
    $subscriber = new SubscriberEntity();
    $subscriber->setEmail($email);
    $subscriber->setFirstName('First');
    $subscriber->setLastName('Last');
    $this->entityManager->persist($subscriber);
    return $subscriber;
  }

  private function createLink(NewsletterEntity $newsletter, SendingQueueEntity $queue): NewsletterLinkEntity {
    $link = new NewsletterLinkEntity($newsletter, $queue, 'url', 'hash');
    $this->entityManager->persist($link);
    return $link;
  }

  private function createClick(NewsletterLinkEntity $link, SubscriberEntity $subscriber): StatisticsClickEntity {
    $newsletter = $link->getNewsletter();
    $queue = $link->getQueue();
    $this->assertInstanceOf(NewsletterEntity::class, $newsletter);
    $this->assertInstanceOf(SendingQueueEntity::class, $queue);
    $click = new StatisticsClickEntity($newsletter, $queue, $subscriber, $link, 1);
    $this->entityManager->persist($click);
    $timestamp = new DateTime('-5 days');
    $click->setCreatedAt($timestamp);
    $click->setUpdatedAt($timestamp);
    return $click;
  }

  public function _after() {
    parent::_after();
    delete_option(OrderAttributionWriter::WRITES_STARTED_AT_OPTION);
    $orders = (array)wc_get_orders(['limit' => -1]);
    foreach ($orders as $order) {
      $order->delete(true);
    }
  }
}
