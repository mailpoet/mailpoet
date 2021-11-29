<?php declare(strict_types = 1);

namespace MailPoet\Segments\DynamicSegments\Filters;

use Carbon\Carbon;
use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoetVendor\Doctrine\DBAL\ForwardCompatibility\DriverStatement;

class WooCommerceNumberOfOrdersTest extends \MailPoetTest {
  /** @var WooCommerceNumberOfOrders */
  private $numberOfOrders;

  private $orders;

  public function _before() {
    $this->numberOfOrders = $this->diContainer->get(WooCommerceNumberOfOrders::class);
    $this->cleanUp();

    $this->tester->createWordPressUser('customer1@example.com', 'customer');
    $this->tester->createWordPressUser('customer2@example.com', 'customer');
    $this->tester->createWordPressUser('customer3@example.com', 'customer');
    $this->setIsWoocommerceUser([
      'customer1@example.com',
      'customer2@example.com',
      'customer3@example.com',
    ]);

    $this->orders[] = $this->createOrder(['email' => 'customer1@example.com', 'post_date' => Carbon::now()->subDays(3)->toDateTimeString()]);
    $this->orders[] = $this->createOrder(['email' => 'customer2@example.com']);
    $this->orders[] = $this->createOrder(['email' => 'customer2@example.com']);
    $this->orders[] = $this->createOrder(['email' => 'customer3@example.com']);
  }

  public function testItGetsCustomersThatPlacedTwoOrdersInTheLastDay() {
    $segmentFilter = $this->getSegmentFilter('=', 2, 1);
    $queryBuilder = $this->numberOfOrders->apply($this->getQueryBuilder(), $segmentFilter);
    $statement = $queryBuilder->execute();
    assert($statement instanceof DriverStatement);
    $result = $statement->fetchAll();
    $this->assertSame(1, count($result));
    $subscriber1 = $this->entityManager->find(SubscriberEntity::class, $result[0]['inner_subscriber_id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber1);
    $this->assertSame('customer2@example.com', $subscriber1->getEmail());
  }

  public function testItGetsCustomersThatPlacedZeroOrdersInTheLastDay() {
    $segmentFilter = $this->getSegmentFilter('=', 0, 1);
    $queryBuilder = $this->numberOfOrders->apply($this->getQueryBuilder(), $segmentFilter);
    $statement = $queryBuilder->execute();
    assert($statement instanceof DriverStatement);
    $result = $statement->fetchAll();
    $this->assertSame(1, count($result));
    $subscriber1 = $this->entityManager->find(SubscriberEntity::class, $result[0]['inner_subscriber_id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber1);
    $this->assertSame('customer1@example.com', $subscriber1->getEmail());
  }

  public function testItGestCustomersThatPlacedAtLeastOneOrderInTheLastWeek() {
    $segmentFilter = $this->getSegmentFilter('>', 0, 7);
    $queryBuilder = $this->numberOfOrders->apply($this->getQueryBuilder(), $segmentFilter);
    $statement = $queryBuilder->execute();
    assert($statement instanceof DriverStatement);
    $result = $statement->fetchAll();
    $this->assertSame(3, count($result));
  }

  private function getQueryBuilder() {
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    return $this->entityManager
      ->getConnection()
      ->createQueryBuilder()
      ->select("$subscribersTable.id as inner_subscriber_id")
      ->from($subscribersTable);
  }

  private function getSegmentFilter($comparisonType, $ordersCount, $days): DynamicSegmentFilterEntity {
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

  private function createOrder($data) {
    $orderData = [
      'post_type' => 'shop_order',
      'post_status' => 'wc-completed',
      'post_date' => $data['post_date'] ?? '',
      'meta_input' => [
        '_billing_email' => $data['email'],
      ],
    ];

    return wp_insert_post($orderData);
  }

  public function _after() {
    $this->cleanUp();
  }

  private function cleanUp() {
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
  }

  private function setIsWoocommerceUser(array $emails): void {
    $subscriberTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    foreach ($emails as $email) {
      $this->entityManager->getConnection()->update(
        $subscriberTable,
        ['is_woocommerce_user' => '1'],
        ['email' => $email]
      );
    }
  }
}
