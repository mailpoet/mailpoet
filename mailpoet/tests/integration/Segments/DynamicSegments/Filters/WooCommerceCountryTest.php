<?php declare(strict_types = 1);

namespace MailPoet\Segments\DynamicSegments\Filters;

use Automattic\WooCommerce\Admin\API\Reports\Orders\Stats\DataStore as OrdersStatsDataStore;
use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;

/**
 * @group woo
 */
class WooCommerceCountryTest extends \MailPoetTest {

  /** @var WooCommerceCountry */
  private $wooCommerceCountryFilter;

  public function _before(): void {
    $this->wooCommerceCountryFilter = $this->diContainer->get(WooCommerceCountry::class);

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
    $segmentFilterData = $this->getSegmentFilterData('CZ');
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->wooCommerceCountryFilter);
    $this->assertEqualsCanonicalizing(['customer1@example.com'], $emails);
  }

  public function testItAppliesFilterAny(): void {
    $segmentFilterData = $this->getSegmentFilterData(['CZ','US']);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->wooCommerceCountryFilter);
    $this->assertEqualsCanonicalizing(['customer1@example.com', 'customer2@example.com', 'customer3@example.com'], $emails);
  }

  public function testItAppliesFilterNone(): void {
    $segmentFilterData = $this->getSegmentFilterData(['CZ','US'], DynamicSegmentFilterData::OPERATOR_NONE);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->wooCommerceCountryFilter);
    $this->assertEqualsCanonicalizing(['customer4@example.com'], $emails);
  }

  /**
   * @param string[]|string $country
   * @param string|null $operator
   * @return DynamicSegmentFilterData
   */
  private function getSegmentFilterData($country, string $operator = null): DynamicSegmentFilterData {
    $filterData = [
      'country_code' => $country,
    ];
    if ($operator) {
      $filterData['operator'] = $operator;
    }
    return new DynamicSegmentFilterData(
      DynamicSegmentFilterData::TYPE_WOOCOMMERCE,
      WooCommerceCountry::ACTION_CUSTOMER_COUNTRY,
      $filterData
    );
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
    $this->cleanUp();
  }

  private function cleanup(): void {
    $this->truncateEntity(SegmentEntity::class);
    $this->truncateEntity(SubscriberEntity::class);

    $emails = ['customer1@example.com', 'customer2@example.com', 'customer3@example.com', 'customer4@example.com'];
    foreach ($emails as $email) {
      $this->tester->deleteWordPressUser($email);
    }
    $this->tester->deleteTestWooOrders();
    $this->cleanUpLookUpTables();
  }
}
