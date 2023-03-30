<?php declare(strict_types = 1);

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Doctrine\DBAL\Driver\Statement;
use MailPoetVendor\Doctrine\DBAL\Query\QueryBuilder;

/**
 * @group woo
 */
class WooCommerceTotalSpentTest extends \MailPoetTest {
  /** @var WooCommerceTotalSpent */
  private $totalSpent;

  /** @var WPFunctions */
  private $wp;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var int[] */
  private $orders;

  public function _before(): void {
    $this->totalSpent = $this->diContainer->get(WooCommerceTotalSpent::class);
    $this->subscribersRepository = $this->diContainer->get(SubscribersRepository::class);
    $this->wp = $this->diContainer->get(WPFunctions::class);
    $this->cleanUp();

    $customerId1 = $this->tester->createCustomer('customer1@example.com', 'customer');
    $customerId2 = $this->tester->createCustomer('customer2@example.com', 'customer');
    $customerId3 = $this->tester->createCustomer('customer3@example.com', 'customer');

    $this->orders[] = $this->createOrder($customerId1, Carbon::now()->subDays(3), 10);
    $this->orders[] = $this->createOrder($customerId1, Carbon::now(), 5);
    $this->orders[] = $this->createOrder($customerId2, Carbon::now(), 15);
    $this->orders[] = $this->createOrder($customerId3, Carbon::now(), 25);
  }

