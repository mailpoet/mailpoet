<?php declare(strict_types = 1);

namespace MailPoet\Segments\DynamicSegments\Filters;

use Automattic\WooCommerce\Admin\API\Reports\Orders\Stats\DataStore as OrdersStatsDataStore;
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
class WooCommerceCountryTest extends \MailPoetTest {

  /** @var WooCommerceCountry */
  private $wooCommerceCountry;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  public function _before(): void {
    $this->wooCommerceCountry = $this->diContainer->get(WooCommerceCountry::class);
    $this->subscribersRepository = $this->diContainer->get(SubscribersRepository::class);

    $this->cleanup();

    $userId1 = $this->tester->createWordPressUser('customer1@example.com', 'customer');
    $userId2 = $this->tester->createWordPressUser('customer2@example.com', 'customer');
    $userId3 = $this->tester->createWordPressUser('customer3@example.com', 'customer');
    $userId4 = $this->tester->createWordPressUser('customer4@example.com', 'customer');

    $this->createCustomerLookupData(['user_id' => $userId1, 'email' => 'customer1@example.com', 'country' => 'CZ']);
    $this->createCustomerLookupData(['user_id' => $userId2, 'email' => 'customer2@example.com', 'country' => 'US']);
    $this->createCustomerLookupData(['user_id' => $userId3, 'email' => 'customer3@example.com', 'country' => 'US']);
    $this->createCustomerLookupData(['user_id' => $userId4, 'email' => 'customer4@example.com', 'country' => 'ES']);

  }

  public function testItAppliesFilter(): void {
    $segmentFilter = $this->getSegmentFilter('CZ');
    $queryBuilder = $this->wooCommerceCountry->apply($this->getQueryBuilder(), $segmentFilter);
    $statement = $queryBuilder->execute();
    $this->assertInstanceOf(DriverStatement::class, $statement);
    $result = $statement->fetchAll();
    expect(count($result))->equals(1);
    $this->assertIsArray($result[0]);
    $subscriber1 = $this->subscribersRepository->findOneById($result[0]['inner_subscriber_id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber1);
    expect($subscriber1)->isInstanceOf(SubscriberEntity::class);
    expect($subscriber1->getEmail())->equals('customer1@example.com');
  }

  public function testItAppliesFilterAny(): void {
    $segmentFilter = $this->getSegmentFilter(['CZ','US']);
    $queryBuilder = $this->wooCommerceCountry->apply($this->getQueryBuilder(), $segmentFilter);
    $statement = $queryBuilder->execute();
    $this->assertInstanceOf(DriverStatement::class, $statement);
    $result = $statement->fetchAll();
    expect(count($result))->equals(3);
    $this->assertIsArray($result[0]);
    $subscriber1 = $this->subscribersRepository->findOneById($result[0]['inner_subscriber_id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber1);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber1);
    expect($subscriber1->getEmail())->equals('customer1@example.com');
    $this->assertIsArray($result[1]);
    $subscriber2 = $this->subscribersRepository->findOneById($result[1]['inner_subscriber_id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber2);
    expect($subscriber2)->isInstanceOf(SubscriberEntity::class);
    expect($subscriber2->getEmail())->equals('customer2@example.com');
    $this->assertIsArray($result[2]);
    $subscriber3 = $this->subscribersRepository->findOneById($result[2]['inner_subscriber_id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber3);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber3);
    expect($subscriber3->getEmail())->equals('customer3@example.com');
  }

  public function testItAppliesFilterNone(): void {
    $segmentFilter = $this->getSegmentFilter(['CZ','US'], DynamicSegmentFilterData::OPERATOR_NONE);
    $queryBuilder = $this->wooCommerceCountry->apply($this->getQueryBuilder(), $segmentFilter);
    $statement = $queryBuilder->execute();
    $this->assertInstanceOf(DriverStatement::class, $statement);
    $result = $statement->fetchAll();
    expect(count($result))->equals(1);
    $this->assertIsArray($result[0]);
    $subscriber1 = $this->subscribersRepository->findOneById($result[0]['inner_subscriber_id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber1);
    expect($subscriber1->getEmail())->equals('customer4@example.com');
  }

  private function getQueryBuilder(): QueryBuilder {
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    return $this->entityManager
      ->getConnection()
      ->createQueryBuilder()
      ->select("$subscribersTable.id as inner_subscriber_id")
      ->from($subscribersTable);
  }

  /**
   * @param string[]|string $country
   * @param string $operator
   * @return DynamicSegmentFilterEntity
   */
  private function getSegmentFilter($country, $operator = null): DynamicSegmentFilterEntity {
    $filterData = [
      'country_code' => $country,
    ];
    if ($operator) {
      $filterData['operator'] = $operator;
    }
    $data = new DynamicSegmentFilterData(
      DynamicSegmentFilterData::TYPE_WOOCOMMERCE,
      WooCommerceCountry::ACTION_CUSTOMER_COUNTRY,
      $filterData
    );
    $segment = new SegmentEntity('Dynamic Segment', SegmentEntity::TYPE_DYNAMIC, 'description');
    $this->entityManager->persist($segment);
    $dynamicSegmentFilter = new DynamicSegmentFilterEntity($segment, $data);
    $this->entityManager->persist($dynamicSegmentFilter);
    $segment->addDynamicFilter($dynamicSegmentFilter);
    return $dynamicSegmentFilter;
  }

  private function createCustomerLookupData(array $data): void {
    $order = $this->tester->createWooCommerceOrder();
    $order->set_customer_id($data['user_id']);
    $order->set_billing_email($data['email']);
    $order->set_billing_country($data['country']);
    $order->save();
    // Force sync to lookup table
    OrdersStatsDataStore::sync_order($order->get_id());
  }

  private function cleanUpLookUpTables(): void {
    global $wpdb;
    $connection = $this->entityManager->getConnection();
    $lookupTable = $wpdb->prefix . 'wc_customer_lookup';
    $orderLookupTable = $wpdb->prefix . 'wc_order_stats';
    $connection->executeStatement("TRUNCATE $lookupTable");
    $connection->executeStatement("TRUNCATE $orderLookupTable");
  }

  public function _after(): void {
    parent::_after();
    $this->cleanUp();
  }

  private function cleanup(): void {
    $this->cleanUpLookUpTables();
  }
}
