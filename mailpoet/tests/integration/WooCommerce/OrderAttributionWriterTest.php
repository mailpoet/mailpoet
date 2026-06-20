<?php declare(strict_types = 1);

namespace MailPoet\WooCommerce;

use Codeception\Stub;
use DateTime;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterLinkEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\StatisticsClickEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\TrackingConfig;
use MailPoet\Statistics\StatisticsClicksRepository;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Util\Cookies;
use MailPoet\WP\Functions as WPFunctions;
use WC_Order;

/**
 * @group woo
 */
class OrderAttributionWriterTest extends \MailPoetTest {
  private const WOO_ATTRIBUTION_FEATURE_OPTION = 'woocommerce_feature_order_attribution_enabled';

  /** @var SubscriberEntity */
  private $subscriber;

  /** @var NewsletterEntity */
  private $newsletter;

  /** @var SendingQueueEntity */
  private $queue;

  /** @var NewsletterLinkEntity */
  private $link;

  /** @var OrderAttributionWriter */
  private $writer;

  /** @var SettingsController */
  private $settings;

  public function _before() {
    parent::_before();
    delete_option(OrderAttributionWriter::WRITES_STARTED_AT_OPTION);
    unset($_COOKIE['mailpoet_revenue_tracking']);
    $this->settings = SettingsController::getInstance();
    $this->settings->set('tracking.level', TrackingConfig::LEVEL_FULL);
    $this->subscriber = $this->createSubscriber('attribution@example.com');
    $this->newsletter = $this->createNewsletter('First Campaign');
    $this->queue = $this->createQueue($this->newsletter, $this->subscriber);
    $this->link = $this->createLink($this->newsletter, $this->queue);
    $this->writer = $this->diContainer->get(OrderAttributionWriter::class);
  }

  public function testItWritesStandardSourceForEligibleOrder(): void {
    $this->createClick($this->link, $this->subscriber);
    $this->entityManager->flush();
    $order = $this->createOrder($this->subscriber->getEmail());

    $this->writer->writeForOrder($order->get_id());

    $order = $this->reloadOrder($order);
    verify($order->get_meta(OrderAttributionFields::getMetaKey('source_type')))->equals('utm');
    verify($order->get_meta(OrderAttributionFields::getMetaKey('utm_source')))->equals('mailpoet');
    verify($order->get_meta(OrderAttributionFields::getMetaKey('utm_medium')))->equals('email');
    verify($order->get_meta(OrderAttributionFields::getMetaKey('utm_source_platform')))->equals('mailpoet');
    verify($order->get_meta(OrderAttributionFields::getMetaKey('utm_campaign')))->equals('First Campaign');
    verify(get_option(OrderAttributionWriter::WRITES_STARTED_AT_OPTION))->notEmpty();
  }

  public function testLastClickWinsAcrossEmailAndCookieCandidates(): void {
    $this->createClick($this->link, $this->subscriber, 5);

    $cookieSubscriber = $this->createSubscriber('cookie@example.com');
    $cookieNewsletter = $this->createNewsletter('Cookie Campaign');
    $cookieQueue = $this->createQueue($cookieNewsletter, $cookieSubscriber);
    $cookieLink = $this->createLink($cookieNewsletter, $cookieQueue);
    $cookieClick = $this->createClick($cookieLink, $cookieSubscriber, 1);
    $this->entityManager->flush();

    $_COOKIE['mailpoet_revenue_tracking'] = (string)json_encode([
      'statistics_clicks' => $cookieClick->getId(),
      'created_at' => time(),
    ]);

    $order = $this->createOrder($this->subscriber->getEmail());
    $this->writer->writeForOrder($order->get_id());

    // utm_campaign carries the winning click's newsletter subject, so it uniquely
    // identifies which candidate won last-click arbitration.
    $order = $this->reloadOrder($order);
    verify($order->get_meta(OrderAttributionFields::getMetaKey('utm_source')))->equals('mailpoet');
    verify($order->get_meta(OrderAttributionFields::getMetaKey('utm_campaign')))->equals('Cookie Campaign');
  }

  public function testLastClickWinsAcrossNewslettersForTheSameSubscriber(): void {
    $this->createClick($this->link, $this->subscriber, 3);

    $newerNewsletter = $this->createNewsletter('Newer Campaign');
    $newerQueue = $this->createQueue($newerNewsletter, $this->subscriber);
    $newerLink = $this->createLink($newerNewsletter, $newerQueue);
    $this->createClick($newerLink, $this->subscriber, 1);
    $this->entityManager->flush();

    $order = $this->createOrder($this->subscriber->getEmail());
    $this->writer->writeForOrder($order->get_id());

    $order = $this->reloadOrder($order);
    verify($order->get_meta(OrderAttributionFields::getMetaKey('utm_source')))->equals('mailpoet');
    verify($order->get_meta(OrderAttributionFields::getMetaKey('utm_campaign')))->equals('Newer Campaign');
  }