  public function testItGetsCustomersThatSpentFifteenInTheLastDay(): void {
    $segmentFilter = $this->getSegmentFilter('=', 15, 1);
    $queryBuilder = $this->totalSpent->apply($this->createQueryBuilder(), $segmentFilter);
    $statement = $queryBuilder->execute();
    $result = $statement instanceof Statement ? $statement->fetchAll() : [];
    expect($result)->count(1);
    $this->assertIsArray($result[0]);
    $subscriber1 = $this->subscribersRepository->findOneById($result[0]['inner_subscriber_id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber1);
    expect($subscriber1)->isInstanceOf(SubscriberEntity::class);
    expect($subscriber1->getEmail())->equals('customer2@example.com');
  }

  public function testItGetsCustomersThatSpentFifteenInTheLastWeek(): void {
    $segmentFilter = $this->getSegmentFilter('=', 15, 7);
    $queryBuilder = $this->totalSpent->apply($this->createQueryBuilder(), $segmentFilter);
    $statement = $queryBuilder->execute();
    $result = $statement instanceof Statement ? $statement->fetchAll() : [];
    expect($result)->count(2);
    $this->assertIsArray($result[0]);
    $subscriber1 = $this->subscribersRepository->findOneById($result[0]['inner_subscriber_id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber1);
    expect($subscriber1)->isInstanceOf(SubscriberEntity::class);
    expect($subscriber1->getEmail())->equals('customer1@example.com');
    $this->assertIsArray($result[1]);
    $subscriber2 = $this->subscribersRepository->findOneById($result[1]['inner_subscriber_id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber2);
    expect($subscriber2)->isInstanceOf(SubscriberEntity::class);
    expect($subscriber2->getEmail())->equals('customer2@example.com');
  }

  public function testItGetsCustomersThatDidNotSpendFifteenInTheLastDay(): void {
    $segmentFilter = $this->getSegmentFilter('!=', 15, 1);
    $queryBuilder = $this->totalSpent->apply($this->createQueryBuilder(), $segmentFilter);
    $statement = $queryBuilder->execute();
    $result = $statement instanceof Statement ? $statement->fetchAll() : [];
    expect($result)->count(2);
    $this->assertIsArray($result[0]);
    $subscriber1 = $this->subscribersRepository->findOneById($result[0]['inner_subscriber_id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber1);
    expect($subscriber1)->isInstanceOf(SubscriberEntity::class);
    expect($subscriber1->getEmail())->equals('customer1@example.com');
    $this->assertIsArray($result[1]);
    $subscriber2 = $this->subscribersRepository->findOneById($result[1]['inner_subscriber_id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber2);
    expect($subscriber2)->isInstanceOf(SubscriberEntity::class);
    expect($subscriber2->getEmail())->equals('customer3@example.com');
  }

  public function testItGetsCustomersThatSpentMoreThanTwentyInTheLastDay(): void {
    $segmentFilter = $this->getSegmentFilter('>', 20, 1);
    $queryBuilder = $this->totalSpent->apply($this->createQueryBuilder(), $segmentFilter);
    $statement = $queryBuilder->execute();
    $result = $statement instanceof Statement ? $statement->fetchAll() : [];
    expect($result)->count(1);
    $this->assertIsArray($result[0]);
    $subscriber1 = $this->subscribersRepository->findOneById($result[0]['inner_subscriber_id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber1);
    expect($subscriber1)->isInstanceOf(SubscriberEntity::class);
    expect($subscriber1->getEmail())->equals('customer3@example.com');
  }

  public function testItGetsCustomersThatSpentLessThanTenInTheLastDay(): void {
    $segmentFilter = $this->getSegmentFilter('<', 10, 1);
    $queryBuilder = $this->totalSpent->apply($this->createQueryBuilder(), $segmentFilter);
    $statement = $queryBuilder->execute();
    $result = $statement instanceof Statement ? $statement->fetchAll() : [];
    expect($result)->count(1);
    $this->assertIsArray($result[0]);
    $subscriber1 = $this->subscribersRepository->findOneById($result[0]['inner_subscriber_id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber1);
    expect($subscriber1)->isInstanceOf(SubscriberEntity::class);
    expect($subscriber1->getEmail())->equals('customer1@example.com');
  }

  public function testItGetsCustomersThatSpentMoreThanTenInTheLastWeek(): void {
    $segmentFilter = $this->getSegmentFilter('>', 10, 7);
    $queryBuilder = $this->totalSpent->apply($this->createQueryBuilder(), $segmentFilter);
    $statement = $queryBuilder->execute();
    $result = $statement instanceof Statement ? $statement->fetchAll() : [];
    expect($result)->count(3);
  }

  private function createQueryBuilder(): QueryBuilder {
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    return $this->entityManager
      ->getConnection()
      ->createQueryBuilder()
      ->select("$subscribersTable.id as inner_subscriber_id")
      ->from($subscribersTable);
  }

  private function getSegmentFilter(string $type, float $amount, int $days): DynamicSegmentFilterEntity {
    $data = new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_WOOCOMMERCE, WooCommerceTotalSpent::ACTION_TOTAL_SPENT, [
      'total_spent_type' => $type,
      'total_spent_amount' => $amount,
      'total_spent_days' => $days,
    ]);

    $segment = new SegmentEntity('Dynamic Segment', SegmentEntity::TYPE_DYNAMIC, 'description');
    $this->entityManager->persist($segment);
    $dynamicSegmentFilter = new DynamicSegmentFilterEntity($segment, $data);
    $this->entityManager->persist($dynamicSegmentFilter);
    $segment->addDynamicFilter($dynamicSegmentFilter);
    return $dynamicSegmentFilter;
  }

  private function createOrder(int $customerId, Carbon $createdAt, int $orderTotal): int {
    $order = $this->tester->createWooCommerceOrder();
    $order->set_customer_id($customerId);
    $order->set_date_created($createdAt->toDateTimeString());
    $order->set_status('wc-completed');
    $order->set_total((string)$orderTotal);
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

    if (is_array($this->orders)) {
      foreach ($this->orders as $orderId) {
        $this->wp->wpDeletePost($orderId);
      }
    }

    $this->connection->executeQuery("TRUNCATE TABLE {$wpdb->prefix}wc_customer_lookup");
    $this->connection->executeQuery("TRUNCATE TABLE {$wpdb->prefix}wc_order_stats");
  }
}
