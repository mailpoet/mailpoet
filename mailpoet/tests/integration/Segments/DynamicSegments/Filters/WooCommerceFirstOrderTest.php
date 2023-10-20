<?php declare(strict_types = 1);

namespace integration\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Segments\DynamicSegments\Filters\WooCommerceFirstOrder;
use MailPoet\Test\DataFactories\Subscriber;
use MailPoetVendor\Carbon\Carbon;

/**
 * @group woo
 */
class WooCommerceFirstOrderTest extends \MailPoetTest {

  /** @var WooCommerceFirstOrder */
  private $wooCommerceFirstOrder;

  public function _before(): void {
    parent::_before();
    $this->wooCommerceFirstOrder = $this->diContainer->get(WooCommerceFirstOrder::class);
  }

  public function testGetSubscribersWithFirstOrderBeforeDate(): void {
    $customerId1 = $this->tester->createCustomer('c1@example.com');
    $customerId2 = $this->tester->createCustomer('c2@example.com');
    $this->createOrder($customerId1, new Carbon('2023-02-20'));
    $this->createOrder($customerId2, new Carbon('2023-02-22'));
    $emails = $this->getSubscriberEmailsMatchingFilter('before', '2023-02-21');
    expect(count($emails))->equals(1);
    expect($emails)->equals(['c1@example.com']);
  }

  public function testGetSubscribersWithFirstOrderAfterDate(): void {
    $customerId1 = $this->tester->createCustomer('c1@example.com');
    $customerId2 = $this->tester->createCustomer('c2@example.com');
    $customerId3 = $this->tester->createCustomer('c3@example.com');
    $this->createOrder($customerId1, new Carbon('2023-02-02'));
    $this->createOrder($customerId2, new Carbon('2023-02-01'));
    $this->createOrder($customerId2, new Carbon('2023-02-03'));
    $this->createOrder($customerId3, new Carbon('1993-01-01'));
    $this->createOrder($customerId3, new Carbon('2024-01-01'));
    $emails = $this->getSubscriberEmailsMatchingFilter('after', '2023-02-01');
    expect($emails)->count(1);
    expect($emails)->equals(['c1@example.com']);
  }

  public function testInTheLast(): void {
    $customerId1 = $this->tester->createCustomer('c1@example.com');
    $this->createOrder($customerId1, Carbon::now()->subDays(3));
    $emails = $this->getSubscriberEmailsMatchingFilter('inTheLast', '5');
    expect(count($emails))->equals(1);
    $this->assertEqualsCanonicalizing(['c1@example.com'], $emails);

    $this->createOrder($customerId1, Carbon::now()->subDays(6));
    $emails = $this->getSubscriberEmailsMatchingFilter('inTheLast', '5');
    expect(count($emails))->equals(0);
  }

  public function testNotInTheLast(): void {
    $customerId1 = $this->tester->createCustomer('c1@example.com');
    $customerId2 = $this->tester->createCustomer('c2@example.com');
    $customerId3 = $this->tester->createCustomer('c3@example.com');
    $this->createOrder($customerId1, Carbon::now()->subDays(3));
    $this->createOrder($customerId2, Carbon::now()->subDays(4));
    $this->createOrder($customerId3, Carbon::now()->subDays(5));
    $this->createOrder($customerId3, Carbon::now()->subDays(2));
    $emails = $this->getSubscriberEmailsMatchingFilter('notInTheLast', '5');
    expect(count($emails))->equals(1);
    expect($emails)->equals(['c3@example.com']);
  }

  public function testNotInTheLastIncludesNonCustomers(): void {
    $this->tester->createCustomer('c1@example.com');
    $customerId2 = $this->tester->createCustomer('c2@example.com');
    $subscriber = (new Subscriber())->create();
    $this->createOrder($customerId2, (new Carbon())->subDays(3));
    $emails = $this->getSubscriberEmailsMatchingFilter('notInTheLast', '4');
    $this->assertEqualsCanonicalizing([$subscriber->getEmail(), 'c1@example.com'], $emails);
  }

  public function testOnDate(): void {
    $customerId1 = $this->tester->createCustomer('c1@example.com');
    $customerId2 = $this->tester->createCustomer('c2@example.com');
    $customerId3 = $this->tester->createCustomer('c3@example.com');
    $this->createOrder($customerId1, new Carbon('2023-01-01'));
    $this->createOrder($customerId1, new Carbon('2023-01-02')); // On the date, but not first order
    $this->createOrder($customerId2, new Carbon('2023-01-02'));
    $this->createOrder($customerId3, new Carbon('2023-01-03'));
    $emails = $this->getSubscriberEmailsMatchingFilter('on', '2023-01-02');
    expect(count($emails))->equals(1);
    expect($emails)->equals(['c2@example.com']);
  }