  public function testItPreservesExistingNonMailPoetSource(): void {
    $this->createClick($this->link, $this->subscriber);
    $this->entityManager->flush();

    $order = $this->createOrder($this->subscriber->getEmail());
    $order->update_meta_data(OrderAttributionFields::getMetaKey('source_type'), 'referral');
    $order->update_meta_data(OrderAttributionFields::getMetaKey('utm_source'), 'google');
    $order->save_meta_data();

    $this->writer->writeForOrder($order->get_id());

    $order = $this->reloadOrder($order);
    verify($order->get_meta(OrderAttributionFields::getMetaKey('source_type')))->equals('referral');
    verify($order->get_meta(OrderAttributionFields::getMetaKey('utm_source')))->equals('google');
    verify($order->meta_exists(OrderAttributionFields::getMetaKey('utm_medium')))->false();
  }

  public function testItOverwritesExistingSourceWhenMailPoetClickIsNewer(): void {
    $click = $this->createClick($this->link, $this->subscriber);
    $this->entityManager->flush();

    $order = $this->createOrder($this->subscriber->getEmail());
    $this->setWooSourceMeta(
      $order,
      'utm',
      'google',
      $this->formatWooSessionStartTime($click->getUpdatedAt(), -DAY_IN_SECONDS)
    );

    $this->writer->writeForOrder($order->get_id());

    $order = $this->reloadOrder($order);
    verify($order->get_meta(OrderAttributionFields::getMetaKey('source_type')))->equals('utm');
    verify($order->get_meta(OrderAttributionFields::getMetaKey('utm_source')))->equals('mailpoet');
    verify($order->get_meta(OrderAttributionFields::getMetaKey('utm_medium')))->equals('email');
  }

  public function testItPreservesExistingSourceWhenMailPoetClickIsOlder(): void {
    $click = $this->createClick($this->link, $this->subscriber);
    $this->entityManager->flush();

    $order = $this->createOrder($this->subscriber->getEmail());
    $this->setWooSourceMeta(
      $order,
      'utm',
      'google',
      $this->formatWooSessionStartTime($click->getUpdatedAt(), DAY_IN_SECONDS)
    );

    $this->writer->writeForOrder($order->get_id());

    $order = $this->reloadOrder($order);
    verify($order->get_meta(OrderAttributionFields::getMetaKey('source_type')))->equals('utm');
    verify($order->get_meta(OrderAttributionFields::getMetaKey('utm_source')))->equals('google');
    verify($order->meta_exists(OrderAttributionFields::getMetaKey('utm_medium')))->false();
  }

  public function testItOverwritesExistingSourceWhenMailPoetClickMatchesWooSessionTime(): void {
    $click = $this->createClick($this->link, $this->subscriber);
    $this->entityManager->flush();

    $order = $this->createOrder($this->subscriber->getEmail());
    $this->setWooSourceMeta(
      $order,
      'utm',
      'google',
      $this->formatWooSessionStartTime($click->getUpdatedAt())
    );

    $this->writer->writeForOrder($order->get_id());

    $order = $this->reloadOrder($order);
    verify($order->get_meta(OrderAttributionFields::getMetaKey('source_type')))->equals('utm');
    verify($order->get_meta(OrderAttributionFields::getMetaKey('utm_source')))->equals('mailpoet');
    verify($order->get_meta(OrderAttributionFields::getMetaKey('utm_medium')))->equals('email');
  }

  public function testItOverwritesEmptyDirectAndUnknownSourcesWithoutSessionStartTime(): void {
    $this->createClick($this->link, $this->subscriber);
    $this->entityManager->flush();

    foreach (OrderAttributionWriter::OVERWRITABLE_SOURCE_TYPES as $sourceType) {
      $order = $this->createOrder($this->subscriber->getEmail());
      $this->setWooSourceMeta($order, $sourceType, '(direct)');

      $this->writer->writeForOrder($order->get_id());

      $order = $this->reloadOrder($order);
      verify($order->get_meta(OrderAttributionFields::getMetaKey('source_type')))->equals('utm');
      verify($order->get_meta(OrderAttributionFields::getMetaKey('utm_source')))->equals('mailpoet');
    }
  }

