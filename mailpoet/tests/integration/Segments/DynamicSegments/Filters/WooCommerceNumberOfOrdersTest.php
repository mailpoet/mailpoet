<?php declare(strict_types = 1);

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Subscribers\Source;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Doctrine\DBAL\Driver\Statement;
use MailPoetVendor\Doctrine\DBAL\Query\QueryBuilder;

/**
 * @group woo
 */
class WooCommerceNumberOfOrdersTest extends \MailPoetTest {
  /** @var WooCommerceNumberOfOrders */
  private $numberOfOrders;

  /** @var array */
  private $orders;

  /**
   * @var SubscriberFactory
   */
  private $subscriberFactory;

  public function _before(): void {
    $this->subscriberFactory = new SubscriberFactory();
    $this->numberOfOrders = $this->diContainer->get(WooCommerceNumberOfOrders::class);
    $this->cleanUp();

    $customerId1 = $this->tester->createCustomer('customer1@example.com', 'customer');
    $customerId2 = $this->tester->createCustomer('customer2@example.com', 'customer');
    $customerId3 = $this->tester->createCustomer('customer3@example.com', 'customer');

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

  public function testItGetsNoneCustomerSubscriberInLastDays() {
    $createdSub = $this->subscriberFactory
      ->withSource(Source::API)
      ->withCreatedAt(Carbon::now()->subDays(5))
      ->create();
    $segmentFilter = $this->getSegmentFilter('=', 0, 30);
    $queryBuilder = $this->numberOfOrders->apply($this->getQueryBuilder(), $segmentFilter);
    $compatibilityResult = $queryBuilder->execute();
    $this->assertInstanceOf(Statement::class, $compatibilityResult);
    $result = $compatibilityResult->fetchAllAssociative();
    $this->assertSame(1, count($result));
    $subscriber1 = $this->entityManager->find(SubscriberEntity::class, $result[0]['inner_subscriber_id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber1);
    $this->assertSame($createdSub->getEmail(), $subscriber1->getEmail());
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

  private function createOrder(int $customerId, Carbon $createdAt, $status = 'wc-completed'): int {
    $order = $this->tester->createWooCommerceOrder();
    $order->set_customer_id($customerId);
    $order->set_date_created($createdAt->toDateTimeString());
    $order->set_status($status);
    $order->save();
    $this->tester->updateWooOrderStats($order->get_id());

    return $order->get_id();
  }

  public function _after(): void {
    parent::_after();
    $this->cleanUp();
  }

  private function cleanUp(): void {
    global $wpdb;

    if (!empty($this->orders)) {
      foreach ($this->orders as $orderId) {
        wp_delete_post($orderId);
      }
    }
    $this->connection->executeQuery("TRUNCATE TABLE {$wpdb->prefix}wc_customer_lookup");
    $this->connection->executeQuery("TRUNCATE TABLE {$wpdb->prefix}wc_order_stats");
  }
}
