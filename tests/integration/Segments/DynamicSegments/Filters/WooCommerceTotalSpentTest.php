<?php

namespace MailPoet\Segments\DynamicSegments\Filters;

use Carbon\Carbon;
use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Doctrine\DBAL\Driver\Statement;
use MailPoetVendor\Doctrine\DBAL\Query\QueryBuilder;

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

    $userId1 = $this->tester->createWordPressUser('customer1@example.com', 'customer');
    $userId2 = $this->tester->createWordPressUser('customer2@example.com', 'customer');
    $userId3 = $this->tester->createWordPressUser('customer3@example.com', 'customer');

    $this->orders[] = $this->createOrder([
      'user_id' => $userId1,
      'post_date' => Carbon::now()->subDays(3)->toDateTimeString(),
      'order_total' => 10,
    ]);
    $this->orders[] = $this->createOrder(['user_id' => $userId1, 'order_total' => 5]);
    $this->orders[] = $this->createOrder(['user_id' => $userId2, 'order_total' => 15]);
    $this->orders[] = $this->createOrder(['user_id' => $userId3, 'order_total' => 25]);
  }

  public function testItGetsCustomersThatSpentMoreThanTwentyInTheLastDay(): void {
    $segmentFilter = $this->getSegmentFilter('>', 20, 1);
    $queryBuilder = $this->totalSpent->apply($this->createQueryBuilder(), $segmentFilter);
    $statement = $queryBuilder->execute();
    $result = $statement instanceof Statement ? $statement->fetchAll() : [];
    expect($result)->count(1);
    $subscriber1 = $this->subscribersRepository->findOneById($result[0]['inner_subscriber_id']);
    assert($subscriber1 instanceof SubscriberEntity);
    expect($subscriber1)->isInstanceOf(SubscriberEntity::class);
    expect($subscriber1->getEmail())->equals('customer3@example.com');
  }

  public function testItGetsCustomersThatSpentLessThanTenInTheLastDay(): void {
    $segmentFilter = $this->getSegmentFilter('<', 10, 1);
    $queryBuilder = $this->totalSpent->apply($this->createQueryBuilder(), $segmentFilter);
    $statement = $queryBuilder->execute();
    $result = $statement instanceof Statement ? $statement->fetchAll() : [];
    expect($result)->count(1);
    $subscriber1 = $this->subscribersRepository->findOneById($result[0]['inner_subscriber_id']);
    assert($subscriber1 instanceof SubscriberEntity);
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
    $data = new DynamicSegmentFilterData([
      'segmentType' => DynamicSegmentFilterData::TYPE_WOOCOMMERCE,
      'action' => WooCommerceTotalSpent::ACTION_TOTAL_SPENT,
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

  private function createOrder($data): int {
    return (int)$this->wp->wpInsertPost([
      'post_type' => 'shop_order',
      'post_status' => 'wc-completed',
      'post_date' => $data['post_date'] ?? '',
      'meta_input' => [
        '_customer_user' => $data['user_id'] ?? '',
        '_order_total' => $data['order_total'] ?? '1',
      ],
    ]);
  }

  public function _after(): void {
    $this->cleanUp();
  }

  private function cleanUp(): void {
    $this->truncateEntity(SegmentEntity::class);
    $this->truncateEntity(SubscriberEntity::class);
    $emails = ['customer1@example.com', 'customer2@example.com', 'customer3@example.com'];
    foreach ($emails as $email) {
      $this->tester->deleteWordPressUser($email);
    }

    foreach ($this->orders ?? [] as $orderId) {
      $this->wp->wpDeletePost($orderId);
    }
  }
}
