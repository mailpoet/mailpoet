<?php

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Doctrine\DBAL\Driver\Statement;
use MailPoetVendor\Doctrine\DBAL\Query\QueryBuilder;

class WooCommerceCountryTest extends \MailPoetTest {

  /** @var WooCommerceCountry */
  private $wooCommerceCountry;

  /**  @var WPFunctions */
  private $wp;

  /** @var int[] */
  private $orders;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  public function _before(): void {
    $this->wooCommerceCountry = $this->diContainer->get(WooCommerceCountry::class);
    $this->wp = $this->diContainer->get(WPFunctions::class);
    $this->subscribersRepository = $this->diContainer->get(SubscribersRepository::class);

    $this->cleanup();

    $userId1 = $this->tester->createWordPressUser('customer1@example.com', 'customer');
    $userId2 = $this->tester->createWordPressUser('customer2@example.com', 'customer');
    $userId3 = $this->tester->createWordPressUser('customer3@example.com', 'customer');

    $this->orders[] = $this->createOrder(['user_id' => $userId1, 'billing_country' => 'US']);
    $this->orders[] = $this->createOrder(['user_id' => $userId2, 'billing_country' => 'US']);
    $this->orders[] = $this->createOrder(['user_id' => $userId3]);
  }

  public function testItAppliesFilter(): void {
    $segmentFilter = $this->getSegmentFilter('CZ');
    $queryBuilder = $this->wooCommerceCountry->apply($this->getQueryBuilder(), $segmentFilter);
    $statement = $queryBuilder->execute();
    $result = $statement instanceof Statement ? $statement->fetchAll() : [];
    expect(count($result))->equals(1);
    $subscriber1 = $this->subscribersRepository->findOneById($result[0]['inner_subscriber_id']);
    assert($subscriber1 instanceof SubscriberEntity);
    expect($subscriber1)->isInstanceOf(SubscriberEntity::class);
    expect($subscriber1->getEmail())->equals('customer3@example.com');
  }

  private function getQueryBuilder(): QueryBuilder {
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    return $this->entityManager
      ->getConnection()
      ->createQueryBuilder()
      ->select("$subscribersTable.id as inner_subscriber_id")
      ->from($subscribersTable);
  }

  private function getSegmentFilter(string $country): DynamicSegmentFilterEntity {
    $data = new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_WOOCOMMERCE, WooCommerceCountry::ACTION_CUSTOMER_COUNTRY, [
      'country_code' => $country,
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
        '_billing_country' => $data['billing_country'] ?? 'CZ',
      ],
    ]);
  }

  public function _after(): void {
    $this->cleanUp();
  }

  private function cleanup(): void {
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
