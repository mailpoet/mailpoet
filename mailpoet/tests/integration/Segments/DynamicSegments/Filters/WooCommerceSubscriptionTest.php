<?php declare(strict_types = 1);

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Test\DataFactories\WooCommerceSubscription as WooCommerceSubscriptionFactory;

/**
 * @group woo
 */
class WooCommerceSubscriptionTest extends \MailPoetTest {

  /**
   * The email address defines also the post_status of the subscription.
   */
  private const ACTIVE_EMAILS = [
    'active_subscriber1@example.com',
    'active_subscriber2@example.com',
    'pending-cancel_subscriber1@example.com',
  ];
  private const INACTIVE_EMAILS = [
    'cancelled_subscriber1@example.com',
  ];
  private const SUBSCRIBER_EMAILS = self::ACTIVE_EMAILS + self::INACTIVE_EMAILS;

  /** @var array */
  private $subscriptions = [];
  /** @var array */
  private $products = [];
  /** @var WooCommerceSubscriptionFactory */
  private $subscriptionsFactory;
  /** @var WooCommerceSubscription */
  private $wooCommerceSubscriptionFilter;

  public function _before(): void {
    $this->cleanup();
    $this->wooCommerceSubscriptionFilter = $this->diContainer->get(WooCommerceSubscription::class);
    $productId = $this->createProduct('Premium Newsletter');
    $this->subscriptionsFactory = new WooCommerceSubscriptionFactory();
    foreach (self::SUBSCRIBER_EMAILS as $email) {
      $userId = $this->tester->createWordPressUser($email, 'subscriber');

      $status = explode('_', $email)[0];
      $this->subscriptionsFactory->createSubscription($userId, $productId, $status);
    }
  }

  public function testAllSubscribersFoundWithOperatorAny(): void {
    $filterData = $this->getSegmentFilterData(
      DynamicSegmentFilterData::OPERATOR_ANY
    );
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($filterData, $this->wooCommerceSubscriptionFilter);
    $this->assertEqualsCanonicalizing(self::ACTIVE_EMAILS, $emails);
  }

  public function testAllSubscribersFoundWithOperatorNoneOf(): void {
    $productId = $this->createProduct("Another newsletter");
    $notToBeFoundEmail = "not-to-be-found@example.com";
    $subscriberId = $this->tester->createWordPressUser($notToBeFoundEmail, "subscriber");
    $this->assertTrue(!is_wp_error($subscriberId), "User could not be created $notToBeFoundEmail");
    $this->subscriptionsFactory->createSubscription($subscriberId, $productId);
    $filterData = $this->getSegmentFilterData(
      DynamicSegmentFilterData::OPERATOR_NONE,
      [$productId]
    );
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($filterData, $this->wooCommerceSubscriptionFilter);
    verify($emails)->arrayCount(3);
    verify(in_array($notToBeFoundEmail, $emails))->false();
    $this->tester->deleteWordPressUser($notToBeFoundEmail);
  }

  public function testAllSubscribersFoundWithOperatorAllOf(): void {
    $this->createProduct("Another newsletter");
    $notToBeFoundEmail = "not-to-be-found@example.com";
    $toBeFoundEmail = "find-me@example.com";
    $this->tester->deleteWordPressUser($toBeFoundEmail);
    $this->tester->deleteWordPressUser($notToBeFoundEmail);
    $notToBeFoundSubscriberId = $this->tester->createWordPressUser($notToBeFoundEmail, "subscriber");
    $toBeFoundSubscriberId = $this->tester->createWordPressUser($toBeFoundEmail, "subscriber");
    $this->assertTrue(!is_wp_error($toBeFoundSubscriberId), "Could not create user $toBeFoundEmail");
    $this->assertTrue(!is_wp_error($notToBeFoundSubscriberId), "Could not create user $notToBeFoundEmail");

    foreach ($this->products as $productId) {
      $this->subscriptionsFactory->createSubscription($toBeFoundSubscriberId, $productId);
    }
    $filterData = $this->getSegmentFilterData(
      DynamicSegmentFilterData::OPERATOR_ALL,
      $this->products
    );
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($filterData, $this->wooCommerceSubscriptionFilter);
    $this->assertEqualsCanonicalizing([$toBeFoundEmail], $emails);
    $this->tester->deleteWordPressUser($notToBeFoundEmail);
    $this->tester->deleteWordPressUser($toBeFoundEmail);
  }

  private function createProduct(string $name): int {
    $productData = [
      'post_type' => 'product',
      'post_status' => 'publish',
      'post_title' => $name,
    ];
    $productId = wp_insert_post($productData);
    $this->products[] = (int)$productId;
    return (int)$productId;
  }

  private function getSegmentFilterData(string $operator, array $productIds = null): DynamicSegmentFilterData {
    $filterData = [
      'operator' => $operator,
      'product_ids' => $productIds ?: $this->products,
    ];

    return new DynamicSegmentFilterData(
      DynamicSegmentFilterData::TYPE_WOOCOMMERCE_SUBSCRIPTION,
      WooCommerceSubscription::ACTION_HAS_ACTIVE,
      $filterData
    );
  }

  public function _after(): void {
    parent::_after();
    $this->cleanUp();
  }

  public function cleanUp(): void {
    global $wpdb;
    foreach (self::SUBSCRIBER_EMAILS as $email) {
      $this->tester->deleteWordPressUser($email);
    }

    foreach ($this->products as $productId) {
      wp_delete_post($productId);
    }
    foreach ($this->subscriptions as $productId) {
      wp_delete_post($productId);
    }

    $this->connection->executeQuery("TRUNCATE {$wpdb->prefix}woocommerce_order_itemmeta");
    $this->connection->executeQuery("TRUNCATE {$wpdb->prefix}woocommerce_order_items");
  }
}