  public function testItPreservesExistingSourceWhenSessionStartTimeIsUnparseable(): void {
    $this->createClick($this->link, $this->subscriber);
    $this->entityManager->flush();

    $order = $this->createOrder($this->subscriber->getEmail());
    $this->setWooSourceMeta($order, 'utm', 'google', 'not-a-date');

    $this->writer->writeForOrder($order->get_id());

    $order = $this->reloadOrder($order);
    verify($order->get_meta(OrderAttributionFields::getMetaKey('source_type')))->equals('utm');
    verify($order->get_meta(OrderAttributionFields::getMetaKey('utm_source')))->equals('google');
    verify($order->meta_exists(OrderAttributionFields::getMetaKey('utm_medium')))->false();
  }

  public function testItIsIdempotent(): void {
    $this->createClick($this->link, $this->subscriber);
    $this->entityManager->flush();
    $order = $this->createOrder($this->subscriber->getEmail());

    $this->writer->writeForOrder($order->get_id());
    $writesStartedAt = get_option(OrderAttributionWriter::WRITES_STARTED_AT_OPTION);
    $this->writer->writeForOrder($order->get_id());

    $order = $this->reloadOrder($order);
    verify($this->getMetaValues($order, OrderAttributionFields::getMetaKey('utm_source')))->equals(['mailpoet']);
    verify(get_option(OrderAttributionWriter::WRITES_STARTED_AT_OPTION))->equals($writesStartedAt);
  }

  public function testItMarksWritesStartedOnActivationAndNeverMovesIt(): void {
    verify(get_option(OrderAttributionWriter::WRITES_STARTED_AT_OPTION))->false();

    $this->writer->markWritesStartedIfActive();

    $writesStartedAt = get_option(OrderAttributionWriter::WRITES_STARTED_AT_OPTION);
    verify($writesStartedAt)->notEmpty();

    $this->writer->markWritesStartedIfActive();
    verify(get_option(OrderAttributionWriter::WRITES_STARTED_AT_OPTION))->equals($writesStartedAt);
  }

  public function testItDoesNotMarkWritesStartedWhenTrackingIsDisabled(): void {
    $this->settings->set('tracking.level', TrackingConfig::LEVEL_BASIC);

    $this->writer->markWritesStartedIfActive();

    verify(get_option(OrderAttributionWriter::WRITES_STARTED_AT_OPTION))->false();
  }

  public function testItWritesNothingWhenTrackingIsDisabled(): void {
    $this->settings->set('tracking.level', TrackingConfig::LEVEL_BASIC);
    $this->createClick($this->link, $this->subscriber);
    $this->entityManager->flush();
    $order = $this->createOrder($this->subscriber->getEmail());

    $this->writer->writeForOrder($order->get_id());

    $order = $this->reloadOrder($order);
    verify($order->meta_exists(OrderAttributionFields::getMetaKey('utm_source')))->false();
    verify(get_option(OrderAttributionWriter::WRITES_STARTED_AT_OPTION))->false();
  }

  public function testItWritesNothingWhenWooAttributionIsDisabled(): void {
    $this->createClick($this->link, $this->subscriber);
    $this->entityManager->flush();
    $order = $this->createOrder($this->subscriber->getEmail());

    update_option(self::WOO_ATTRIBUTION_FEATURE_OPTION, 'no');
    try {
      $this->writer->writeForOrder($order->get_id());
    } finally {
      update_option(self::WOO_ATTRIBUTION_FEATURE_OPTION, 'yes');
    }

    $order = $this->reloadOrder($order);
    verify($order->meta_exists(OrderAttributionFields::getMetaKey('utm_source')))->false();
    verify(get_option(OrderAttributionWriter::WRITES_STARTED_AT_OPTION))->false();
  }

  public function testItWritesNothingWhenNoClickResolves(): void {
    $order = $this->createOrder('no-clicks@example.com');

    $this->writer->writeForOrder($order->get_id());

    $order = $this->reloadOrder($order);
    verify($order->meta_exists(OrderAttributionFields::getMetaKey('utm_source')))->false();
    verify($order->meta_exists(OrderAttributionFields::getMetaKey('utm_medium')))->false();
  }

  public function testItWritesViaWooAttributionDataAction(): void {
    $this->createClick($this->link, $this->subscriber);
    $this->entityManager->flush();
    $order = $this->createOrder($this->subscriber->getEmail());

    do_action('woocommerce_order_save_attribution_data', $order, []);

    $order = $this->reloadOrder($order);
    verify($order->get_meta(OrderAttributionFields::getMetaKey('utm_source')))->equals('mailpoet');
    verify($this->getMetaValues($order, OrderAttributionFields::getMetaKey('utm_source')))->equals(['mailpoet']);
  }

