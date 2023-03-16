<?php declare(strict_types = 1);

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoetVendor\Carbon\Carbon;

/**
 * @group woo
 */
class WooCommerceTotalSpentTest extends \MailPoetTest {
  /** @var WooCommerceTotalSpent */
  private $totalSpentFilter;

  public function _before(): void {
    $this->totalSpentFilter = $this->diContainer->get(WooCommerceTotalSpent::class);

    $customerId1 = $this->tester->createCustomer('customer1@example.com', 'customer');
    $customerId2 = $this->tester->createCustomer('customer2@example.com', 'customer');
    $customerId3 = $this->tester->createCustomer('customer3@example.com', 'customer');

    $this->createOrder($customerId1, Carbon::now()->subDays(3), 10);
    $this->createOrder($customerId1, Carbon::now(), 5);
    $this->createOrder($customerId2, Carbon::now(), 15);
    $this->createOrder($customerId3, Carbon::now(), 25);
  }

  public function testItGetsCustomersThatSpentFifteenInTheLastDay(): void {
    $segmentFilterData = $this->getSegmentFilterData('=', 15, 1);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->totalSpentFilter);
    $this->assertEqualsCanonicalizing(['customer2@example.com'], $emails);
  }

  public function testItGetsCustomersThatSpentFifteenInTheLastWeek(): void {
    $segmentFilterData = $this->getSegmentFilterData('=', 15, 7);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->totalSpentFilter);
    $this->assertEqualsCanonicalizing(['customer1@example.com', 'customer2@example.com'], $emails);
  }

  public function testItGetsCustomersThatDidNotSpendFifteenInTheLastDay(): void {
    $segmentFilterData = $this->getSegmentFilterData('!=', 15, 1);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->totalSpentFilter);
    $this->assertEqualsCanonicalizing(['customer1@example.com', 'customer3@example.com'], $emails);
  }

  public function testItGetsCustomersThatSpentMoreThanTwentyInTheLastDay(): void {
    $segmentFilterData = $this->getSegmentFilterData('>', 20, 1);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->totalSpentFilter);
    $this->assertEqualsCanonicalizing(['customer3@example.com'], $emails);
  }

  public function testItGetsCustomersThatSpentLessThanTenInTheLastDay(): void {
    $segmentFilterData = $this->getSegmentFilterData('<', 10, 1);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->totalSpentFilter);
    $this->assertEqualsCanonicalizing(['customer1@example.com'], $emails);
  }

  public function testItGetsCustomersThatSpentMoreThanTenInTheLastWeek(): void {
    $segmentFilterData = $this->getSegmentFilterData('>', 10, 7);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->totalSpentFilter);
    $this->assertEqualsCanonicalizing(['customer1@example.com', 'customer2@example.com', 'customer3@example.com'], $emails);
  }

  private function getSegmentFilterData(string $type, float $amount, int $days): DynamicSegmentFilterData {
    return new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_WOOCOMMERCE, WooCommerceTotalSpent::ACTION_TOTAL_SPENT, [
      'total_spent_type' => $type,
      'total_spent_amount' => $amount,
      'total_spent_days' => $days,
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
    $this->cleanUp();
  }

  private function cleanUp(): void {
    $this->truncateEntity(SegmentEntity::class);
    $this->truncateEntity(SubscriberEntity::class);
  }
}
