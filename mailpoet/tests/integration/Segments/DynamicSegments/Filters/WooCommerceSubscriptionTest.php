<?php

namespace MailPoet\Segments\DynamicSegments\Filters;

use Helper\Database;
use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoetVendor\Doctrine\DBAL\ForwardCompatibility\DriverStatement;
use MailPoetVendor\Doctrine\DBAL\Query\QueryBuilder;

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

  public function _before(): void {

    Database::createLookUpTables();

    $this->cleanup();
    $productId = $this->createProduct('Premium Newsletter');
    foreach (self::SUBSCRIBER_EMAILS as $email) {
      $userId = $this->tester->createWordPressUser($email, 'subscriber');
      $status = 'wc-' . explode('_', $email)[0];
      $this->createSubscription(
        [
          'post_status' => $status,
        ],
        $userId,
        $productId
      );
    }
  }

  public function testAllSubscribersFoundWithOperatorAny(): void {
    $testee = $this->diContainer->get(WooCommerceSubscription::class);
    $queryBuilder = $this->getQueryBuilder();
    $filter = $this->getSegmentFilter(
      DynamicSegmentFilterData::OPERATOR_ANY
    );

    $resultQuery = $testee->apply($queryBuilder, $filter);
    $foundSubscribers = $this->getEmailsFromQueryBuilder($resultQuery);

    $this->assertCount(3, $foundSubscribers, "Did not find expected three subscribers.");
    foreach ($foundSubscribers as $email) {
      $this->assertTrue(in_array($email, self::ACTIVE_EMAILS));
    }
  }

  public function testAllSubscribersFoundWithOperatorNoneOf(): void {
    $product = $this->createProduct("Another newsletter");
    $notToBeFoundEmail = "not-to-be-found@example.com";
    $subscriberId = $this->tester->createWordPressUser($notToBeFoundEmail, "subscriber");
    $this->assertTrue(!is_wp_error($subscriberId), "User could not be created $notToBeFoundEmail");

    $this->createSubscription(
      [],
      (int)$subscriberId,
      $product
    );
    $testee = $this->diContainer->get(WooCommerceSubscription::class);
    $queryBuilder = $this->getQueryBuilder();
    $filter = $this->getSegmentFilter(
      DynamicSegmentFilterData::OPERATOR_NONE,
      [$product]
    );


    $resultQuery = $testee->apply($queryBuilder, $filter);

    $foundSubscribers = $this->getEmailsFromQueryBuilder($resultQuery);

    $this->assertCount(3, $foundSubscribers);
    $this->assertFalse(in_array($notToBeFoundEmail, $foundSubscribers));
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

    $this->createSubscription(
      [],
      (int)$toBeFoundSubscriberId,
      ...$this->products
    );
    $testee = $this->diContainer->get(WooCommerceSubscription::class);
    $queryBuilder = $this->getQueryBuilder();
    $filter = $this->getSegmentFilter(
      DynamicSegmentFilterData::OPERATOR_ALL,
      $this->products
    );

    $resultQuery = $testee->apply($queryBuilder, $filter);

    $foundSubscribers = $this->getEmailsFromQueryBuilder($resultQuery);

    $this->assertCount(1, $foundSubscribers);
    $this->assertTrue(in_array($toBeFoundEmail, $foundSubscribers));
    $this->tester->deleteWordPressUser($notToBeFoundEmail);
    $this->tester->deleteWordPressUser($toBeFoundEmail);
  }

  private function getEmailsFromQueryBuilder(QueryBuilder $builder): array {

    $repository = $this->diContainer->get(SubscribersRepository::class);
    $statement = $builder->execute();
    if (!$statement instanceof DriverStatement) {
      throw new \RuntimeException("Could not create statement.");
    }
    $data = $statement->fetchAllAssociative();
    return array_map(
      function(array $data) use ($repository): ?string {
        /**
         * @var SubscriberEntity|null $subscriber
         */
        $subscriber = $repository->findOneById($data['id']);
        return $subscriber ? $subscriber->getEmail() : null;
      },
      $data
    );
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

  private function createSubscription(array $args, int $user, int ...$productIds): int {
    global $wpdb;
    $defaults = [
      'post_status' => 'wc-active',
      'post_type' => 'shop_subscription',
      'post_author' => 1,
    ];

    $args = wp_parse_args($args, $defaults);
    $orderId = wp_insert_post($args);
    $orderId = (int)$orderId;
    update_post_meta( $orderId, '_customer_user', $user );

    foreach ($productIds as $productId) {
      $sql = 'insert into ' . $wpdb->prefix . 'woocommerce_order_items (order_id,order_item_type) values (' . $orderId . ', "line_item")';
      $wpdb->query($sql);
      $sql = 'select LAST_INSERT_ID() as id';
      $lineItemId = $wpdb->get_col($sql)[0];
      $sql = 'insert into ' . $wpdb->prefix . 'woocommerce_order_itemmeta (order_item_id, meta_key, meta_value) values (' . $lineItemId . ', "_product_id", "' . $productId . '")';
      $wpdb->query($sql);
    }

    $this->subscriptions[] = $orderId;
    return $orderId;
  }

  private function getSegmentFilter(string $operator, array $productIds = null): DynamicSegmentFilterEntity {
    $filterData = [
      'operator' => $operator,
      'product_ids' => $productIds ? $productIds : $this->products,
    ];

    $data = new DynamicSegmentFilterData(
      DynamicSegmentFilterData::TYPE_WOOCOMMERCE_SUBSCRIPTION,
      WooCommerceSubscription::ACTION_HAS_ACTIVE,
      $filterData
    );
    $segment = new SegmentEntity('Dynamic Segment', SegmentEntity::TYPE_DYNAMIC, 'description');
    $this->entityManager->persist($segment);
    $dynamicSegmentFilter = new DynamicSegmentFilterEntity($segment, $data);
    $this->entityManager->persist($dynamicSegmentFilter);
    $segment->addDynamicFilter($dynamicSegmentFilter);
    return $dynamicSegmentFilter;
  }

  private function getQueryBuilder(): QueryBuilder {
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    return $this->entityManager
      ->getConnection()
      ->createQueryBuilder()
      ->select("DISTINCT $subscribersTable.id")
      ->from($subscribersTable);
  }

  public function _after(): void {
    $this->cleanUp();

    global $wpdb;
    $this->connection->executeQuery("DROP TABLE IF EXISTS {$wpdb->prefix}woocommerce_order_itemmeta");
    $this->connection->executeQuery("DROP TABLE IF EXISTS {$wpdb->prefix}woocommerce_order_items");
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