  public function testItWritesWhenOrderStatusChanges(): void {
    $this->createClick($this->link, $this->subscriber);
    $this->entityManager->flush();
    $order = $this->createOrder($this->subscriber->getEmail());

    $order->set_status('processing');
    $order->save();

    $order = $this->reloadOrder($order);
    verify($order->get_meta(OrderAttributionFields::getMetaKey('utm_source')))->equals('mailpoet');
  }

  public function testNewOrderPathRunsOnlyInAdminOrRestContext(): void {
    $this->createClick($this->link, $this->subscriber);
    $this->entityManager->flush();
    $order = $this->createOrder($this->subscriber->getEmail());

    // neither admin nor REST here, so the new-order path must not write
    $this->writer->writeForNewOrder($order->get_id());
    $reloaded = $this->reloadOrder($order);
    verify($reloaded->meta_exists(OrderAttributionFields::getMetaKey('utm_source')))->false();

    $storeApiOrder = $this->createOrder($this->subscriber->getEmail());
    $storeApiWriter = $this->createWriterForRequestContext(false, true, true);
    $storeApiWriter->writeForNewOrder($storeApiOrder->get_id());
    $reloadedStoreApiOrder = $this->reloadOrder($storeApiOrder);
    verify($reloadedStoreApiOrder->meta_exists(OrderAttributionFields::getMetaKey('utm_source')))->false();

    $restApiOrder = $this->createOrder($this->subscriber->getEmail());
    $restApiWriter = $this->createWriterForRequestContext(false, true, false);
    $restApiWriter->writeForNewOrder($restApiOrder->get_id());
    $reloadedRestApiOrder = $this->reloadOrder($restApiOrder);
    verify($reloadedRestApiOrder->get_meta(OrderAttributionFields::getMetaKey('utm_source')))->equals('mailpoet');

    $adminWriter = $this->createWriterForRequestContext(true, false, false);
    $adminWriter->writeForNewOrder($order->get_id());
    $reloaded = $this->reloadOrder($order);
    verify($reloaded->get_meta(OrderAttributionFields::getMetaKey('utm_source')))->equals('mailpoet');
  }

  private function createOrder(string $billingEmail): WC_Order {
    $order = wc_create_order();
    $this->assertInstanceOf(WC_Order::class, $order);
    $order->set_billing_email($billingEmail);
    $order->set_total('15');
    $order->save();
    return $order;
  }

  private function reloadOrder(WC_Order $order): WC_Order {
    $reloaded = wc_get_order($order->get_id());
    $this->assertInstanceOf(WC_Order::class, $reloaded);
    return $reloaded;
  }

  private function setWooSourceMeta(
    WC_Order $order,
    string $sourceType,
    string $utmSource,
    ?string $sessionStartTime = null
  ): void {
    $order->update_meta_data(OrderAttributionFields::getMetaKey('source_type'), $sourceType);
    $order->update_meta_data(OrderAttributionFields::getMetaKey('utm_source'), $utmSource);
    if (!is_null($sessionStartTime)) {
      $order->update_meta_data(OrderAttributionFields::getMetaKey('session_start_time'), $sessionStartTime);
    }
    $order->save_meta_data();
  }

  private function formatWooSessionStartTime(\DateTimeInterface $date, int $offsetSeconds = 0): string {
    return (new \DateTimeImmutable('@' . ($date->getTimestamp() + $offsetSeconds)))
      ->setTimezone(wp_timezone())
      ->format('Y-m-d H:i:s');
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

  /**
   * @return string[]
   */
  private function getMetaValues(WC_Order $order, string $metaKey): array {
    $values = [];
    foreach ($order->get_meta_data() as $meta) {
      $data = $meta->get_data();
      if ($data['key'] === $metaKey) {
        $values[] = $data['value'];
      }
    }
    return $values;
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

  private function createClick(NewsletterLinkEntity $link, SubscriberEntity $subscriber, int $createdDaysAgo = 5): StatisticsClickEntity {
    $newsletter = $link->getNewsletter();
    $queue = $link->getQueue();
    $this->assertInstanceOf(NewsletterEntity::class, $newsletter);
    $this->assertInstanceOf(SendingQueueEntity::class, $queue);
    $click = new StatisticsClickEntity($newsletter, $queue, $subscriber, $link, 1);
    $this->entityManager->persist($click);
    $timestamp = new DateTime("-$createdDaysAgo days");
    $click->setCreatedAt($timestamp);
    $click->setUpdatedAt($timestamp);
    return $click;
  }
}
