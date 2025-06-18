<?php declare(strict_types = 1);

namespace integration\Segments\DynamicSegments\Filters;

use MailPoet\Segments\DynamicSegments\Filters\WooFilterHelper;
use MailPoetVendor\Carbon\Carbon;

/**
 * @group woo
 */
class WooFilterHelperTest extends \MailPoetTest {
  /** @var WooFilterHelper */
  private $wooFilterHelper;

  public function _before() {
    parent::_before();
    $this->wooFilterHelper = $this->diContainer->get(WooFilterHelper::class);
  }

  /**
   * @dataProvider includedStatuses
   */
  public function testItCanJoinCustomersBasedOnPurchaseStatus($status) {
    $customerId = $this->tester->createCustomer('customer@example.com');
    $this->createOrder($customerId, $status);
    $queryBuilder = $this->tester->getSubscribersQueryBuilder();
    // Pass an empty array to exclude no statuses, which should include all statuses
    $this->wooFilterHelper->applyOrderStatusFilter($queryBuilder, []);
    $emails = $this->tester->getSubscriberEmailsFromQueryBuilder($queryBuilder);
    verify($emails)->arrayContains('customer@example.com');
  }

  /**
   * @dataProvider excludedStatuses
   */
  public function testItExcludesDisallowedOrderStatuses($status) {
    $customerId = $this->tester->createCustomer('customer@example.com');
    $this->createOrder($customerId, $status);
    $queryBuilder = $this->tester->getSubscribersQueryBuilder();
    $this->wooFilterHelper->applyOrderStatusFilter($queryBuilder);
    $emails = $this->tester->getSubscriberEmailsFromQueryBuilder($queryBuilder);
    verify($emails)->arrayNotContains('customer@example.com');
  }

  public function testOrderStatusesCanBeOverridden() {
    $customerId = $this->tester->createCustomer('refunded@example.com');
    $this->createOrder($customerId, 'wc-refunded');
    $customerId2 = $this->tester->createCustomer('completed@example.com');
    $this->createOrder($customerId2, 'wc-completed');
    $queryBuilder = $this->tester->getSubscribersQueryBuilder();
    // Override to exclude both refunded and completed statuses
    $this->wooFilterHelper->applyOrderStatusFilter($queryBuilder, ['wc-refunded', 'wc-completed']);
    $emails = $this->tester->getSubscriberEmailsFromQueryBuilder($queryBuilder);
    verify($emails)->arrayNotContains('refunded@example.com');
    verify($emails)->arrayNotContains('completed@example.com');
  }

  public function includedStatuses() {
    // These are statuses that are not in the excluded list
    return [
      'other' => ['wc-other-status'],
    ];
  }

  public function excludedStatuses() {
    return [
      'refunded' => ['wc-refunded'],
      'cancelled' => ['wc-cancelled'],
      'on hold' => ['wc-on-hold'],
      'pending' => ['wc-pending'],
      'failed' => ['wc-failed'],
    ];
  }

  private function createOrder(int $customerId, string $status): int {
    $order = $this->tester->createWooCommerceOrder();
    $order->set_customer_id($customerId);
    $order->set_date_created(Carbon::now()->toDateTimeString());
    $order->set_status($status);
    $order->save();
    $this->tester->updateWooOrderStats($order->get_id());

    return $order->get_id();
  }

  public function _after() {
    parent::_after();
    global $wpdb;
    $this->connection->executeQuery("TRUNCATE TABLE {$wpdb->prefix}wc_customer_lookup");
    $this->connection->executeQuery("TRUNCATE TABLE {$wpdb->prefix}wc_order_stats");
    $this->connection->executeQuery("TRUNCATE TABLE {$wpdb->prefix}wc_order_product_lookup");
  }
}
