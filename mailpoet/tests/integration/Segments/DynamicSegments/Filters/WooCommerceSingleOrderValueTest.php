<?php declare(strict_types = 1);

namespace integration\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Segments\DynamicSegments\Filters\WooCommerceSingleOrderValue;
use MailPoetVendor\Carbon\Carbon;

/**
 * @group woo
 */
class WooCommerceSingleOrderValueTest extends \MailPoetTest {
  /** @var WooCommerceSingleOrderValue */
  private $singleOrderValue;

  public function _before(): void {
    $this->singleOrderValue = $this->diContainer->get(WooCommerceSingleOrderValue::class);
    $this->cleanUp();

    $customerId1 = $this->tester->createCustomer('customer1@example.com');
    $customerId2 = $this->tester->createCustomer('customer2@example.com');
    $customerId3 = $this->tester->createCustomer('customer3@example.com');

    $this->createOrder($customerId1, Carbon::now()->subDays(3), 10);
    $this->createOrder($customerId1, Carbon::now(), 5);
    $this->createOrder($customerId2, Carbon::now(), 15);
    $this->createOrder($customerId3, Carbon::now(), 25);
  }

  public function testItGetsCustomersThatSpentFifteenInAnOrderInTheLastDay(): void {
    $segmentFilterData = $this->getSegmentFilterData('=', 15, 1);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->singleOrderValue);
    verify($emails)->equals(['customer2@example.com']);
  }

  public function testItGetsCustomersThatSpentFifteenInAnOrderInTheLastWeek(): void {
    $segmentFilterData = $this->getSegmentFilterData('=', 15, 7);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->singleOrderValue);
    verify($emails)->equals(['customer2@example.com']);
  }

  public function testItGetsCustomersThatDidNotSpendFifteenInAnOrderInTheLastDay(): void {
    $segmentFilterData = $this->getSegmentFilterData('!=', 15, 1);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->singleOrderValue);
    $this->assertEqualsCanonicalizing(['customer1@example.com', 'customer3@example.com'], $emails);
  }

  public function testItGetsCustomersThatSpentMoreThanTwentyInAnOrderInTheLastDay(): void {
    $segmentFilterData = $this->getSegmentFilterData('>', 20, 1);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->singleOrderValue);
    verify($emails)->equals(['customer3@example.com']);
  }

  public function testItGetsCustomersThatSpentLessThanTenInAnOrderInTheLastDay(): void {
    $segmentFilterData = $this->getSegmentFilterData('<', 10, 1);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->singleOrderValue);
    verify($emails)->equals(['customer1@example.com']);
  }

  public function testItGetsCustomersThatSpentMoreThanTenInAnOrderInTheLastWeek(): void {
    $segmentFilterData = $this->getSegmentFilterData('>', 10, 7);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->singleOrderValue);
    $this->assertEqualsCanonicalizing(['customer2@example.com', 'customer3@example.com'], $emails);
  }

  public function testItWorksWithLifetimeOption(): void {
    $segmentFilterData = $this->getSegmentFilterData('<', 1000000000, 0, DynamicSegmentFilterData::TIMEFRAME_ALL_TIME);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->singleOrderValue);
    $this->assertEqualsCanonicalizing(['customer1@example.com', 'customer2@example.com', 'customer3@example.com'], $emails);
  }

  private function getSegmentFilterData(string $type, float $amount, int $days, $timeframe = DynamicSegmentFilterData::TIMEFRAME_IN_THE_LAST): DynamicSegmentFilterData {
    return new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_WOOCOMMERCE, WooCommerceSingleOrderValue::ACTION_SINGLE_ORDER_VALUE, [
      'single_order_value_type' => $type,
      'single_order_value_amount' => $amount,
      'days' => $days,
      'timeframe' => $timeframe,
    ]);
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
    $this->connection->executeQuery("TRUNCATE TABLE {$wpdb->prefix}wc_customer_lookup");
    $this->connection->executeQuery("TRUNCATE TABLE {$wpdb->prefix}wc_order_stats");
  }
}