  public function testOnOrAfterDate(): void {
    $customerId1 = $this->tester->createCustomer('c1@example.com');
    $customerId2 = $this->tester->createCustomer('c2@example.com');
    $customerId3 = $this->tester->createCustomer('c3@example.com');
    $this->createOrder($customerId1, new Carbon('2023-01-01'));
    $this->createOrder($customerId1, new Carbon('2023-01-03')); // after the date, but not first order
    $this->createOrder($customerId2, new Carbon('2023-01-02'));
    $this->createOrder($customerId3, new Carbon('2023-01-03'));
    $emails = $this->getSubscriberEmailsMatchingFilter('onOrAfter', '2023-01-02');
    expect(count($emails))->equals(2);
    expect($emails)->equals(['c2@example.com', 'c3@example.com']);
  }

  public function testOnOrBeforeDate(): void {
    $customerId1 = $this->tester->createCustomer('c1@example.com');
    $customerId2 = $this->tester->createCustomer('c2@example.com');
    $customerId3 = $this->tester->createCustomer('c3@example.com');
    $this->createOrder($customerId1, new Carbon('2023-01-01'));
    $this->createOrder($customerId2, new Carbon('2023-01-02'));
    $this->createOrder($customerId3, new Carbon('2023-01-03'));
    $emails = $this->getSubscriberEmailsMatchingFilter('onOrBefore', '2023-01-02');
    expect(count($emails))->equals(2);
    expect($emails)->equals(['c1@example.com', 'c2@example.com']);
  }

  public function testNotOn(): void {
    $customerId1 = $this->tester->createCustomer('c1@example.com');
    $customerId2 = $this->tester->createCustomer('c2@example.com');
    $customerId3 = $this->tester->createCustomer('c3@example.com');
    $this->createOrder($customerId1, new Carbon('2023-01-01'));
    $this->createOrder($customerId2, new Carbon('2023-01-02'));
    $this->createOrder($customerId3, new Carbon('2023-01-03'));
    $emails = $this->getSubscriberEmailsMatchingFilter('notOn', '2023-01-02');
    expect(count($emails))->equals(2);
    $this->assertEqualsCanonicalizing(['c1@example.com', 'c3@example.com'], $emails);

    $this->createOrder($customerId3, new Carbon('2023-01-02'));
    $emails = $this->getSubscriberEmailsMatchingFilter('notOn', '2023-01-02');
    expect(count($emails))->equals(1);
    $this->assertEqualsCanonicalizing(['c1@example.com'], $emails);
  }

  public function testNotOnReturnsNonCustomersToo() {
    $this->tester->createCustomer('c1@example.com');
    $customerId2 = $this->tester->createCustomer('c2@example.com');
    $subscriber = (new Subscriber())->create();
    $this->createOrder($customerId2, new Carbon('2023-02-22'));
    $emails = $this->getSubscriberEmailsMatchingFilter('notOn', '2023-02-22');
    $this->assertEqualsCanonicalizing([$subscriber->getEmail(), 'c1@example.com'], $emails);
  }

  public function testItOnlyIncludesCompletedAndProcessingOrders(): void {
    $validStatuses = ['wc-processing', 'wc-completed'];
    $invalidstatuses = ['wc-pending', 'wc-refunded', 'wc-on-hold', 'wc-cancelled', 'wc-failed', 'any-custom-status'];
    $date = '2023-02-24';
    foreach ($validStatuses as $validStatus) {
      $customerId = $this->tester->createCustomer("$validStatus@example.com");
      $this->createOrder($customerId, new Carbon($date), $validStatus);
    }
    foreach ($invalidstatuses as $invalidStatus) {
      $customerId = $this->tester->createCustomer("$invalidStatus@example.com");
      $this->createOrder($customerId, new Carbon($date), $invalidStatus);
    }
    $emails = $this->getSubscriberEmailsMatchingFilter('on', $date);
    expect($emails)->count(2);
    $this->assertEqualsCanonicalizing(['wc-processing@example.com', 'wc-completed@example.com'], $emails);
  }

  private function getSubscriberEmailsMatchingFilter(string $operator, string $value): array {
    $data = new DynamicSegmentFilterData(
      DynamicSegmentFilterData::TYPE_WOOCOMMERCE,
      WooCommerceFirstOrder::ACTION,
      [
        'operator' => $operator,
        'value' => $value,
      ]
    );
    return $this->tester->getSubscriberEmailsMatchingDynamicFilter($data, $this->wooCommerceFirstOrder);
  }

  private function createOrder(int $customerId, Carbon $createdAt, string $status = 'wc-completed'): int {
    $order = $this->tester->createWooCommerceOrder();
    $order->set_customer_id($customerId);
    $order->set_date_created($createdAt->toDateTimeString());
    $order->set_status($status);
    $order->save();
    $this->tester->updateWooOrderStats($order->get_id());

    return $order->get_id();
  }

  public function _after() {
    parent::_after();
    $this->cleanUp();
  }

  private function cleanUp(): void {
    global $wpdb;
    $this->connection->executeQuery("TRUNCATE TABLE {$wpdb->prefix}wc_customer_lookup");
    $this->connection->executeQuery("TRUNCATE TABLE {$wpdb->prefix}wc_order_stats");
  }
}
