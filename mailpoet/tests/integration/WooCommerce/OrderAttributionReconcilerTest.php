<?php declare(strict_types = 1);

namespace MailPoet\WooCommerce;

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
use MailPoet\Statistics\StatisticsWooCommercePurchasesRepository;
use WC_Order;

/**
 * @group woo
 */
class OrderAttributionReconcilerTest extends \MailPoetTest {
  private const WOO_ATTRIBUTION_FEATURE_OPTION = 'woocommerce_feature_order_attribution_enabled';

  /** @var SubscriberEntity */
  private $subscriber;

  /** @var NewsletterEntity */
  private $newsletter;

  /** @var SendingQueueEntity */
  private $queue;

  /** @var NewsletterLinkEntity */
  private $link;

  /** @var OrderAttributionReconciler */
  private $reconciler;

  /** @var SettingsController */
  private $settings;

  public function _before() {
    parent::_before();
    unset($_COOKIE['mailpoet_revenue_tracking']);
    $this->settings = SettingsController::getInstance();
    $this->settings->set('tracking.level', TrackingConfig::LEVEL_FULL);
    // Mirror production: the boundary is persisted on init, before any
    // post-activation order exists, so even the first order is reconciled.
    delete_option(OrderAttributionWriter::WRITES_STARTED_AT_OPTION);
    $this->diContainer->get(OrderAttributionWriter::class)->markWritesStartedIfActive();
    $this->subscriber = $this->createSubscriber('reconciliation@example.com');
    $this->newsletter = $this->createNewsletter('First Campaign');
    $this->queue = $this->createQueue($this->newsletter, $this->subscriber);
    $this->link = $this->createLink($this->newsletter, $this->queue);
    $this->reconciler = $this->diContainer->get(OrderAttributionReconciler::class);
  }

  public function testItRecordsMatchedAttributionViaStatusChangeHook(): void {
    $click = $this->createClick($this->link, $this->subscriber);
    $this->entityManager->flush();
    $order = $this->createOrder($this->subscriber->getEmail());

    $this->completeOrder($order);

    $record = $this->getRecord($this->reloadOrder($order));
    verify($record['outcome'])->equals(OrderAttributionReconciler::OUTCOME_MATCH);
    verify($record['reason'])->equals(OrderAttributionReconciler::REASON_MATCHED);
    verify($record['classification'])->null();
    verify($record['trigger'])->equals(OrderAttributionReconciler::TRIGGER_STATUS_CHANGED);
    verify($record['legacy_click_ids'])->equals([$click->getId()]);
    verify($record['legacy_last_click_id'])->equals($click->getId());
    verify($record['woo_click_id'])->equals($click->getId());
    verify($record['legacy_revenue'])->equals(15.0);
    verify($record['legacy_revenue_per_newsletter'])->equals(15.0);
    verify($record['woo_revenue'])->equals(15.0);
    verify($record['order_status'])->equals('completed');
    verify($record['foreign_source_preserved'])->false();
  }

  public function testItRecordsLastClickCollapseForMultipleNewsletters(): void {
    $olderClick = $this->createClick($this->link, $this->subscriber, 5);

    $newerNewsletter = $this->createNewsletter('Newer Campaign');
    $newerQueue = $this->createQueue($newerNewsletter, $this->subscriber);
    $newerLink = $this->createLink($newerNewsletter, $newerQueue);
    $newerClick = $this->createClick($newerLink, $this->subscriber, 1);
    $this->entityManager->flush();

    $order = $this->createOrder($this->subscriber->getEmail());
    $this->completeOrder($order);

    $record = $this->getRecord($this->reloadOrder($order));
    verify($record['outcome'])->equals(OrderAttributionReconciler::OUTCOME_DIVERGED);
    verify($record['reason'])->equals(OrderAttributionReconciler::REASON_LAST_CLICK_COLLAPSE);
    verify($record['classification'])->equals(OrderAttributionReconciler::CLASSIFICATION_INTENTIONAL);
    verify($record['legacy_click_ids'])->equals([$olderClick->getId(), $newerClick->getId()]);
    verify($record['legacy_min_click_id'])->equals($olderClick->getId());
    verify($record['legacy_last_click_id'])->equals($newerClick->getId());
    verify($record['woo_click_id'])->equals($newerClick->getId());
    verify($record['legacy_revenue'])->equals(15.0);
    verify($record['legacy_revenue_per_newsletter'])->equals(30.0);
    verify($record['woo_revenue'])->equals(15.0);
  }

  public function testItRecordsPreservedForeignSourceAsIntentional(): void {
    $click = $this->createClick($this->link, $this->subscriber);
    $this->entityManager->flush();

    $order = $this->createOrder($this->subscriber->getEmail());
    $order->update_meta_data(OrderAttributionFields::getMetaKey('source_type'), 'referral');
    $order->update_meta_data(OrderAttributionFields::getMetaKey('utm_source'), 'google');
    $order->save_meta_data();

    $this->completeOrder($order);

    $record = $this->getRecord($this->reloadOrder($order));
    verify($record['outcome'])->equals(OrderAttributionReconciler::OUTCOME_DIVERGED);
    verify($record['reason'])->equals(OrderAttributionReconciler::REASON_FOREIGN_SOURCE_PRESERVED);
    verify($record['classification'])->equals(OrderAttributionReconciler::CLASSIFICATION_INTENTIONAL);
    verify($record['foreign_source_preserved'])->true();
    verify($record['woo_click_id'])->equals($click->getId());
    verify($record['legacy_revenue'])->equals(15.0);
    verify($record['woo_revenue'])->equals(15.0);
  }

