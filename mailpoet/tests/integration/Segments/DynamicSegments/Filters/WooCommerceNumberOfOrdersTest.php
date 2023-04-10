<?php declare(strict_types = 1);

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Subscribers\Source;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
use MailPoetVendor\Carbon\Carbon;

/**
 * @group woo
 */
class WooCommerceNumberOfOrdersTest extends \MailPoetTest {
  /** @var WooCommerceNumberOfOrders */
  private $numberOfOrdersFilter;

  /**
   * @var SubscriberFactory
   */
  private $subscriberFactory;

  public function _before(): void {
    $this->subscriberFactory = new SubscriberFactory();
    $this->numberOfOrdersFilter = $this->diContainer->get(WooCommerceNumberOfOrders::class);
    $this->cleanUp();

    $customerId1 = $this->tester->createCustomer('customer1@example.com', 'customer');
    $customerId2 = $this->tester->createCustomer('customer2@example.com', 'customer');
    $customerId3 = $this->tester->createCustomer('customer3@example.com', 'customer');

    $this->createOrder($customerId1, Carbon::now()->subDays(3));
    $this->createOrder($customerId2, Carbon::now());
    $this->createOrder($customerId2, Carbon::now());
    $this->createOrder($customerId3, Carbon::now());
  }

  public function testItGetsCustomersThatPlacedTwoOrdersInTheLastDay(): void {
    $segmentFilterData = $this->getSegmentFilterData('=', 2, 1);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->numberOfOrdersFilter);
    $this->assertEqualsCanonicalizing(['customer2@example.com'], $emails);
  }

  public function testItGetsCustomersThatPlacedZeroOrdersInTheLastDay(): void {
    $segmentFilterData = $this->getSegmentFilterData('=', 0, 1);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->numberOfOrdersFilter);
    $this->assertEqualsCanonicalizing(['customer1@example.com'], $emails);
  }

  public function testItGetsCustomersThatDidNotPlaceTwoOrdersInTheLastWeek(): void {
    $segmentFilterData = $this->getSegmentFilterData('!=', 2, 7);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->numberOfOrdersFilter);
    $this->assertEqualsCanonicalizing(['customer1@example.com', 'customer3@example.com'], $emails);
  }

  public function testItGetsCustomersThatPlacedAtLeastOneOrderInTheLastWeek(): void {
    $segmentFilterData = $this->getSegmentFilterData('>', 0, 7);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->numberOfOrdersFilter);
    $this->assertEqualsCanonicalizing(['customer1@example.com', 'customer2@example.com', 'customer3@example.com'], $emails);
  }

  public function testItGetsNoneCustomerSubscriberInLastDays() {
    $createdSub = $this->subscriberFactory
      ->withSource(Source::API)
      ->withCreatedAt(Carbon::now()->subDays(5))
      ->create();
    $segmentFilterData = $this->getSegmentFilterData('=', 0, 30);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->numberOfOrdersFilter);
    $this->assertEqualsCanonicalizing([$createdSub->getEmail()], $emails);
  }

  private function getSegmentFilterData(string $comparisonType, int $ordersCount, int $days): DynamicSegmentFilterData {
    return new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_WOOCOMMERCE, WooCommerceNumberOfOrders::ACTION_NUMBER_OF_ORDERS, [
      'number_of_orders_type' => $comparisonType,
      'number_of_orders_count' => $ordersCount,
      'number_of_orders_days' => $days,
    ]);
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
    $this->connection->executeQuery("TRUNCATE TABLE {$wpdb->prefix}wc_customer_lookup");
    $this->connection->executeQuery("TRUNCATE TABLE {$wpdb->prefix}wc_order_stats");
  }
}
