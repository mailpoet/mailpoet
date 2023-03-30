<?php declare(strict_types = 1);

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Test\DataFactories\WooCommerceSubscription as WooCommerceSubscriptionFactory;
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
  /** @var WooCommerceSubscriptionFactory */
  private $subscriptionsFactory;

  public function _before(): void {
    $this->cleanup();
    $productId = $this->createProduct('Premium Newsletter');
    $this->subscriptionsFactory = new WooCommerceSubscriptionFactory();
    foreach (self::SUBSCRIBER_EMAILS as $email) {
      $userId = $this->tester->createWordPressUser($email, 'subscriber');

      $status = explode('_', $email)[0];
      $this->subscriptionsFactory->createSubscription($userId, $productId, $status);
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
    $productId = $this->createProduct("Another newsletter");
    $notToBeFoundEmail = "not-to-be-found@example.com";
    $subscriberId = $this->tester->createWordPressUser($notToBeFoundEmail, "subscriber");
    $this->assertTrue(!is_wp_error($subscriberId), "User could not be created $notToBeFoundEmail");
    $this->subscriptionsFactory->createSubscription($subscriberId, $productId);
    $testee = $this->diContainer->get(WooCommerceSubscription::class);
    $queryBuilder = $this->getQueryBuilder();
    $filter = $this->getSegmentFilter(
      DynamicSegmentFilterData::OPERATOR_NONE,
      [$productId]
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

    foreach ($this->products as $productId) {
      $this->subscriptionsFactory->createSubscription($toBeFoundSubscriberId, $productId);
    }
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