  public function testItRecordsMissingWooAttribution(): void {
    $this->createClick($this->link, $this->subscriber);
    $this->entityManager->flush();
    $order = $this->createOrder($this->subscriber->getEmail());
    $this->completeOrder($order);

    $order = $this->reloadOrder($order);
    $order->delete_meta_data(OrderAttributionFields::getMetaKey(OrderAttributionFields::FIELD_CLICK_ID));
    $order->save_meta_data();

    $this->reconciler->reconcileForOrder($order->get_id());

    $record = $this->getRecord($this->reloadOrder($order));
    verify($record['outcome'])->equals(OrderAttributionReconciler::OUTCOME_DIVERGED);
    verify($record['reason'])->equals(OrderAttributionReconciler::REASON_WOO_ATTRIBUTION_MISSING);
    verify($record['classification'])->equals(OrderAttributionReconciler::CLASSIFICATION_UNEXPLAINED);
    verify($record['woo_click_id'])->null();
    verify($record['legacy_revenue'])->equals(15.0);
    verify($record['woo_revenue'])->equals(0.0);
  }

  public function testItRecordsMissingLegacyAttribution(): void {
    $this->createClick($this->link, $this->subscriber);
    $this->entityManager->flush();
    $order = $this->createOrder($this->subscriber->getEmail());
    $this->completeOrder($order);

    $purchasesRepository = $this->diContainer->get(StatisticsWooCommercePurchasesRepository::class);
    foreach ($purchasesRepository->findBy(['orderId' => $order->get_id()]) as $purchase) {
      $this->entityManager->remove($purchase);
    }
    $this->entityManager->flush();

    $this->reconciler->reconcileForOrder($order->get_id());

    $record = $this->getRecord($this->reloadOrder($order));
    verify($record['outcome'])->equals(OrderAttributionReconciler::OUTCOME_DIVERGED);
    verify($record['reason'])->equals(OrderAttributionReconciler::REASON_LEGACY_ATTRIBUTION_MISSING);
    verify($record['classification'])->equals(OrderAttributionReconciler::CLASSIFICATION_UNEXPLAINED);
    verify($record['legacy_click_ids'])->equals([]);
    verify($record['legacy_revenue'])->equals(0.0);
    verify($record['woo_revenue'])->equals(15.0);
  }

  public function testItRecordsCanonicalClickMismatch(): void {
    $click = $this->createClick($this->link, $this->subscriber);
    $this->entityManager->flush();
    $order = $this->createOrder($this->subscriber->getEmail());
    $this->completeOrder($order);

    $order = $this->reloadOrder($order);
    $order->update_meta_data(OrderAttributionFields::getMetaKey(OrderAttributionFields::FIELD_CLICK_ID), (string)((int)$click->getId() + 999));
    $order->save_meta_data();

    $this->reconciler->reconcileForOrder($order->get_id());

    $record = $this->getRecord($this->reloadOrder($order));
    verify($record['outcome'])->equals(OrderAttributionReconciler::OUTCOME_DIVERGED);
    verify($record['reason'])->equals(OrderAttributionReconciler::REASON_CANONICAL_CLICK_MISMATCH);
    verify($record['classification'])->equals(OrderAttributionReconciler::CLASSIFICATION_UNEXPLAINED);
  }

  public function testItRecordsTrackingDisabledWhenLegacyRowsExist(): void {
    $this->createClick($this->link, $this->subscriber);
    $this->entityManager->flush();
    $order = $this->createOrder($this->subscriber->getEmail());

    $this->settings->set('tracking.level', TrackingConfig::LEVEL_BASIC);
    $this->completeOrder($order);

    $record = $this->getRecord($this->reloadOrder($order));
    verify($record['outcome'])->equals(OrderAttributionReconciler::OUTCOME_DIVERGED);
    verify($record['reason'])->equals(OrderAttributionReconciler::REASON_TRACKING_DISABLED);
    verify($record['classification'])->equals(OrderAttributionReconciler::CLASSIFICATION_UNEXPLAINED);
    verify($record['woo_click_id'])->null();
  }

  public function testItRecordsWooAttributionDisabled(): void {
    $this->createClick($this->link, $this->subscriber);
    $this->entityManager->flush();
    $order = $this->createOrder($this->subscriber->getEmail());

    update_option(self::WOO_ATTRIBUTION_FEATURE_OPTION, 'no');
    try {
      $this->completeOrder($order);
    } finally {
      update_option(self::WOO_ATTRIBUTION_FEATURE_OPTION, 'yes');
    }

    $record = $this->getRecord($this->reloadOrder($order));
    verify($record['outcome'])->equals(OrderAttributionReconciler::OUTCOME_DIVERGED);
    verify($record['reason'])->equals(OrderAttributionReconciler::REASON_WOO_ATTRIBUTION_DISABLED);
    verify($record['classification'])->equals(OrderAttributionReconciler::CLASSIFICATION_UNEXPLAINED);
  }

