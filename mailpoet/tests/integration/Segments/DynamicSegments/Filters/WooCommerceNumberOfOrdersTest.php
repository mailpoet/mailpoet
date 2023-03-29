<?php declare(strict_types = 1);

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Subscribers\Source;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
use MailPoetVendor\Carbon\Carbon;

/**
 * @group woo
 */
class WooCommerceNumberOfOrdersTest extends \MailPoetTest {
  /** @var WooCommerceNumberOfOrders */
  private $numberOfOrdersFilter;

  /** @var array */
  private $orders;

  /**
   * @var SubscriberFactory
   */
  private $subscriberFactory;

  public function _before(): void {
    $this->subscriberFactory = new SubscriberFactory();
    $this->numberOfOrdersFilter = $this->diContainer->get(WooCommerceNumberOfOrders::class);
    $this->cleanUp();

    $customerId1 = $this->createCustomer('customer1@example.com', 'customer');
    $customerId2 = $this->createCustomer('customer2@example.com', 'customer');
    $customerId3 = $this->createCustomer('customer3@example.com', 'customer');

    $this->orders[] = $this->createOrder($customerId1, Carbon::now()->subDays(3));
    $this->orders[] = $this->createOrder($customerId2, Carbon::now());
    $this->orders[] = $this->createOrder($customerId2, Carbon::now());
    $this->orders[] = $this->createOrder($customerId3, Carbon::now());
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

  private function createCustomer(string $email, string $role): int {
    global $wpdb;
    $userId = $this->tester->createWordPressUser($email, $role);
    $this->connection->executeQuery("
      INSERT INTO {$wpdb->prefix}wc_customer_lookup (customer_id, user_id, first_name, last_name, email)
      VALUES ({$userId}, {$userId}, 'First Name', 'Last Name', '{$email}')
    ");
    return $userId;
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
    $this->cleanUp();
  }

  private function cleanUp(): void {
    global $wpdb;
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
    $this->connection->executeQuery("TRUNCATE TABLE {$wpdb->prefix}wc_customer_lookup");
    $this->connection->executeQuery("TRUNCATE TABLE {$wpdb->prefix}wc_order_stats");
  }
}
