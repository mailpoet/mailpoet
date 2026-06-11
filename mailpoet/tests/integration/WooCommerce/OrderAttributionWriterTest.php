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

  public function testItWritesAttributionMetaForEligibleOrder(): void {
    $click = $this->createClick($this->link, $this->subscriber);
    $this->entityManager->flush();
    $order = $this->createOrder($this->subscriber->getEmail());

    $this->writer->writeForOrder($order->get_id());

    $order = $this->reloadOrder($order);
    verify($order->get_meta('_wc_order_attribution_mailpoet_click_id'))->equals((string)$click->getId());
    verify($order->get_meta('_wc_order_attribution_mailpoet_newsletter_id'))->equals((string)$this->newsletter->getId());
    verify($order->get_meta('_wc_order_attribution_mailpoet_queue_id'))->equals((string)$this->queue->getId());
    verify($order->get_meta('_wc_order_attribution_mailpoet_subscriber_id'))->equals((string)$this->subscriber->getId());
    verify($order->get_meta('_wc_order_attribution_source_type'))->equals('utm');
    verify($order->get_meta('_wc_order_attribution_utm_source'))->equals('mailpoet');
    verify($order->get_meta('_wc_order_attribution_utm_medium'))->equals('email');
    verify($order->get_meta('_wc_order_attribution_utm_source_platform'))->equals('mailpoet');
    verify($order->get_meta('_wc_order_attribution_utm_campaign'))->equals('First Campaign');
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

    $order = $this->reloadOrder($order);
    verify($order->get_meta('_wc_order_attribution_mailpoet_click_id'))->equals((string)$cookieClick->getId());
    verify($order->get_meta('_wc_order_attribution_mailpoet_newsletter_id'))->equals((string)$cookieNewsletter->getId());
    verify($order->get_meta('_wc_order_attribution_utm_campaign'))->equals('Cookie Campaign');
  }

  public function testLastClickWinsAcrossNewslettersForTheSameSubscriber(): void {
    $this->createClick($this->link, $this->subscriber, 3);

    $newerNewsletter = $this->createNewsletter('Newer Campaign');
    $newerQueue = $this->createQueue($newerNewsletter, $this->subscriber);
    $newerLink = $this->createLink($newerNewsletter, $newerQueue);
    $newerClick = $this->createClick($newerLink, $this->subscriber, 1);
    $this->entityManager->flush();

    $order = $this->createOrder($this->subscriber->getEmail());
    $this->writer->writeForOrder($order->get_id());

    $order = $this->reloadOrder($order);
    verify($order->get_meta('_wc_order_attribution_mailpoet_click_id'))->equals((string)$newerClick->getId());
    verify($order->get_meta('_wc_order_attribution_mailpoet_newsletter_id'))->equals((string)$newerNewsletter->getId());
  }

  public function testItPreservesExistingNonMailPoetSource(): void {
    $click = $this->createClick($this->link, $this->subscriber);
    $this->entityManager->flush();

    $order = $this->createOrder($this->subscriber->getEmail());
    $order->update_meta_data('_wc_order_attribution_source_type', 'referral');
    $order->update_meta_data('_wc_order_attribution_utm_source', 'google');
    $order->save_meta_data();

    $this->writer->writeForOrder($order->get_id());

    $order = $this->reloadOrder($order);
    verify($order->get_meta('_wc_order_attribution_source_type'))->equals('referral');
    verify($order->get_meta('_wc_order_attribution_utm_source'))->equals('google');
    verify($order->meta_exists('_wc_order_attribution_utm_medium'))->false();
    verify($order->get_meta('_wc_order_attribution_mailpoet_click_id'))->equals((string)$click->getId());
  }

  public function testItIsIdempotent(): void {
    $click = $this->createClick($this->link, $this->subscriber);
    $this->entityManager->flush();
    $order = $this->createOrder($this->subscriber->getEmail());

    $this->writer->writeForOrder($order->get_id());
    $writesStartedAt = get_option(OrderAttributionWriter::WRITES_STARTED_AT_OPTION);
    $this->writer->writeForOrder($order->get_id());

    $order = $this->reloadOrder($order);
    verify($this->getMetaValues($order, '_wc_order_attribution_mailpoet_click_id'))->equals([(string)$click->getId()]);
    verify($this->getMetaValues($order, '_wc_order_attribution_utm_source'))->equals(['mailpoet']);
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
    verify($order->meta_exists('_wc_order_attribution_mailpoet_click_id'))->false();
    verify($order->meta_exists('_wc_order_attribution_utm_source'))->false();
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
    verify($order->meta_exists('_wc_order_attribution_mailpoet_click_id'))->false();
    verify(get_option(OrderAttributionWriter::WRITES_STARTED_AT_OPTION))->false();
  }

  public function testItRemovesEmptyPlaceholdersWhenNoClickResolves(): void {
    $order = $this->createOrder('no-clicks@example.com');
    foreach (OrderAttributionFields::FIELD_NAMES as $fieldName) {
      $order->update_meta_data('_wc_order_attribution_' . $fieldName, '');
    }
    $order->save_meta_data();

    $this->writer->writeForOrder($order->get_id());

    $order = $this->reloadOrder($order);
    foreach (OrderAttributionFields::FIELD_NAMES as $fieldName) {
      verify($order->meta_exists('_wc_order_attribution_' . $fieldName))->false();
    }
    verify($order->meta_exists('_wc_order_attribution_utm_source'))->false();
  }

  public function testItWritesViaWooAttributionDataAction(): void {
    $click = $this->createClick($this->link, $this->subscriber);
    $this->entityManager->flush();
    $order = $this->createOrder($this->subscriber->getEmail());

    // simulate the params of a classic checkout submission: Woo's own priority-10
    // handler persists the MailPoet fields as empty placeholders, which the
    // MailPoet handler then overwrites with the resolved values
    $params = array_fill_keys([
      'source_type',
      'referrer',
      'utm_campaign',
      'utm_source',
      'utm_medium',
      'utm_content',
      'utm_id',
      'utm_term',
      'utm_source_platform',
      'utm_creative_format',
      'utm_marketing_tactic',
      'session_entry',
      'session_start_time',
      'session_pages',
      'session_count',
      'user_agent',
    ], '(none)');
    foreach (OrderAttributionFields::FIELD_NAMES as $fieldName) {
      $params[$fieldName] = '';
    }

    do_action('woocommerce_order_save_attribution_data', $order, $params);

    $order = $this->reloadOrder($order);
    verify($order->get_meta('_wc_order_attribution_mailpoet_click_id'))->equals((string)$click->getId());
    verify($this->getMetaValues($order, '_wc_order_attribution_mailpoet_click_id'))->equals([(string)$click->getId()]);
  }

  public function testItWritesWhenOrderStatusChanges(): void {
    $click = $this->createClick($this->link, $this->subscriber);
    $this->entityManager->flush();
    $order = $this->createOrder($this->subscriber->getEmail());

    $order->set_status('processing');
    $order->save();

    $order = $this->reloadOrder($order);
    verify($order->get_meta('_wc_order_attribution_mailpoet_click_id'))->equals((string)$click->getId());
  }

  public function testNewOrderPathRunsOnlyInAdminOrRestContext(): void {
    $click = $this->createClick($this->link, $this->subscriber);
    $this->entityManager->flush();
    $order = $this->createOrder($this->subscriber->getEmail());

    // neither admin nor REST here, so the new-order path must not write
    $this->writer->writeForNewOrder($order->get_id());
    $reloaded = $this->reloadOrder($order);
    verify($reloaded->meta_exists('_wc_order_attribution_mailpoet_click_id'))->false();

    $adminWriter = new OrderAttributionWriter(
      Stub::make(WPFunctions::class, ['isAdmin' => true]),
      $this->diContainer->get(Helper::class),
      $this->diContainer->get(TrackingConfig::class),
      $this->diContainer->get(StatisticsClicksRepository::class),
      $this->diContainer->get(SubscribersRepository::class),
      new Cookies()
    );
    $adminWriter->writeForNewOrder($order->get_id());
    $reloaded = $this->reloadOrder($order);
    verify($reloaded->get_meta('_wc_order_attribution_mailpoet_click_id'))->equals((string)$click->getId());
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
