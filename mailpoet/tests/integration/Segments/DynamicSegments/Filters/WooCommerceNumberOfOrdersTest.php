<?php declare(strict_types = 1);

namespace MailPoet\Segments\DynamicSegments\Filters;

use Helper\Database;
use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Doctrine\DBAL\Driver\Statement;
use MailPoetVendor\Doctrine\DBAL\Query\QueryBuilder;

class WooCommerceNumberOfOrdersTest extends \MailPoetTest {
  /** @var WooCommerceNumberOfOrders */
  private $numberOfOrders;

  /** @var array */
  private $orders;

  public function _before(): void {
    $this->numberOfOrders = $this->diContainer->get(WooCommerceNumberOfOrders::class);
    Database::loadSQL('createWCLookupTables');
    $this->cleanUp();

    $customerId1 = $this->createCustomer('customer1@example.com', 'customer');
    $customerId2 = $this->createCustomer('customer2@example.com', 'customer');
    $customerId3 = $this->createCustomer('customer3@example.com', 'customer');

    $this->orders[] = $this->createOrder($customerId1, Carbon::now()->subDays(3));
    $this->orders[] = $this->createOrder($customerId2, Carbon::now());
    $this->orders[] = $this->createOrder($customerId2, Carbon::now());
    $this->orders[] = $this->createOrder($customerId3, Carbon::now());
  }

  public function testItGetsCustomersThatPlacedTwoOrdersInTheLastDay(): void {
    $segmentFilter = $this->getSegmentFilter('=', 2, 1);
    $result = $this->applyFilter($this->numberOfOrders, $this->getQueryBuilder(), $segmentFilter);
    $this->assertSame(1, count($result));
    $subscriber1 = $this->entityManager->find(SubscriberEntity::class, $result[0]['inner_subscriber_id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber1);
    $this->assertSame('customer2@example.com', $subscriber1->getEmail());
  }

  public function testItGetsCustomersThatPlacedZeroOrdersInTheLastDay(): void {
    $segmentFilter = $this->getSegmentFilter('=', 0, 1);
    $result = $this->applyFilter($this->numberOfOrders, $this->getQueryBuilder(), $segmentFilter);

    $this->assertSame(1, count($result));
    $subscriber1 = $this->entityManager->find(SubscriberEntity::class, $result[0]['inner_subscriber_id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber1);
    $this->assertSame('customer1@example.com', $subscriber1->getEmail());
  }

  public function testItGetsCustomersThatDidNotPlaceTwoOrdersInTheLastWeek(): void {
    $segmentFilter = $this->getSegmentFilter('!=', 2, 7);
    $result = $this->applyFilter($this->numberOfOrders, $this->getQueryBuilder(), $segmentFilter);

    $this->assertSame(2, count($result));
    $subscriber1 = $this->entityManager->find(SubscriberEntity::class, $result[0]['inner_subscriber_id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber1);
    $this->assertSame('customer1@example.com', $subscriber1->getEmail());
    $subscriber2 = $this->entityManager->find(SubscriberEntity::class, $result[1]['inner_subscriber_id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber2);
    $this->assertSame('customer3@example.com', $subscriber2->getEmail());
  }

  public function testItGetsCustomersThatPlacedAtLeastOneOrderInTheLastWeek(): void {
    $segmentFilter = $this->getSegmentFilter('>', 0, 7);
    $result = $this->applyFilter($this->numberOfOrders, $this->getQueryBuilder(), $segmentFilter);
    $this->assertSame(3, count($result));
  }

  private function getQueryBuilder(): QueryBuilder {
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    return $this->entityManager
      ->getConnection()
      ->createQueryBuilder()
      ->select("$subscribersTable.id as inner_subscriber_id")
      ->from($subscribersTable);
  }

  private function applyFilter(WooCommerceNumberOfOrders $numberOfOrders, QueryBuilder $queryBuilder, DynamicSegmentFilterEntity $segmentFilter): array {
    $queryBuilder = $numberOfOrders->apply($queryBuilder, $segmentFilter);
    $statement = $queryBuilder->execute();
    $this->assertInstanceOf(Statement::class, $statement);
    return $statement->fetchAll();
  }

  private function getSegmentFilter(string $comparisonType, int $ordersCount, int $days): DynamicSegmentFilterEntity {
    $data = new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_WOOCOMMERCE, WooCommerceNumberOfOrders::ACTION_NUMBER_OF_ORDERS, [
      'number_of_orders_type' => $comparisonType,
      'number_of_orders_count' => $ordersCount,
      'number_of_orders_days' => $days,
    ]);

    $segment = new SegmentEntity('Dynamic Segment', SegmentEntity::TYPE_DYNAMIC, 'description');
    $this->entityManager->persist($segment);
    $dynamicSegmentFilter = new DynamicSegmentFilterEntity($segment, $data);
    $this->entityManager->persist($dynamicSegmentFilter);
    $segment->addDynamicFilter($dynamicSegmentFilter);
    return $dynamicSegmentFilter;
  }

  private function createCustomer(string $email, string $role): int {
    global $wpdb;
    $userId = $this->tester->createWordPressUser($email, $role);
    $this->connection->executeQuery("
      INSERT INTO {$wpdb->prefix}wc_customer_lookup (customer_id, user_id, first_name, last_name, email)
      VALUES ({$userId}, {$userId}, 'First Name', 'Last Name', '{$email}')
    ");
    return $userId;
  }

  private function createOrder(int $customerId, Carbon $createdAt): int {
    global $wpdb;
    $orderData = [
      'post_type' => 'shop_order',
      'post_status' => 'wc-completed',
      'post_date' => $createdAt->toDateTimeString(),
    ];

    $orderId = wp_insert_post($orderData);
    assert(is_integer($orderId));
    $this->connection->executeQuery("
      INSERT INTO {$wpdb->prefix}wc_order_stats (order_id, customer_id, status, date_created, date_created_gmt)
      VALUES ({$orderId}, {$customerId}, 'wc-completed', '{$createdAt->toDateTimeString()}', '{$createdAt->toDateTimeString()}')
    ");
    return $orderId;
  }

  public function _after(): void {
    $this->cleanUp();
  }

  private function cleanUp(): void {
    global $wpdb;
    $this->truncateEntity(SegmentEntity::class);
    $this->truncateEntity(SubscriberEntity::class);
    $emails = ['customer1@example.com', 'customer2@example.com', 'customer3@example.com'];
    foreach ($emails as $email) {
      $this->tester->deleteWordPressUser($email);
    }

    if (!empty($this->orders)) {
      foreach ($this->orders as $orderId) {
        wp_delete_post($orderId);
      }
    }
    $this->connection->executeQuery("TRUNCATE TABLE {$wpdb->prefix}wc_customer_lookup");
    $this->connection->executeQuery("TRUNCATE TABLE {$wpdb->prefix}wc_order_stats");
  }
}