  public function testItRecordsNoClickCandidateWhenBothSidesAreEmpty(): void {
    $order = $this->createOrder('no-clicks@example.com');
    $this->completeOrder($order);

    $record = $this->getRecord($this->reloadOrder($order));
    verify($record['outcome'])->equals(OrderAttributionReconciler::OUTCOME_MATCH);
    verify($record['reason'])->equals(OrderAttributionReconciler::REASON_NO_CLICK_CANDIDATE);
    verify($record['classification'])->null();
    verify($record['legacy_click_ids'])->equals([]);
    verify($record['woo_click_id'])->null();
  }

  public function testItSkipsOrdersWithoutAttributionWhenTrackingIsDisabled(): void {
    $this->settings->set('tracking.level', TrackingConfig::LEVEL_BASIC);
    $order = $this->createOrder('no-clicks@example.com');
    $this->completeOrder($order);

    $order = $this->reloadOrder($order);
    verify($order->meta_exists(OrderAttributionReconciler::RECONCILIATION_META_KEY))->false();
  }

  public function testItSkipsOrdersCreatedBeforeWritesStartedBoundary(): void {
    $this->createClick($this->link, $this->subscriber);
    $this->entityManager->flush();
    $order = $this->createOrder($this->subscriber->getEmail());

    update_option(OrderAttributionWriter::WRITES_STARTED_AT_OPTION, gmdate('Y-m-d H:i:s', time() + HOUR_IN_SECONDS));
    $this->reconciler->reconcileForOrder($order->get_id());
    $reloaded = $this->reloadOrder($order);
    verify($reloaded->meta_exists(OrderAttributionReconciler::RECONCILIATION_META_KEY))->false();

    delete_option(OrderAttributionWriter::WRITES_STARTED_AT_OPTION);
    $this->reconciler->reconcileForOrder($order->get_id());
    $reloaded = $this->reloadOrder($order);
    verify($reloaded->meta_exists(OrderAttributionReconciler::RECONCILIATION_META_KEY))->false();
  }

  public function testItUpdatesRevenueOnRefund(): void {
    $this->createClick($this->link, $this->subscriber);
    $this->entityManager->flush();
    $order = $this->createOrder($this->subscriber->getEmail());
    $this->completeOrder($order);

    $refund = wc_create_refund([
      'order_id' => $order->get_id(),
      'amount' => 5,
    ]);
    $this->assertNotInstanceOf(\WP_Error::class, $refund);

    $record = $this->getRecord($this->reloadOrder($order));
    verify($record['trigger'])->equals(OrderAttributionReconciler::TRIGGER_REFUND);
    verify($record['outcome'])->equals(OrderAttributionReconciler::OUTCOME_MATCH);
    verify($record['legacy_revenue'])->equals(10.0);
    verify($record['woo_revenue'])->equals(10.0);
  }

  public function testRecordCanBeRebuiltFromDirectCall(): void {
    $click = $this->createClick($this->link, $this->subscriber);
    $this->entityManager->flush();
    $order = $this->createOrder($this->subscriber->getEmail());
    $this->completeOrder($order);

    $this->reconciler->reconcileForOrder($this->reloadOrder($order));

    $record = $this->getRecord($this->reloadOrder($order));
    verify($record['version'])->equals(OrderAttributionReconciler::RECORD_VERSION);
    verify($record['woo_click_id'])->equals($click->getId());
    verify($record['currency'])->equals(get_woocommerce_currency());
    verify($record['order_created_at'])->notEmpty();
    verify($record['reconciled_at'])->notEmpty();
  }

  private function createOrder(string $billingEmail): WC_Order {
    $order = wc_create_order();
    $this->assertInstanceOf(WC_Order::class, $order);
    $order->set_billing_email($billingEmail);
    $order->set_total('15');
    $order->save();
    return $order;
  }

  /**
   * Drives the production pipeline on woocommerce_order_status_changed: the legacy
   * purchase tracker and the attribution writer (priority 10), then the reconciler
   * (priority 20).
   */
  private function completeOrder(WC_Order $order): void {
    $order->set_status('completed');
    $order->save();
  }

  private function reloadOrder(WC_Order $order): WC_Order {
    $reloaded = wc_get_order($order->get_id());
    $this->assertInstanceOf(WC_Order::class, $reloaded);
    return $reloaded;
  }

  /**
   * @return mixed[]
   */
  private function getRecord(WC_Order $order): array {
    $meta = $order->get_meta(OrderAttributionReconciler::RECONCILIATION_META_KEY);
    $this->assertIsString($meta);
    $this->assertNotSame('', $meta, 'Expected a reconciliation record on the order');
    $record = json_decode($meta, true);
    $this->assertIsArray($record);
    return $record;
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
